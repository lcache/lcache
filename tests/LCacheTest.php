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
        return new L1CacheFactory();
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

    public function testClearStaticL2()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $l2->delete('mypool', new Address());
        $this->assertNull($l2->get($myaddr));
    }

    public function testStaticL2Expiration()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', -1);
        $this->assertNull($l2->get($myaddr));
    }

    public function testStaticL2Reread()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testNewPoolSynchronization()
    {
        $central = new StaticL2();
        $pool1 = new Integrated($this->l1Factory()->create('static'), $central);

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
        $pool2 = new Integrated($this->l1Factory()->create('static'), $central);
        $applied = $pool2->synchronize();
        $this->assertNull($applied);
        $this->assertEquals($pool1->getLastAppliedEventID(), $pool2->getLastAppliedEventID());
    }

    protected function performTombstoneTest($l1)
    {
        // This test is not for L1 - this tests integratino logick.
        $central = new Integrated($l1, new StaticL2());

        $dne = new Address('mypool', 'mykey-dne');
        $this->assertNull($central->get($dne));

        $tombstone = $central->getEntry($dne, true);
        $this->assertNotNull($tombstone);
        $this->assertNull($tombstone->value);
        // The L1 should return the tombstone entry so the integrated cache
        // can avoid rewriting it.
        $tombstone = $l1->getEntry($dne);
        $this->assertNotNull($tombstone);
        $this->assertNull($tombstone->value);

        // The tombstone should also count as non-existence.
        $this->assertFalse($central->exists($dne));

        // This is a no-op for most L1 implementations, but it should not
        // return false, regardless.
        $this->assertTrue(false !== $l1->collectGarbage());
    }

    public function testStaticL1Tombstone()
    {
        $l1 = $this->l1Factory()->create('static');
        $this->performTombstoneTest($l1);
    }

    public function testAPCuL1Tombstone()
    {
        $l1 = $this->l1Factory()->create('apcu', 'testAPCuL1Tombstone');
        $this->performTombstoneTest($l1);
    }

    public function testSQLiteL1Tombstone()
    {
        $l1 = $this->l1Factory()->create('sqlite');
        $this->performTombstoneTest($l1);
    }

    protected function performSynchronizationTest($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = new Integrated($first_l1, $central);
        $pool2 = new Integrated($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry in Pool 1.
        $pool1->set($myaddr, 'myvalue');
        $this->assertEquals('myvalue', $pool1->get($myaddr));
        $this->assertEquals(1, $pool1->getHitsL1());
        $this->assertEquals(0, $pool1->getHitsL2());
        $this->assertEquals(0, $pool1->getMisses());

        // Read the entry in Pool 2.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $this->assertEquals(0, $pool2->getHitsL1());
        $this->assertEquals(1, $pool2->getHitsL2());
        $this->assertEquals(0, $pool2->getMisses());

        // Initialize Pool 2 synchronization.
        $changes = $pool2->synchronize();
        $this->assertNull($changes);
        $this->assertEquals(1, $second_l1->getLastAppliedEventID());

        // Alter the item in Pool 1. Pool 2 should hit its L1 again
        // with the out-of-date item. Synchronizing should fix it.
        $pool1->set($myaddr, 'myvalue2');
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertEquals('myvalue2', $pool2->get($myaddr));

        // Delete the item in Pool 1. Pool 2 should hit its L1 again
        // with the now-deleted item. Synchronizing should fix it.
        $pool1->delete($myaddr);
        $this->assertEquals('myvalue2', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertNull($pool2->get($myaddr));

        // Try to get an entry that has never existed.
        $myaddr_nonexistent = new Address('mybin', 'mykeynonexistent');
        $this->assertNull($pool1->get($myaddr_nonexistent));

        // Test out bins and clearing.
        $mybin1_mykey = new Address('mybin1', 'mykey');
        $mybin1 = new Address('mybin1');
        $mybin2_mykey = new Address('mybin2', 'mykey');
        $pool1->set($mybin1_mykey, 'myvalue1');
        $pool1->set($mybin2_mykey, 'myvalue2');
        $pool2->synchronize();
        $pool1->delete($mybin1);

        // The deleted bin should be evident in pool1 but not in pool2.
        $this->assertNull($pool1->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool1->get($mybin2_mykey));
        $this->assertEquals('myvalue1', $pool2->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool2->get($mybin2_mykey));

        // Synchronizing should propagate the bin clearing to pool2.
        $pool2->synchronize();
        $this->assertNull($pool2->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool2->get($mybin2_mykey));
    }

    protected function performClearSynchronizationTest($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = new Integrated($first_l1, $central);
        $pool2 = new Integrated($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Create an item, synchronize, and then do a complete clear.
        $pool1->set($myaddr, 'mynewvalue');
        $this->assertEquals('mynewvalue', $pool1->get($myaddr));
        $pool2->synchronize();
        $this->assertEquals('mynewvalue', $pool2->get($myaddr));
        $pool1->delete(new Address());
        $this->assertNull($pool1->get($myaddr));

        // Pool 2 should lag until it synchronizes.
        $this->assertEquals('mynewvalue', $pool2->get($myaddr));
        $pool2->synchronize();
        $this->assertNull($pool2->get($myaddr));
    }

    protected function performTaggedSynchronizationTest($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = new Integrated($first_l1, $central);
        $pool2 = new Integrated($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Test deleting a tag that doesn't exist yet.
        $pool1->deleteTag('mytag');

        // Set and get an entry in Pool 1.
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals([$myaddr], $central->getAddressesForTag('mytag'));
        $this->assertEquals('myvalue', $pool1->get($myaddr));
        $this->assertEquals(1, $pool1->getHitsL1());
        $this->assertEquals(0, $pool1->getHitsL2());
        $this->assertEquals(0, $pool1->getMisses());

        // Read the entry in Pool 2.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $this->assertEquals(0, $pool2->getHitsL1());
        $this->assertEquals(1, $pool2->getHitsL2());
        $this->assertEquals(0, $pool2->getMisses());


        // Initialize Pool 2 synchronization.
        $pool2->synchronize();

        // Delete the tag. The item should now be missing from Pool 1.
        $pool1->deleteTag('mytag'); // TKTK
        $this->assertNull($central->get($myaddr));
        $this->assertNull($first_l1->get($myaddr));
        $this->assertNull($pool1->get($myaddr));


        // Pool 2 should hit its L1 again with the tag-deleted item.
        // Synchronizing should fix it.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertNull($pool2->get($myaddr));

        // Ensure the addition of a second tag still works for deletion.
        $myaddr2 = new Address('mybin', 'mykey2');
        $pool1->set($myaddr2, 'myvalue', null, ['mytag']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->deleteTag('mytag2');
        $this->assertNull($pool1->get($myaddr2));

        // Ensure updating a second item with a tag doesn't remove it from the
        // first.
        $pool1->delete(new Address());
        $pool1->set($myaddr, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);

        $found_addresses = $central->getAddressesForTag('mytag2');
        // getAddressesForTag() may return additional addresses, but it should
        // always return at least the current tagged address.
        $found = false;
        foreach ($found_addresses as $found_address) {
            if ($found_address->serialize() === $myaddr2->serialize()) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testSynchronizationStatic()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, $this->l1Factory()->create('static'), $this->l1Factory()->create('static'));
    }

    public function testTaggedSynchronizationStatic()
    {
        $central = new StaticL2();
        $this->performTaggedSynchronizationTest($central, $this->l1Factory()->create('static'), $this->l1Factory()->create('static'));
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
            $this->performSynchronizationTest(
                $central,
                $this->l1Factory()->create('apcu', 'testSynchronizationAPCu1'),
                $this->l1Factory()->create('apcu', 'testSynchronizationAPCu2')
            );

            // Because of how APCu only offers full cache clears, we test against a static cache for the other L1.
            $this->performClearSynchronizationTest(
                $central,
                $this->l1Factory()->create('apcu', 'testSynchronizationAPCu1b'),
                $this->l1Factory()->create('static')
            );
            $this->performClearSynchronizationTest(
                $central,
                $this->l1Factory()->create('static'),
                $this->l1Factory()->create('apcu', 'testSynchronizationAPCu1c')
            );
        } else {
            $this->markTestSkipped('The APCu extension is not installed, enabled (for the CLI), or functional.');
        }
    }

    public function testSynchronizationSQLiteL1()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest(
            $central,
            $this->l1Factory()->create('sqlite'),
            $this->l1Factory()->create('sqlite')
        );

        $this->performClearSynchronizationTest(
            $central,
            $this->l1Factory()->create('sqlite'),
            $this->l1Factory()->create('static')
        );
        $this->performClearSynchronizationTest(
            $central,
            $this->l1Factory()->create('static'),
            $this->l1Factory()->create('sqlite')
        );
        $this->performClearSynchronizationTest(
            $central,
            $this->l1Factory()->create('sqlite'),
            $this->l1Factory()->create('sqlite')
        );
    }

    public function testSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performSynchronizationTest(
            $central,
            $this->l1Factory()->create('static', 'testSynchronizationDatabase1'),
            $this->l1Factory()->create('static', 'testSynchronizationDatabase2')
        );
        $this->performClearSynchronizationTest(
            $central,
            $this->l1Factory()->create('static', 'testSynchronizationDatabase1a'),
            $this->l1Factory()->create('static', 'testSynchronizationDatabase2a')
        );
    }

    public function testTaggedSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performTaggedSynchronizationTest(
            $central,
            $this->l1Factory()->create('static', 'testTaggedSynchronizationDatabase1'),
            $this->l1Factory()->create('static', 'testTaggedSynchronizationDatabase2')
        );
    }

    public function testBrokenDatabaseFallback()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = $this->l1Factory()->create('static', 'first');
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
        $this->assertNull($l2->applyEvents($this->l1Factory()->create('static')));

        // Try garbage collection routines.
        $pool->collectGarbage();
        $count = $l2->countGarbage();
        $this->assertNull($count);
    }

    public function testDatabaseL2SyncWithNoWrites()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = $this->l1Factory()->create('static', 'first');
        $pool = new Integrated($l1, $l2);
        $pool->synchronize();
    }

    public function testExistsIntegrated()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $l1 = $this->l1Factory()->create('apcu', 'first');
        $pool = new Integrated($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');
        $pool->set($myaddr, 'myvalue');
        $this->assertTrue($pool->exists($myaddr));
        $pool->delete($myaddr);
        $this->assertFalse($pool->exists($myaddr));
    }

    public function testPoolIntegrated()
    {
        $l2 = new StaticL2();
        $l1 = $this->l1Factory()->create('apcu', 'first');
        $pool = new Integrated($l1, $l2);
        $this->assertEquals('first', $pool->getPool());
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

    public function testAPCuL1IntegratedExpiration()
    {
        $l1 = $this->l1Factory()->create('apcu', 'expiration');
        $this->performIntegratedExpiration($l1);
    }

    public function testStaticL1IntegratedExpiration()
    {
        $l1 = $this->l1Factory()->create('static');
        $this->performIntegratedExpiration($l1);
    }

    public function testSQLiteL1IntegratedExpiration()
    {
        $l1 = $this->l1Factory()->create('sqlite');
        $this->performIntegratedExpiration($l1);
    }

    public function performIntegratedExpiration($l1)
    {

        $pool = new Integrated($l1, new StaticL2());
        $myaddr = new Address('mybin', 'mykey');
        $pool->set($myaddr, 'value', 1);
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $l1->getEntry($myaddr)->expiration);

        // Setting items with past expirations should result in a nothing stored.
        $myaddr2 = new Address('mybin', 'mykey2');
        $l1->set(0, $myaddr2, 'value', $_SERVER['REQUEST_TIME'] - 1);
        $this->assertNull($l1->get($myaddr2));

        // Setting an TTL/expiration more than request time should be treated
        // as an expiration.
        $pool->set($myaddr, 'value', $_SERVER['REQUEST_TIME'] + 1);
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $l1->getEntry($myaddr)->expiration);
    }

    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
