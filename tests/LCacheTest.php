<?php

namespace LCache;

//use phpunit\framework\TestCase;

class LCacheTest extends \PHPUnit_Extensions_Database_TestCase
{
    protected $dbh = null;

    /**
     * @return \LCache\L1CacheFactory
     */
    protected function l1Factory()
    {
        return new L1CacheFactory(new StateL1Factory());
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($this->dbh, ':memory:');
    }

    protected function setUp()
    {
        parent::setUp();
        StaticL2::resetStorageState();
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

    public function testL1Factory()
    {
        $staticL1 = $this->l1Factory()->create('static');
        $invalidL1 = $this->l1Factory()->create('invalid_cache_driver');
        $this->assertEquals(get_class($staticL1), get_class($invalidL1));
    }

    protected function performFailedUnserializationTest($l2)
    {
        $l1 = $this->l1Factory()->create('static');
        $pool = new Integrated($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');

        $invalid_object = 'O:10:"HelloWorl":0:{}';

        // Set the L1's high water mark.
        $pool->set($myaddr, 'valid');
        $changes = $pool->synchronize();
        $this->assertNull($changes);  // Just initialized event high water mark.
        $this->assertEquals(1, $l1->getLastAppliedEventID());

        // Put an invalid object into the L2 and synchronize again.
        $l2->set('anotherpool', $myaddr, $invalid_object, null, [], true);
        $changes = $pool->synchronize();
        $this->assertEquals(1, $changes);
        $this->assertEquals(2, $l1->getLastAppliedEventID());

        // The sync should delete the item from the L1, causing it to miss.
        $this->assertNull($l1->get($myaddr));
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());
    }

    protected function performCaughtUnserializationOnGetTest($l2)
    {
        $l1 = $this->l1Factory()->create('static');
        $pool = new Integrated($l1, $l2);
        $invalid_object = 'O:10:"HelloWorl":0:{}';
        $myaddr = new Address('mybin', 'performCaughtUnserializationOnGetTest');
        $l2->set('anypool', $myaddr, $invalid_object, null, [], true);
        try {
            $pool->get($myaddr);
            $this->assertTrue(false);  // Should not reach here.
        } catch (UnserializationException $e) {
            $this->assertEquals($invalid_object, $e->getSerializedData());

            // The text of the exception should include the class name, bin, and key.
            $this->assertRegExp('/^' . preg_quote('LCache\UnserializationException: Cache') . '/', strval($e));
            $this->assertRegExp('/bin "' . preg_quote($myaddr->getBin()) . '"/', strval($e));
            $this->assertRegExp('/key "' . preg_quote($myaddr->getKey()) . '"/', strval($e));
        }
    }

    public function testDatabaseL2FailedUnserialization()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performFailedUnserializationTest($l2);
        $this->performCaughtUnserializationOnGetTest($l2);
    }

    public function testStaticL2FailedUnserialization()
    {
        $l2 = new StaticL2();
        $this->performFailedUnserializationTest($l2);
        $this->performCaughtUnserializationOnGetTest($l2);
    }

    // Callers should expect an UnserializationException.
    protected function performFailedUnserializationOnGetTest($l2)
    {
        $l1 = $this->l1Factory()->create('static');
        $pool = new Integrated($l1, $l2);
        $invalid_object = 'O:10:"HelloWorl":0:{}';
        $myaddr = new Address('mybin', 'performFailedUnserializationOnGetTest');
        $l2->set('anypool', $myaddr, $invalid_object, null, [], true);
        $pool->get($myaddr);
    }

    /**
     * @expectedException LCache\UnserializationException
     */
    public function testDatabaseL2FailedUnserializationOnGet()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performFailedUnserializationOnGetTest($l2);
    }

    /**
     * @expectedException LCache\UnserializationException
     */
    public function testStaticL2FailedUnserializationOnGet()
    {
        $l2 = new StaticL2();
        $this->performFailedUnserializationOnGetTest($l2);
    }

    public function performGarbageCollectionTest($l2)
    {
        $pool = new Integrated($this->l1Factory()->create('static'), $l2);
        $myaddr = new Address('mybin', 'mykey');
        $this->assertEquals(0, $l2->countGarbage());
        $pool->set($myaddr, 'myvalue', -1);
        $this->assertEquals(1, $l2->countGarbage());
        $pool->collectGarbage();
        $this->assertEquals(0, $l2->countGarbage());
    }

    public function testDatabaseL2GarbageCollection()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performGarbageCollectionTest($l2);
    }

    public function testStaticL2GarbageCollection()
    {
        $l2 = new StaticL2();
        $this->performGarbageCollectionTest($l2);

        // Test item limits.
        $pool = new Integrated($this->l1Factory()->create('static'), $l2);
        $myaddr2 = new Address('mybin', 'mykey2');
        $myaddr3 = new Address('mybin', 'mykey3');
        $pool->collectGarbage();
        $pool->set($myaddr2, 'myvalue', -1);
        $pool->set($myaddr3, 'myvalue', -1);
        $this->assertEquals(2, $l2->countGarbage());
        $pool->collectGarbage(1);
        $this->assertEquals(1, $l2->countGarbage());
    }

    /**
     * @todo Is this still needed, or it can be deleted.
     *   Same tests are implemented against all L1 drivers directly in
     *   L1CacheTest::testStateStorage().
     */
    protected function performHitSetCounterTest($l1)
    {
        $pool = new Integrated($l1, new StaticL2());
        $myaddr = new Address('mybin', 'mykey');

        $this->assertEquals(0, $l1->getKeyOverhead($myaddr));
        $pool->set($myaddr, 'myvalue');
        $this->assertEquals(1, $l1->getKeyOverhead($myaddr));
        $pool->get($myaddr);
        $this->assertEquals(0, $l1->getKeyOverhead($myaddr));
        $pool->set($myaddr, 'myvalue2');
        $this->assertEquals(1, $l1->getKeyOverhead($myaddr));

        // An unknown get should create negative overhead, generally
        // in anticipation of a set.
        $myaddr2 = new Address('mybin', 'mykey2');
        $pool->get($myaddr2);
        $this->assertEquals(-1, $l1->getKeyOverhead($myaddr2));
    }

    public function testStaticL1Counters()
    {
        $this->performHitSetCounterTest($this->l1Factory()->create('static'));
    }

    public function testAPCuL1Counters()
    {
        $this->performHitSetCounterTest($this->l1Factory()->create('apcu', 'counters'));
    }

    public function testSQLiteL1Counters()
    {
        $this->performHitSetCounterTest($this->l1Factory()->create('sqlite'));
    }

    protected function performExcessiveOverheadSkippingTest($l1)
    {
        $pool = new Integrated($l1, new StaticL2(), 2);
        $myaddr = new Address('mybin', 'mykey');

        // These should go through entirely.
        $this->assertNotNull($pool->set($myaddr, 'myvalue1'));
        $this->assertNotNull($pool->set($myaddr, 'myvalue2'));

        // This should return an event_id but delete the item.
        $this->assertEquals(2, $l1->getKeyOverhead($myaddr));
        $this->assertFalse($l1->isNegativeCache($myaddr));
        $this->assertNotNull($pool->set($myaddr, 'myvalue3'));
        $this->assertFalse($pool->exists($myaddr));

        // A few more sets to offset the existence check, which some L1s may
        // treat as a hit. This should put us firmly in excessive territory.
        $pool->set($myaddr, 'myvalue4');
        $pool->set($myaddr, 'myvalue5');
        $pool->set($myaddr, 'myvalue6');

        // Now, with the local negative cache, these shouldn't even return
        // an event_id.
        $this->assertNull($pool->set($myaddr, 'myvalueA1'));
        $this->assertNull($pool->set($myaddr, 'myvalueA2'));

        // Test a lot of sets but with enough hits to drop below the threshold.
        $myaddr2 = new Address('mybin', 'mykey2');
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertEquals('myvalue', $pool->get($myaddr2));
        $this->assertEquals('myvalue', $pool->get($myaddr2));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
    }

    public function testStaticL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest($this->l1Factory()->create('static'));
    }

    public function testAPCuL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest($this->l1Factory()->create('apcu', 'overhead'));
    }

    public function testSQLiteL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest($this->l1Factory()->create('sqlite'));
    }

    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
