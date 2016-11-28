<?php

namespace LCache;

//use phpunit\framework\TestCase;

trait L1TestHelpers {

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

    protected function performExistsTest($l1)
    {
        $myaddr = new Address('mybin', 'mykey');
        $l1->set(1, $myaddr, 'myvalue');
        $this->assertTrue($l1->exists($myaddr));
        $l1->delete(2, $myaddr);
        $this->assertFalse($l1->exists($myaddr));
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

    public function testSQLiteL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest(new SQLiteL1());
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
}