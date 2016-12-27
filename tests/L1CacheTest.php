<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of L1CacheTest
 *
 * @author ndobromirov
 */
abstract class L1CacheTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function driverName();

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
}
