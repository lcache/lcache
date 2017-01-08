<?php

/**
 * @file
 * DB init utility trait.
 */

namespace LCache\Utils;

/**
 * Moved everything that is DB init specifi to a trait, so it can be reused in
 * different test classes.
 *
 * Currently used in \LCache\L2\DatabaseTest only.
 *
 * @author ndobromirov
 */
trait LCacheDBTestTrait
{
    use \PHPUnit_Extensions_Database_TestCase_Trait {
        \PHPUnit_Extensions_Database_TestCase_Trait::setUp as phpUnitDbTraitSetUp;
    }

    /** @var \PDO */
    protected $dbh;

    /** @var bool Flag to prefent multiple schema creation during a test. */
    private $tablesCreated;

    /** @var string Optional tables prefix to be used for the DB table names. */
    protected $dbPrefix;

    protected $dbErrorsLog = false;

    /**
     * Needed by PHPUnit_Extensions_Database_TestCase_Trait.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $connection = $this->createDefaultDBConnection($this->dbh, ':memory:');
        return $connection;
    }

    /**
     * Needed by PHPUnit_Extensions_Database_TestCase_Trait.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }

    /**
     * Utility executed before every test.
     */
    protected function setUp()
    {
        $this->phpUnitDbTraitSetUp();
        $this->tablesCreated = false;
        $this->dbPrefix = '';
        $this->dbErrorsLog = false;
    }

    /**
     * Utility to create the chema on a given connection.
     *
     * @param string $prefix
     */
    protected function createSchema($prefix = '')
    {
        if ($this->tablesCreated) {
            return;
        }
        $this->dbh->exec('PRAGMA foreign_keys = ON');

        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_events ('
            . '"event_id" INTEGER PRIMARY KEY AUTOINCREMENT, '
            . '"pool" TEXT NOT NULL, '
            . '"address" TEXT, '
            . '"value" BLOB, '
            . '"expiration" INTEGER, '
            . '"created" INTEGER NOT NULL)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'latest_entry ON ' . $prefix . 'lcache_events ("address", "event_id")');

        // @TODO: Set a proper primary key and foreign key relationship.
        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_tags ('
            . '"tag" TEXT, '
            . '"event_id" INTEGER, '
            . 'PRIMARY KEY ("tag", "event_id"), '
            . 'FOREIGN KEY("event_id") REFERENCES ' . $prefix . 'lcache_events("event_id") ON DELETE CASCADE)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'rewritten_entry ON ' . $prefix . 'lcache_tags ("event_id")');

        $this->tablesCreated = true;
    }
}
