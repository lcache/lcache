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
    protected function createL1($pool = null) {
        return (new L1CacheFactory())->create($this->driverName(), $pool);
    }

    public function testSetGetDelete()
    {
        $l1 = $this->createL1();

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

    public function testExists()
    {
        $l1 = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        $l1->set(1, $myaddr, 'myvalue');
        $this->assertTrue($l1->exists($myaddr));
        $l1->delete(2, $myaddr);
        $this->assertFalse($l1->exists($myaddr));
    }

    public function testPoolIDs()
    {
        // Test unique ID generation.
        $this->assertNotNull($this->createL1()->getPool());

        // Test host-based generation.
        $_SERVER['SERVER_ADDR'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $this->assertEquals('localhost-80', $this->createL1()->getPool());
    }
}
