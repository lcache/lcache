<?php

namespace LCache;


//use phpunit\framework\TestCase;

class LCacheTest extends



















  \PHPUnit_Extensions_Database_TestCase
{
    use L1TestHelpers;
    use L2TestHelpers;

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




    public function testNewPoolSynchronization()
    {
        $central = new StaticL2();
        $pool1 = new Integrated(new StaticL1(), $central);

        $myaddr = new Address('mybin', 'mykey');

        // Initialize sync for Pool 1.
        $applied = $pool1->synchronize();
        $this->assertNull($applied);
        $current_event_id = $pool1->getLastAppliedEventID();
        $this->assertEquals(0, $current_event_id);

        // Add a new entry to Pool 1. The last applied event should be our
        // change. However, because the event is from the same pool, applied
        // should be zero.
        $pool1->set($myaddr, 'myvalue');
        $applied = $pool1->synchronize();
        $this->assertEquals(0, $applied);
        $this->assertEquals($current_event_id + 1, $pool1->getLastAppliedEventID());

        // Add a new pool. Sync should return NULL applied changes but should
        // bump the last applied event ID.
        $pool2 = new Integrated(new StaticL1(), $central);
        $applied = $pool2->synchronize();
        $this->assertNull($applied);
        $this->assertEquals($pool1->getLastAppliedEventID(), $pool2->getLastAppliedEventID());
    }



    public function testSynchronizationAPCu()
    {
        // Warning: As long as LCache\APCuL1 flushes all of APCu on a wildcard
        // deletion, it is not possible to test such functionality in a
        // single process.

        $run_test = false;
        if (function_exists('apcu_store')) {
            apcu_store('test_key', 'test_value');
            $value = apcu_fetch('test_key');
            if ($value === 'test_value') {
                $run_test = true;
            }
        }

        if ($run_test) {
            $central = new StaticL2();
            $this->performSynchronizationTest($central, new APCuL1('testSynchronizationAPCu1'), new APCuL1('testSynchronizationAPCu2'));

            // Because of how APCu only offers full cache clears, we test against a static cache for the other L1.
            $this->performClearSynchronizationTest($central, new APCuL1('testSynchronizationAPCu1b'), new StaticL1());
            $this->performClearSynchronizationTest($central, new StaticL1(), new APCuL1('testSynchronizationAPCu1c'));
        } else {
            $this->markTestSkipped('The APCu extension is not installed, enabled (for the CLI), or functional.');
        }
    }

    public function testBrokenDatabaseFallback()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = new StaticL1('first');
        $pool = new Integrated($l1, $l2);

        $myaddr = new Address('mybin', 'mykey');

        // Break the schema and try operations.
        $this->dbh->exec('DROP TABLE lcache_tags');
        $this->assertNull($pool->set($myaddr, 'myvalue', null, ['mytag']));
        $this->assertGreaterThanOREqual(1, count($l2->getErrors()));
        $this->assertNull($pool->deleteTag('mytag'));
        $pool->synchronize();

        $myaddr2 = new Address('mybin', 'mykey2');

        $this->dbh->exec('DROP TABLE lcache_events');
        $this->assertNull($pool->synchronize());
        $this->assertNull($pool->get($myaddr2));
        $this->assertNull($pool->exists($myaddr2));
        $this->assertNull($pool->set($myaddr, 'myvalue'));
        $this->assertNull($pool->delete($myaddr));
        $this->assertNull($pool->delete(new Address()));
        $this->assertNull($l2->getAddressesForTag('mytag'));

        // Try applying events to an uninitialized L1.
        $this->assertNull($l2->applyEvents(new StaticL1()));

        // Try garbage collection routines.
        $pool->collectGarbage();
        $count = $l2->countGarbage();
        $this->assertNull($count);
    }


    public function testExistsIntegrated()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $l1 = new APCuL1('first');
        $pool = new Integrated($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');
        $pool->set($myaddr, 'myvalue');
        $this->assertTrue($pool->exists($myaddr));
        $pool->delete($myaddr);
        $this->assertFalse($pool->exists($myaddr));
    }


    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
