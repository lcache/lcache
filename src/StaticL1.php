<?php

namespace LCache;

class StaticL1 extends L1
{
    private static $cacheData = [];

    protected $key_overhead;

    /** @var array Reference to the data array for the instance data pool. */
    protected $storage;

    public function __construct($pool, StateL1Interface $state)
    {
        parent::__construct($pool, $state);

        $this->key_overhead = [];

        if (!isset(self::$cacheData[$this->pool])) {
            self::$cacheData[$this->pool] = [];
        }
        $this->storage = &self::$cacheData[$this->pool];
    }

    public function getKeyOverhead(Address $address)
    {
        $local_key = $address->serialize();
        if (array_key_exists($local_key, $this->key_overhead)) {
            return $this->key_overhead[$local_key];
        }
        return 0;
    }

    public function setWithExpiration($event_id, Address $address, $value, $created, $expiration = null)
    {
        $local_key = $address->serialize();

        // If not setting a negative cache entry, increment the key's overhead.
        if (!is_null($value)) {
            if (isset($this->key_overhead[$local_key])) {
                $this->key_overhead[$local_key]++;
            } else {
                $this->key_overhead[$local_key] = 1;
            }
        }

        // Don't overwrite local entries that are even newer or the same age.
        if (isset($this->storage[$local_key]) && $this->storage[$local_key]->event_id >= $event_id) {
            return true;
        }
        $this->storage[$local_key] = new Entry($event_id, $this->getPool(), $address, $value, $created, $expiration);

        return true;
    }

    public function isNegativeCache(Address $address)
    {
        $local_key = $address->serialize();
        return (isset($this->storage[$local_key]) && is_null($this->storage[$local_key]->value));
    }

    public function getEntry(Address $address)
    {
        $local_key = $address->serialize();

        // Decrement the key's overhead.
        if (isset($this->key_overhead[$local_key])) {
            $this->key_overhead[$local_key]--;
        } else {
            $this->key_overhead[$local_key] = -1;
        }

        if (!array_key_exists($local_key, $this->storage)) {
            $this->recordMiss();
            return null;
        }
        $entry = $this->storage[$local_key];
        if (!is_null($entry->expiration) && $entry->expiration < $_SERVER['REQUEST_TIME']) {
            unset($this->storage[$local_key]);
            $this->recordMiss();
            return null;
        }

        $this->recordHit();

        return $entry;
    }

    public function delete($event_id, Address $address)
    {
        $local_key = $address->serialize();
        if ($address->isEntireCache()) {
            $this->storage = array();
            $this->state->clear();
            return true;
        } elseif ($address->isEntireBin()) {
            foreach ($this->storage as $index => $value) {
                if (strpos($index, $local_key) === 0) {
                    unset($this->storage[$index]);
                }
            }
            return true;
        }
        $this->setLastAppliedEventID($event_id);
        // @TODO: Consider adding "race" protection here, like for set.
        unset($this->storage[$local_key]);
        return true;
    }
}
