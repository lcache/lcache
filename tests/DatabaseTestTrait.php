<?php

namespace LCache;

//use phpunit\framework\TestCase;

trait DatabaseTestTrait
{

    protected $dbh = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($this->dbh, ':memory:');
    }

    protected function createSchema($prefix = '')
    {
        $this->dbh->exec('PRAGMA foreign_keys = ON');

        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_events("event_id" INTEGER PRIMARY KEY AUTOINCREMENT, "pool" TEXT NOT NULL, "address" TEXT, "value" BLOB, "expiration" INTEGER, "created" INTEGER NOT NULL)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'latest_entry ON ' . $prefix . 'lcache_events ("address", "event_id")');

        // @TODO: Set a proper primary key and foreign key relationship.
        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_tags("tag" TEXT, "event_id" INTEGER, PRIMARY KEY ("tag", "event_id"), FOREIGN KEY("event_id") REFERENCES ' . $prefix . 'lcache_events("event_id") ON DELETE CASCADE)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'rewritten_entry ON ' . $prefix . 'lcache_tags ("event_id")');
    }


    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
