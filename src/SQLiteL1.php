<?php

namespace LCache;

class SQLiteL1 extends L1
{
    /** @var \PDO */
    private $dbh;

    protected static function tableExists(\PDO $dbh, $table_name)
    {
        try {
            $dbh->query('SELECT 1 FROM ' . $table_name . ' LIMIT 1');
        } catch (\PDOException $e) {
            if (in_array($e->getCode(), ['42S02', 'HY000'])) {
                return false;
            }
            // Rethrow anything else.
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        return true;
    }

    protected static function initializeSchema(\PDO $dbh)
    {
        if (!self::tableExists($dbh, 'entries')) {
            $dbh->exec('CREATE TABLE IF NOT EXISTS entries("address" TEXT PRIMARY KEY, "value" BLOB, "expiration" INTEGER, "created" INTEGER, "event_id" INTEGER NOT NULL DEFAULT 0, "reads" INTEGER NOT NULL DEFAULT 0, "writes" INTEGER NOT NULL DEFAULT 0)');
            $dbh->exec('CREATE INDEX IF NOT EXISTS expiration ON entries ("expiration")');
        }
    }

    protected static function getDatabaseHandle($pool)
    {
        $path = join(DIRECTORY_SEPARATOR, array(sys_get_temp_dir(), 'lcache-' . $pool));
        $dbh = new \PDO('sqlite:' . $path . '.sqlite3');
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA synchronous = OFF');
        $dbh->exec('PRAGMA foreign_keys = ON');
        self::initializeSchema($dbh);
        return $dbh;
    }

    public function __construct($pool = null)
    {
        parent::__construct($pool);
        $this->dbh = self::getDatabaseHandle($this->pool);

        // TODO: Sniff-out APCu presence and use sqlite or null implementation.
        $this->state = new StateL1APCu($this->pool);
    }

    protected function pruneExpiredEntries()
    {
        $sth = $this->dbh->prepare('DELETE FROM entries WHERE expiration < :now');
        $sth->bindValue(':now', $_SERVER['REQUEST_TIME'], \PDO::PARAM_INT);
        try {
            $sth->execute();
        // @codeCoverageIgnoreStart
        } catch (\PDOException $e) {
            $text = 'LCache SQLiteL1: Pruning Failed: ' . $e->getMessage();
            trigger_error($text, E_USER_WARNING);
            return false;
        }
        // @codeCoverageIgnoreEnd
        return $sth->rowCount();
    }

    public function __destruct()
    {
        $this->pruneExpiredEntries();
    }

    public function collectGarbage($item_limit = null)
    {
        $items = $this->pruneExpiredEntries();
        return $items;
    }

    public function getKeyOverhead(Address $address)
    {
        $sth = $this->dbh->prepare('SELECT "reads", "writes" FROM entries WHERE "address" = :address');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->execute();
        $result = $sth->fetchObject();

        if ($result === false) {
            return 0;
        }

        return $result->writes - $result->reads;
    }

    public function setWithExpiration($event_id, Address $address, $value, $created, $expiration = null)
    {
        $serialized_value = null;
        if (!is_null($value)) {
            $serialized_value = serialize($value);
        }

        $sth = $this->dbh->prepare('INSERT OR IGNORE INTO entries ("address", "value", "expiration", "created", "event_id", "writes") VALUES (:address, :value, :expiration, :created, :event_id, 1)');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->bindValue(':value', $serialized_value, \PDO::PARAM_LOB);
        $sth->bindValue(':expiration', $expiration, \PDO::PARAM_INT);
        $sth->bindValue(':created', $created, \PDO::PARAM_INT);
        $sth->bindValue(':event_id', $event_id, \PDO::PARAM_INT);
        $sth->execute();

        // A count of zero means a conflict during the insertion. Update in a way
        // that avoids stomping on newer writes.
        if ($sth->rowCount() === 0) {
            $bump_writes = ', "writes" = "writes" + 1';
            // Don't bump write counts for negative cache entries.
            if (is_null($value)) {
                $bump_writes = '';
            }

            // Always allow overwrites of event ID zero so when there's been a
            // read (which creates a row with a read count of one) and then we
            // still miss L2 (which creates an L1 tombstone here), the update
            // goes through.
            $sth = $this->dbh->prepare('UPDATE entries SET "value" = :value, "expiration" = :expiration, "created" = :created, "event_id" = :event_id ' . $bump_writes . ' WHERE "address" = :address AND ("event_id" < :event_id OR "event_id" = 0)');
            $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
            $sth->bindValue(':value', $serialized_value, \PDO::PARAM_LOB);
            $sth->bindValue(':expiration', $expiration, \PDO::PARAM_INT);
            $sth->bindValue(':created', $created, \PDO::PARAM_INT);
            $sth->bindValue(':event_id', $event_id, \PDO::PARAM_INT);
            $sth->execute();
        }

        return true;
    }

    public function exists(Address $address)
    {
        $sth = $this->dbh->prepare('SELECT COUNT(*) AS existing FROM entries WHERE "address" = :address AND ("expiration" >= :now OR "expiration" IS NULL) AND "value" IS NOT NULL');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->bindValue(':now', $_SERVER['REQUEST_TIME'], \PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchObject();
        return $result->existing > 0;
    }

    public function isNegativeCache(Address $address)
    {
        $sth = $this->dbh->prepare('SELECT COUNT(*) AS entry_count FROM entries WHERE "address" = :address AND ("expiration" >= :now OR "expiration" IS NULL) AND "value" IS NULL');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->bindValue(':now', $_SERVER['REQUEST_TIME'], \PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchObject();
        return ($result->entry_count > 0);
    }

    /**
     * @codeCoverageIgnore
     */
    public function debugDumpState()
    {
        echo PHP_EOL . PHP_EOL . 'Entries:' . PHP_EOL;
        $sth = $this->dbh->prepare('SELECT * FROM "entries" ORDER BY "address"');
        $sth->execute();
        while ($event = $sth->fetchObject()) {
            print_r($event);
        }
        echo PHP_EOL;
    }

    public function getEntry(Address $address)
    {
        $sth = $this->dbh->prepare('SELECT "value", "expiration", "reads", "writes", "created" FROM entries WHERE "address" = :address AND ("expiration" >= :now OR "expiration" IS NULL)');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->bindValue(':now', $_SERVER['REQUEST_TIME'], \PDO::PARAM_INT);
        $sth->execute();
        $entry = $sth->fetchObject();

        // If there are under 10X reads versus writes, bump the read count. We
        // do this to simultaneously track useful overhead data but not unnecessarily
        // record reads after they massively outweigh writes for an address.
        // @TODO: Make this adapt to overhead thresholds.
        if ($entry === false || $entry->reads < 10 * $entry->writes || $entry->reads < 10) {
            $sth = $this->dbh->prepare('UPDATE entries SET "reads" = "reads" + 1 WHERE "address" = :address');
            $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount() === 0) {
                // Use a zero expiration so this row is only used for counts, not negative caching.
                // Use the default event ID of zero to ensure any writes win over this stub.
                $sth = $this->dbh->prepare('INSERT OR IGNORE INTO entries ("address", "expiration", "reads") VALUES (:address, 0, 1)');
                $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
                $sth->execute();
            }
        }

        if ($entry === false) {
            $this->recordMiss();
            return null;
        }

        // Unserialize non-null values.
        if (!is_null($entry->value)) {
            $entry->value = unserialize($entry->value);
        }

        $this->recordHit();
        return $entry;
    }

    public function delete($event_id, Address $address)
    {
        if ($address->isEntireCache() || $address->isEntireBin()) {
            $pattern = $address->serialize() . '%';
            $sth = $this->dbh->prepare('DELETE FROM entries WHERE "address" LIKE :pattern');
            $sth->bindValue('pattern', $pattern, \PDO::PARAM_STR);
            $sth->execute();

            $this->state->clear();
            return true;
        }

        $sth = $this->dbh->prepare('DELETE FROM entries WHERE "address" = :address AND event_id < :event_id');
        $sth->bindValue(':address', $address->serialize(), \PDO::PARAM_STR);
        $sth->bindValue(':event_id', $event_id, \PDO::PARAM_INT);
        $sth->execute();
        return true;
    }
}
