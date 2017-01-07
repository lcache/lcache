<?php

/**
 * @file
 * File containing pure L1 test methods.
 */

namespace LCache;

/**
 * L1CacheTest this is the utility base class to be used for all L1 driver
 * implementations. It contains tests that enforce the L1 interface.
 *
 * Child classes should implement the abstract L1CacheTest::driverName() method.
 *
 * @author ndobromirov
 */
abstract class L1CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Utility that will decide the tested driver's name.
     *
     * This name will then be passed on to the L1 Factory, so a correct instance
     * for testing will be created.
     */
    abstract protected function driverName();

    /**
     * Utility factory method for L1 concretes.
     *
     * @param string $pool
     *   Cache pool to use for data during the tests.
     *
     * @return L1
     *   One of the L1 concrete descendants.
     */
    protected function createL1($pool = null)
    {
        $state = new StateL1Factory();
        return (new L1CacheFactory($state))->create($this->driverName(), $pool);
    }

    /**
     * @group L1
     */
    public function testSetGetDelete()
    {
        $event_id = 1;
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        // Validate emptyness.
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
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());

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

    /**
     * @group L1
     */
    public function testPreventRollback()
    {
        $l1 = $this->createL1();

        $myaddr = new Address('mybin', 'mykey');
        $current_event_id = $l1->getLastAppliedEventID();
        if (is_null($current_event_id)) {
            $current_event_id = 1;
        }
        // Write something in the cache.
        $l1->set($current_event_id++, $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l1->get($myaddr));

        // Atempt to write somthing with older event id.
        $l1->set($current_event_id - 2, $myaddr, 'myoldvalue');
        $this->assertEquals('myvalue', $l1->get($myaddr));
    }

    /**
     * @group L1
     */
    public function testFullDelete()
    {
        $event_id = 1;
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        // Set an entry and clear the storage.
        $l1->set($event_id++, $myaddr, 'myvalue');
        $l1->delete($event_id++, new Address());
        $this->assertEquals(null, $l1->get($myaddr));
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());
    }

    /**
     * @group L1
     */
    public function testExpiration()
    {
        $event_id = 1;
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry.
        $l1->set($event_id++, $myaddr, 'myvalue', -1);
        $this->assertNull($l1->get($myaddr));
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());
    }

    /**
     * @group L1
     */
    public function testExists()
    {
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        $l1->set(1, $myaddr, 'myvalue');
        $this->assertTrue($l1->exists($myaddr));
        $l1->delete(2, $myaddr);
        $this->assertFalse($l1->exists($myaddr));
    }

    /**
     * @group L1
     */
    public function testPoolIDs()
    {
        // Test unique ID generation.
        $this->assertNotNull($this->createL1()->getPool());

        // Test host-based generation.
        $_SERVER['SERVER_ADDR'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost-80', $this->createL1()->getPool());
    }

    /**
     * @group L1
     */
    public function testPoolSharing()
    {
        $value = 'myvalue';
        $myaddr = new Address('mybin', 'mykey');
        $poolName = uniqid('', true) . '-' . mt_rand();

        // Initialize a value in cache.
        $this->createL1($poolName)->set(1, $myaddr, $value);

        // Opening a second instance of the same pool should work.
        // Reading from the second handle should show the same value.
        $this->assertEquals($value, $this->createL1($poolName)->get($myaddr));
    }

    /**
     * @group L1
     */
    public function testHitMiss()
    {
        $event_id = 1;
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');
        list($hits, $misses) = [$l1->getHits(), $l1->getMisses()];

        $l1->get($myaddr);
        $this->assertEquals($misses + 1, $l1->getMisses());

        $l1->set($event_id++, $myaddr, 'myvalue');
        $l1->get($myaddr);
        $this->assertEquals($hits + 1, $l1->getHits());
    }

    /**
     * @group L1
     */
    public function testStateStorage()
    {
        $event_id = 1;
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        $this->assertEquals(0, $l1->getKeyOverhead($myaddr));
        $l1->set($event_id++, $myaddr, 'myvalue');
        $this->assertEquals(1, $l1->getKeyOverhead($myaddr));
        $l1->get($myaddr);
        $this->assertEquals(0, $l1->getKeyOverhead($myaddr));
        $l1->set($event_id++, $myaddr, 'myvalue2');
        $this->assertEquals(1, $l1->getKeyOverhead($myaddr));

        // An unknown get should create negative overhead, generally
        // in anticipation of a set.
        $myaddr2 = new Address('mybin', 'mykey2');
        $l1->get($myaddr2);
        $this->assertEquals(-1, $l1->getKeyOverhead($myaddr2));
    }

    /**
     * @group L1
     */
    public function testNegativeCache()
    {
        $delta = 10;
        $l1 = $this->createL1();
        $now = $_SERVER['REQUEST_TIME'];
        $myaddr = new Address('mybin', 'mykey');

        $this->assertTrue($l1->set(1, $myaddr, null, $now - $delta));
        $this->assertFalse($l1->isNegativeCache($myaddr));
    }
}
