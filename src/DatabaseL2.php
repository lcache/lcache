<?php

namespace LCache;

class DatabaseL2 extends L2
{
    /** @var int */
    protected $hits;

    /** @var int */
    protected $misses;

    /** @var \PDO Database handle object. */
    protected $dbh;

    /** @var bool */
    protected $log_locally;

    /** @var array List of errors that are logged. */
    protected $errors;

    /** @var string */
    protected $table_prefix;

    /** @var array Aggregated list of addresses to be deleted in bulk. */
    protected $address_delete_queue;

    protected $tagsTable;
    protected $eventsTable;

    public function __construct($dbh, $table_prefix = '', $log_locally = false)
    {
        $this->hits = 0;
        $this->misses = 0;
        $this->errors = [];
        $this->address_delete_queue = [];

        $this->dbh = $dbh;
        $this->log_locally = $log_locally;
        $this->table_prefix = $table_prefix;

        $this->tagsTable = $this->prefixTable('lcache_tags');
        $this->eventsTable = $this->prefixTable('lcache_events');
    }

    private function now()
    {
        return $_SERVER['REQUEST_TIME'];
    }

    protected function prefixTable($base_name)
    {
        return $this->table_prefix . $base_name;
    }

    public function pruneReplacedEvents()
    {
        // No deletions, nothing to do.
        if (empty($this->address_delete_queue)) {
            return true;
        }
        // @TODO: Have bin deletions replace key deletions?
        try {
            $conditions = array_fill(0, count($this->address_delete_queue), '("event_id" < ? and "address" = ?)');
            $sql = "DELETE FROM {$this->eventsTable}"
                . " WHERE " . implode(' OR ', $conditions);
            $sth = $this->dbh->prepare($sql);
            foreach (array_keys($this->address_delete_queue) as $i => $address) {
                $event_id = $this->address_delete_queue[$address];
                $sth->bindValue($i * 2 + 1, $event_id, \PDO::PARAM_INT);
                $sth->bindValue($i * 2 + 2, $address, \PDO::PARAM_STR);
            }
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to perform batch deletion', $e);
            return false;
        }

        // Clear the queue.
        $this->address_delete_queue = [];
        return true;
    }

    public function __destruct()
    {
        $this->pruneReplacedEvents();
    }

    public function countGarbage()
    {
        try {
            $sql = 'SELECT COUNT(*) garbage'
                . ' FROM ' . $this->eventsTable
                . ' WHERE "expiration" < :now';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':now', $this->now(), \PDO::PARAM_INT);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to count garbage', $e);
            return null;
        }

        $count = $sth->fetchObject();
        return intval($count->garbage);
    }

    public function collectGarbage($item_limit = null)
    {
        $sql = 'DELETE FROM ' . $this->eventsTable
            . ' WHERE "expiration" < :now';
        // This is not supported by standard SQLite.
        // @codeCoverageIgnoreStart
        if (!is_null($item_limit)) {
            $sql .= ' ORDER BY "event_id" LIMIT :item_limit';
        }
        // @codeCoverageIgnoreEnd
        try {
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':now', $this->now(), \PDO::PARAM_INT);
            // This is not supported by standard SQLite.
            // @codeCoverageIgnoreStart
            if (!is_null($item_limit)) {
                $sth->bindValue(':item_limit', $item_limit, \PDO::PARAM_INT);
            }
            // @codeCoverageIgnoreEnd
            $sth->execute();
            return $sth->rowCount();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to collect garbage', $e);
        }
        return false;
    }

    protected function queueDeletion($eventId, Address $address)
    {
        assert(!$address->isEntireBin());
        // Key by the address, so we will have the last write event ID for a
        // given address in the end of the request.
        $this->address_delete_queue[$address->serialize()] = $eventId;
    }

    protected function logSchemaIssueOrRethrow($description, $pdo_exception)
    {
        $log_only = [
            'HY000' /* General error */,
            '42S22' /* Unknown column */,
            '42S02' /* Base table for view not found */,
        ];

        if (in_array($pdo_exception->getCode(), $log_only, true)) {
            $text = 'LCache Database: ' . $description . ' : ' . $pdo_exception->getMessage();
            if ($this->log_locally) {
                $this->errors[] = $text;
            } else {
                // @codeCoverageIgnoreStart
                trigger_error($text, E_USER_WARNING);
                // @codeCoverageIgnoreEnd
            }
            return;
        }

        // Rethrow anything not whitelisted.
        // @codeCoverageIgnoreStart
        throw $pdo_exception;
        // @codeCoverageIgnoreEnd
    }

    public function getErrors()
    {
        if (!$this->log_locally) {
            // @codeCoverageIgnoreStart
            throw new Exception('Requires setting $log_locally=TRUE on instantiation.');
            // @codeCoverageIgnoreEnd
        }
        return $this->errors;
    }

    /**
     * {inheritDock}
     */
    public function getEntry(Address $address)
    {
        try {
            $sql = 'SELECT "event_id", "pool", "address", "value", "created", "expiration" '
                . ' FROM ' . $this->eventsTable
                . ' WHERE "address" = :address '
                . ' AND ("expiration" >= :now OR "expiration" IS NULL) '
                . ' ORDER BY "event_id" DESC '
                . ' LIMIT 1';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
            $sth->bindValue(':now', $this->now(), \PDO::PARAM_INT);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to search database for cache item', $e);
            return null;
        }
        $last_matching_entry = $sth->fetchObject();

        // No entry or the last one was a deletion - miss.
        if (false === $last_matching_entry || is_null($last_matching_entry->value)) {
            $this->misses++;
            return null;
        }

        $unserialized_value = @unserialize($last_matching_entry->value);

        // If unserialization failed, raise an exception.
        if (false === $unserialized_value && serialize(false) !== $last_matching_entry->value) {
            throw new UnserializationException($address, $last_matching_entry->value);
        }

        // Prepare correct result object.
        $entry = new \LCache\Entry(
            $last_matching_entry->event_id,
            $last_matching_entry->pool,
            clone $address,
            $unserialized_value,
            $last_matching_entry->created
        );

        $this->hits++;
        return $entry;
    }

    // Returns the event entry. Currently used only for testing.
    public function getEvent($event_id)
    {
        $sql = 'SELECT *'
            . ' FROM ' . $this->eventsTable
            . ' WHERE event_id = :event_id';
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(':event_id', $event_id, \PDO::PARAM_INT);
        $sth->execute();
        $event = $sth->fetchObject();
        if (false === $event) {
            return null;
        }
        $event->value = unserialize($event->value);
        return $event;
    }

    public function exists(Address $address)
    {
        try {
            $sql = 'SELECT ("value" IS NOT NULL) AS value_not_null '
                . ' FROM ' . $this->eventsTable
                . ' WHERE "address" = :address'
                . ' AND ("expiration" >= :now OR "expiration" IS NULL)'
                . ' ORDER BY "event_id" DESC'
                . ' LIMIT 1';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
            $sth->bindValue(':now', $this->now(), \PDO::PARAM_INT);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to search database for cache item existence', $e);
            return null;
        }
        $result = $sth->fetchObject();

        $exists = ($result !== false && $result->value_not_null);

        // To comply wiht the LX interface that expects to use LX::get for the
        // implementation, here we need to handle the hit/miss manually.
        $this->{($exists ? 'hits' : 'misses')}++;

        return $exists;
    }

    /**
     * @codeCoverageIgnore
     */
    public function debugDumpState()
    {
        echo PHP_EOL . PHP_EOL . 'Events:' . PHP_EOL;
        $sth = $this->dbh->prepare('SELECT * FROM ' . $this->eventsTable . ' ORDER BY "event_id"');
        $sth->execute();
        while ($event = $sth->fetchObject()) {
            print_r($event);
        }
        unset($sth);
        echo PHP_EOL;

        echo 'Tags:' . PHP_EOL;
        $sth2 = $this->dbh->prepare('SELECT * FROM ' . $this->tagsTable . ' ORDER BY "tag"');
        $sth2->execute();
        $tags_found = false;
        while ($event = $sth2->fetchObject()) {
            print_r($event);
            $tags_found = true;
        }
        if (!$tags_found) {
            echo 'No tag data.' . PHP_EOL;
        }
        echo PHP_EOL;
    }

    /**
     * @todo
     *   Should we consider transactions here? We are doing 3 queries: 1. Add an
     *   event, 2. Delete (if deemed so) and 3. Add tags (if any). All of that
     *   should behave as a single operation in DB. If DB driver is not
     *   supporting that - it should be emulated.
     * @todo
     *   Consider having interface change here, so we do not have all this
     *   input parameters, but a single Entry instance instaead. It has
     *   everything already in it.
     */
    public function set($pool, Address $address, $value = null, $expiration = null, array $tags = [], $value_is_serialized = false)
    {
        // Support pre-serialized values for testing purposes.
        if (!$value_is_serialized && !is_null($value)) {
            $value = serialize($value);
        }

        // Add the event to storage.
        try {
            $sql = 'INSERT INTO ' . $this->eventsTable
                . ' ("pool", "address", "value", "created", "expiration")'
                . ' VALUES'
                . ' (:pool, :address, :value, :now, :expiration)';

            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':pool', $pool, \PDO::PARAM_STR);
            $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
            $sth->bindValue(':value', $value, \PDO::PARAM_LOB);
            $sth->bindValue(':expiration', $expiration, \PDO::PARAM_INT);
            $sth->bindValue(':now', $this->now(), \PDO::PARAM_INT);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to store cache event', $e);
            return null;
        }
        $event_id = $this->dbh->lastInsertId();

        // Handle bin and larger deletions immediately. Queue individual key
        // deletions for shutdown.
        if ($address->isEntireBin() || $address->isEntireCache()) {
            $sql = 'DELETE FROM ' . $this->eventsTable
                . ' WHERE "event_id" < :new_event_id'
                . ' AND "address" LIKE :pattern';
            $pattern = $address->serialize() . '%';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':new_event_id', $event_id, \PDO::PARAM_INT);
            $sth->bindValue(':pattern', $pattern, \PDO::PARAM_STR);
            $sth->execute();
        } else {
            $this->queueDeletion($event_id, $address);
        }

        // Store any new cache tags.
        if (!empty($tags)) {
            try {
                // Unify tags to avoid duplicate keys.
                $tags = array_keys(array_flip($tags));

                // TODO: Consider splitting to multiple multi-row queries.
                // This might be needed when inserting MANY tags for a key.
                $sql = 'INSERT INTO ' . $this->tagsTable
                    . ' ("tag", "event_id")'
                    . ' VALUES '
                    . implode(',', array_fill(0, count($tags), '(?,?)'));
                $sth = $this->dbh->prepare($sql);
                foreach ($tags as $index => $tag_name) {
                    $offset = $index << 1;
                    $sth->bindValue($offset + 1, $tag_name, \PDO::PARAM_STR);
                    $sth->bindValue($offset + 2, $event_id, \PDO::PARAM_INT);
                }
                $sth->execute();
            } catch (\PDOException $e) {
                $this->logSchemaIssueOrRethrow('Failed to associate cache tags', $e);
                return null;
            }
        }

        return $event_id;
    }

    /**
     * Initializes a generator for iterating over tag addresses one by one.
     *
     * @param string $tag
     *   Tag to search the addresses for.
     *
     * @return \Generator|null
     *   When a successfully execuded query is done an activated generator
     *   instance is returned. Otherwise NULL.
     */
    private function getAddressesForTagGenerator($tag)
    {
        try {
            // @TODO: Convert this to using a subquery to only match with the latest event_id.
            // TODO: Move the where condition to a join one to speed-up the query (benchmark with big DB).
            $sql = 'SELECT DISTINCT "address"'
                . ' FROM ' . $this->eventsTable . ' e'
                . ' INNER JOIN ' . $this->tagsTable . ' t ON t.event_id = e.event_id'
                . ' WHERE "tag" = :tag';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':tag', $tag, \PDO::PARAM_STR);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to find cache items associated with tag', $e);
            return null;
        }

        return call_user_func(function () use ($sth) {
            while ($tag_entry = $sth->fetchObject()) {
                $address = new Address();
                $address->unserialize($tag_entry->address);
                yield $address;
            }
        });
    }

    public function getAddressesForTag($tag)
    {
        if (($generator = $this->getAddressesForTagGenerator($tag)) === null) {
            return null;
        }
        return iterator_to_array($generator);
    }

    public function deleteTag(L1 $l1, $tag)
    {
        if (($addressGenerator = $this->getAddressesForTagGenerator($tag)) === null) {
            return null;
        }

        $last_applied_event_id = null;
        foreach ($addressGenerator as $address) {
            $last_applied_event_id = $this->delete($l1->getPool(), $address);
            $l1->delete($last_applied_event_id, $address);
        }
        // We have the possibility to delete many addreses one by one. Any
        // consecuitive tag delete will atempt to delete them again, if not
        // prunned explicitly here. By deleting the stale / old events, we use
        // the DB's ON DELETE CASCADE to clear the relaed tags also.
        $this->pruneReplacedEvents();

        return $last_applied_event_id;
    }

    public function applyEvents(L1 $l1)
    {
        $last_applied_event_id = $l1->getLastAppliedEventID();

        // If the L1 cache is empty, bump the last applied ID
        // to the current high-water mark.
        if (is_null($last_applied_event_id)) {
            try {
                $sql = 'SELECT "event_id"'
                    . ' FROM ' . $this->eventsTable
                    . ' ORDER BY "event_id" DESC'
                    . ' LIMIT 1';
                $sth = $this->dbh->prepare($sql);
                $sth->execute();
            } catch (\PDOException $e) {
                $this->logSchemaIssueOrRethrow('Failed to initialize local event application status', $e);
                return null;
            }
            $last_event = $sth->fetchObject();
            $value = false === $last_event ? 0 : (int) $last_event->event_id;
            $l1->setLastAppliedEventID($value);
            return null;
        }

        try {
            $sql = 'SELECT "event_id", "pool", "address", "value", "created", "expiration"'
                . ' FROM ' . $this->eventsTable
                . ' WHERE "event_id" > :last_applied_event_id'
                . ' ORDER BY event_id';
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(':last_applied_event_id', $last_applied_event_id, \PDO::PARAM_INT);
            $sth->execute();
        } catch (\PDOException $e) {
            $this->logSchemaIssueOrRethrow('Failed to fetch events', $e);
            return null;
        }

        $applied = 0;
        while ($event = $sth->fetchObject()) {
            $last_applied_event_id = $event->event_id;

            // Were created by the local L1.
            if ($event->pool === $l1->getPool()) {
                continue;
            }

            $address = new Address();
            $address->unserialize($event->address);
            if (is_null($event->value)) {
                $l1->delete($event->event_id, $address);
            } else {
                $unserialized_value = @unserialize($event->value);
                if (false === $unserialized_value && serialize(false) !== $event->value) {
                    // Delete the L1 entry, if any, when we fail to unserialize.
                    $l1->delete($event->event_id, $address);
                } else {
                    $l1->setWithExpiration($event->event_id, $address, $unserialized_value, $event->created, $event->expiration);
                }
            }
            $applied++;
        }

        // Just in case there were skipped events, set the high water mark.
        $l1->setLastAppliedEventID($last_applied_event_id);

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
