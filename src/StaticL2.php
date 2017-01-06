<?php

namespace LCache;

class StaticL2 extends L2
{
    /**
     * @var int Shared static counter for the events managed by the driver.
     */
    private static $currentEventId = 0;

    /**
     * @var array Shared static collection that will contain all of the events.
     */
    private static $allEvents = [];

    /**
     * @var array
     *   Shared static collection that will contain for all managed cache tags.
     */
    private static $allTags = [];


    protected $events;
    protected $current_event_id;
    protected $hits;
    protected $misses;
    protected $tags;

    public function __construct()
    {
        // Share the data
        $this->current_event_id = &self::$currentEventId;
        $this->events = &self::$allEvents;
        $this->tags = &self::$allTags;

        $this->hits = 0;
        $this->misses = 0;
    }

    /**
     * Testing utility.
     *
     * Used to reset the shared static state during a single proccess execution.
     */
    public static function resetStorageState()
    {
        static::$allTags = [];
        static::$allEvents = [];
        static::$currentEventId = 0;
    }

    public function countGarbage()
    {
        $garbage = 0;
        foreach ($this->events as $event_id => $entry) {
            if ($entry->expiration < $_SERVER['REQUEST_TIME']) {
                $garbage++;
            }
        }
        return $garbage;
    }

    public function collectGarbage($item_limit = null)
    {
        $deleted = 0;
        foreach ($this->events as $event_id => $entry) {
            if ($entry->expiration < $_SERVER['REQUEST_TIME']) {
                unset($this->events[$event_id]);
                $deleted++;
            }
            if ($deleted === $item_limit) {
                break;
            }
        }
        return $deleted;
    }


    /**
     * {inheritDock}
     */
    public function getEntry(Address $address)
    {
        $events = array_filter($this->events, function (Entry $entry) use ($address) {
            return $entry->getAddress()->isMatch($address);
        });
        $last_matching_entry = null;
        foreach ($events as $entry) {
            if ($entry->getAddress()->isEntireCache() || $entry->getAddress()->isEntireBin()) {
                $last_matching_entry = null;
            } elseif (!is_null($entry->expiration) && $entry->expiration < $_SERVER['REQUEST_TIME']) {
                $last_matching_entry = null;
            } else {
                $last_matching_entry = clone $entry;
            }
        }
        // Last event was a deletion, so miss.
        if (is_null($last_matching_entry) || is_null($last_matching_entry->value)) {
            $this->misses++;
            return null;
        }

        $unserialized_value = @unserialize($last_matching_entry->value);

        // If unserialization failed, miss.
        if (false === $unserialized_value && serialize(false) !== $last_matching_entry->value) {
            throw new UnserializationException($address, $last_matching_entry->value);
        }

        $last_matching_entry->value = $unserialized_value;

        $this->hits++;
        return $last_matching_entry;
    }

    public function set($pool, Address $address, $value = null, $expiration = null, array $tags = [], $value_is_serialized = false)
    {
        $this->current_event_id++;

        // Serialize the value if it isn't already. We serialize the values
        // in static storage to make it more similar to other persistent stores.
        if (!$value_is_serialized && !is_null($value)) {
            $value = serialize($value);
        }

        // Add the new address event entry.
        $this->events[$this->current_event_id] = new Entry($this->current_event_id, $pool, $address, $value, $_SERVER['REQUEST_TIME'], $expiration);

        // Prunning older events to reduce the driver's memory needs.
        $addressEvents = array_filter($this->events, function (Entry $entry) use ($address) {
            return $entry->getAddress()->isMatch($address);
        });
        foreach ($addressEvents as $event_to_delete) {
            /* @var $event_to_delete Entry */
            if ($event_to_delete->event_id < $this->current_event_id) {
                unset($this->events[$event_to_delete->event_id]);
            }
        }
        unset($addressEvents, $event_to_delete);

        // Clear existing tags linked to the item.
        // This is much more efficient with database-style indexes.
        $filter = function ($current) use ($address) {
            return $address !== $current;
        };
        foreach ($this->tags as $tag => $addresses) {
            $this->tags[$tag] = array_filter($addresses, $filter);
        }

        // Set the tags on the new item.
        foreach ($tags as $tag) {
            if (isset($this->tags[$tag])) {
                $this->tags[$tag][] = $address;
            } else {
                $this->tags[$tag] = [$address];
            }
        }

        return $this->current_event_id;
    }

    /**
     * Implemented based on the one in DatabaseL2 class (unused).
     *
     * @param int $eventId
     * @return Entry
     */
    public function getEvent($eventId)
    {
        if (!isset($this->events[$eventId])) {
            return null;
        }
        $event = clone $this->events[$eventId];
        $event->value = unserialize($event->value);
        return $event;
    }

    /**
     * Removes replaced events from storage.
     *
     * @return boolean
     *   True on success.
     */
    public function pruneReplacedEvents()
    {
        // No pruning needed in this driver.
        // In the end of the request, everyhting is killed.
        // Clean-up is sinchronous in the set method.
        return true;
    }

    public function getAddressesForTag($tag)
    {
        return isset($this->tags[$tag]) ? $this->tags[$tag] : [];
    }

    public function deleteTag(L1 $l1, $tag)
    {
        // Materialize the tag deletion as individual key deletions.
        $event_id = null;
        $pool = $l1->getPool();
        foreach ($this->getAddressesForTag($tag) as $address) {
            $event_id = $this->delete($pool, $address);
            $l1->delete($event_id, $address);
        }
        unset($this->tags[$tag]);
        return $event_id;
    }

    public function applyEvents(L1 $l1)
    {
        $last_applied_event_id = $l1->getLastAppliedEventID();

        // If the L1 cache is empty, bump the last applied ID
        // to the current high-water mark.
        if (is_null($last_applied_event_id)) {
            $l1->setLastAppliedEventID($this->current_event_id);
            return null;
        }

        $applied = 0;
        foreach ($this->events as $event_id => $event) {
            // Skip events that are too old or were created by the local L1.
            if ($event_id <= $last_applied_event_id || $event->pool === $l1->getPool()) {
                continue;
            }

            if (is_null($event->value)) {
                $l1->delete($event->event_id, $event->getAddress());
            } else {
                $unserialized_value = @unserialize($event->value);
                if (false === $unserialized_value && serialize(false) !== $event->value) {
                    // Delete the L1 entry, if any, when we fail to unserialize.
                    $l1->delete($event->event_id, $event->getAddress());
                } else {
                    $l1->setWithExpiration($event->event_id, $event->getAddress(), $unserialized_value, $event->created, $event->expiration);
                }
            }
            $applied++;
        }

        // Just in case there were skipped events, set the high water mark.
        $l1->setLastAppliedEventID($this->current_event_id);
        return $applied;
    }

    public function getHits()
    {
        return $this->hits;
    }

    public function getMisses()
    {
        return $this->misses;
    }
}
