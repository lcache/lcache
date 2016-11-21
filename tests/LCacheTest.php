<?php

namespace LCache;

//use phpunit\framework\TestCase;

class LCacheTest extends \PHPUnit_Extensions_Database_TestCase
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

    public function testNullL1()
    {
        $event_id = 1;
        $cache = new NullL1();
        $myaddr = new Address('mybin', 'mykey');
        $cache->set($event_id++, $myaddr, 'myvalue');
        $entry = $cache->get($myaddr);
        $this->assertNull($entry);
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());

        // Because this cache stores nothing it should be perpetually
        // up-to-date.
        $this->assertEquals(PHP_INT_MAX, $cache->getLastAppliedEventID());
    }

    protected function performSetGetDeleteTest($l1)
    {
        $event_id = 1;

        $myaddr = new Address('mybin', 'mykey');

        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(0, $l1->getMisses());

        // Try to get an entry from an empty L1.
        $entry = $l1->get($myaddr);
        $this->assertNull($entry);
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());

        // Set and get an entry.
        $l1->set($event_id++, $myaddr, 'myvalue');
        $entry = $l1->get($myaddr);
        $this->assertEquals('myvalue', $entry);
        $this->assertEquals(1, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());

        // Delete the entry and try to get it again.
        $l1->delete($event_id++, $myaddr);
        $entry = $l1->get($myaddr);
        $this->assertNull($entry);
        $this->assertEquals(1, $l1->getHits());
        $this->assertEquals(2, $l1->getMisses());

        // Clear everything and try to read.
        $l1->delete($event_id++, new Address());
        $entry = $l1->get($myaddr);
        $this->assertNull($entry);
        $this->assertEquals(1, $l1->getHits());
        $this->assertEquals(3, $l1->getMisses());

        // This is a no-op for most L1 implementations, but it should not
        // return false, regardless.
        $this->assertTrue(false !== $l1->collectGarbage());

        // Test complex values that need serialization.
        $myarray = [1, 2, 3];
        $l1->set($event_id++, $myaddr, $myarray);
        $entry = $l1->get($myaddr);
        $this->assertEquals($myarray, $entry);

        // Test creation tracking.
        $l1->setWithExpiration($event_id++, $myaddr, 'myvalue', 42);
        $entry = $l1->getEntry($myaddr);
        $this->assertEquals(42, $entry->created);
    }

    public function testStaticL1SetGetDelete()
    {
        $l1 = new StaticL1();
        $this->performSetGetDeleteTest($l1);
    }

    public function testSQLiteL1SetGetDelete()
    {
        $l1 = new SQLiteL1();
        $this->performSetGetDeleteTest($l1);
    }

    public function testStaticL1Antirollback()
    {
        $l1 = new StaticL1();
        $this->performL1AntirollbackTest($l1);
    }

    public function testStaticL1FullDelete()
    {
        $event_id = 1;
        $cache = new StaticL1();

        $myaddr = new Address('mybin', 'mykey');

        // Set an entry and clear the storage.
        $cache->set($event_id++, $myaddr, 'myvalue');
        $cache->delete($event_id++, new Address());
        $entry = $cache->get($myaddr);
        $this->assertEquals(null, $entry);
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    public function testStaticL1Expiration()
    {
        $event_id = 1;
        $cache = new StaticL1();

        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry.
        $cache->set($event_id++, $myaddr, 'myvalue', -1);
        $this->assertNull($cache->get($myaddr));
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
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

    protected function performTombstoneTest($l1)
    {
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

    public function testAPCuL1Tombstone()
    {
        $l1 = new APCuL1('testAPCuL1Tombstone');
        $this->performTombstoneTest($l1);
    }

    public function testSQLiteL1Tombstone()
    {
        $l1 = new SQLiteL1();
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
        $this->performSynchronizationTest($central, new StaticL1(), new StaticL1());
    }

    public function testTaggedSynchronizationStatic()
    {
        $central = new StaticL2();
        $this->performTaggedSynchronizationTest($central, new StaticL1(), new StaticL1());
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

    public function testSynchronizationSQLiteL1()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());

        $this->performClearSynchronizationTest($central, new SQLiteL1(), new StaticL1());
        $this->performClearSynchronizationTest($central, new StaticL1(), new SQLiteL1());
        $this->performClearSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());
    }

    public function testSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1'), new StaticL1('testSynchronizationDatabase2'));
        $this->performClearSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1a'), new StaticL1('testSynchronizationDatabase2a'));
    }

    public function testTaggedSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performTaggedSynchronizationTest($central, new StaticL1('testTaggedSynchronizationDatabase1'), new StaticL1('testTaggedSynchronizationDatabase2'));
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

    public function testDatabaseL2SyncWithNoWrites()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = new StaticL1('first');
        $pool = new Integrated($l1, $l2);
        $pool->synchronize();
    }

    public function testExistsDatabaseL2()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertTrue($l2->exists($myaddr));
        $l2->delete('mypool', $myaddr);
        $this->assertFalse($l2->exists($myaddr));
    }

    public function testEmptyCleanUpDatabaseL2()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
    }

    protected function performExistsTest($l1)
    {
        $myaddr = new Address('mybin', 'mykey');
        $l1->set(1, $myaddr, 'myvalue');
        $this->assertTrue($l1->exists($myaddr));
        $l1->delete(2, $myaddr);
        $this->assertFalse($l1->exists($myaddr));
    }

    public function testExistsAPCuL1()
    {
        $l1 = new APCuL1('first');
        $this->performExistsTest($l1);
    }

    public function testExistsStaticL1()
    {
        $l1 = new StaticL1();
        $this->performExistsTest($l1);
    }

    public function testExistsSQLiteL1()
    {
        $l1 = new SQLiteL1();
        $this->performExistsTest($l1);
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

    public function testDatabaseL2Prefix()
    {
        $this->createSchema('myprefix_');
        $l2 = new DatabaseL2($this->dbh, 'myprefix_');
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testAPCuL1PoolIDs()
    {
        // Test unique ID generation.
        $l1 = new APCuL1();
        $this->assertNotNull($l1->getPool());

        // Test host-based generation.
        $_SERVER['SERVER_ADDR'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $l1 = new APCuL1();
        $this->assertEquals('localhost-80', $l1->getPool());
    }

    protected function performL1AntirollbackTest($l1)
    {
        $myaddr = new Address('mybin', 'mykey');
        $current_event_id = $l1->getLastAppliedEventID();
        if (is_null($current_event_id)) {
            $current_event_id = 1;
        }
        $l1->set($current_event_id++, $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l1->get($myaddr));
        $l1->set($current_event_id - 2, $myaddr, 'myoldvalue');
        $this->assertEquals('myvalue', $l1->get($myaddr));
    }

    public function testAPCuL1Antirollback()
    {
        $l1 = new APCuL1('first');
        $this->performL1AntirollbackTest($l1);
    }

    public function testSQLite1Antirollback()
    {
        $l1 = new SQLiteL1();
        $this->performL1AntirollbackTest($l1);
    }

    protected function performL1HitMissTest($l1)
    {
        $myaddr = new Address('mybin', 'mykey');
        $current_hits = $l1->getHits();
        $current_misses = $l1->getMisses();
        $current_event_id = 1;
        $l1->get($myaddr);
        $this->assertEquals($current_misses + 1, $l1->getMisses());
        $l1->set($current_event_id++, $myaddr, 'myvalue');
        $l1->get($myaddr);
        $this->assertEquals($current_hits + 1, $l1->getHits());
    }

    public function testAPCuL1HitMiss()
    {
        $l1 = new APCuL1('testAPCuL1HitMiss');
        $this->performL1HitMissTest($l1);
    }

    public function testSQLiteL1HitMiss()
    {
        $l1 = new SQLiteL1();
        $this->performL1HitMissTest($l1);
    }

    public function testPoolIntegrated()
    {
        $l2 = new StaticL2();
        $l1 = new APCuL1('first');
        $pool = new Integrated($l1, $l2);
        $this->assertEquals('first', $pool->getPool());
    }

    protected function performFailedUnserializationTest($l2)
    {
        $l1 = new StaticL1();
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
        $l1 = new StaticL1();
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
        $l1 = new StaticL1();
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
        $pool = new Integrated(new StaticL1(), $l2);
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
        $pool = new Integrated(new StaticL1(), $l2);
        $myaddr2 = new Address('mybin', 'mykey2');
        $myaddr3 = new Address('mybin', 'mykey3');
        $pool->collectGarbage();
        $pool->set($myaddr2, 'myvalue', -1);
        $pool->set($myaddr3, 'myvalue', -1);
        $this->assertEquals(2, $l2->countGarbage());
        $pool->collectGarbage(1);
        $this->assertEquals(1, $l2->countGarbage());
    }

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
        $this->performHitSetCounterTest(new StaticL1());
    }

    public function testAPCuL1Counters()
    {
        $this->performHitSetCounterTest(new APCuL1('counters'));
    }

    public function testSQLiteL1Counters()
    {
        $this->performHitSetCounterTest(new SQLiteL1());
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
        $this->performExcessiveOverheadSkippingTest(new StaticL1());
    }

    public function testAPCuL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest(new APCuL1('overhead'));
    }

    public function testSQLiteL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest(new SQLiteL1());
    }

    public function testAPCuL1Expiration()
    {
        $l1 = new APCuL1();
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

    public function testDatabaseL2BatchDeletion()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');

        $mybin = new Address('mybin', null);
        $l2->delete('mypool', $mybin);

        $this->assertNull($l2->get($myaddr));
    }

    public function testSQLiteL1SchemaErrorHandling()
    {
        $pool_name = uniqid('', true) . '-' . mt_rand();
        $l1_a = new SQLiteL1($pool_name);

        // Opening a second instance of the same pool should work.
        $l1_b = new SQLiteL1($pool_name);

        $myaddr = new Address('mybin', 'mykey');

        $l1_a->set(1, $myaddr, 'myvalue');

        // Reading from the second handle should show the value written to the
        // first.
        $this->assertEquals('myvalue', $l1_b->get($myaddr));
    }

    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
