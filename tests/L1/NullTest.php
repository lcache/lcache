<?php

/**
 * @file
 * Test file for the Null L1 driver in LCache library.
 */

namespace LCache\L1;

use LCache\Address;

/**
 * NullTest concrete implementation.
 *
 * @author ndobromirov
 */
class NullTest extends \LCache\L1CacheTest
{

    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'null';
    }

    public function testHitMiss()
    {
        $cache = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        $cache->set(1, $myaddr, 'myvalue');

        $this->assertNull($cache->get($myaddr));
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    public function testStateStorage()
    {
        $lastEventId = $this->createL1()->getLastAppliedEventID();
        $this->assertEquals(PHP_INT_MAX, $lastEventId);
    }

    public function testSetGetDelete()
    {
        // Not relevant for NullL1 class.
    }

    public function testPreventRollback()
    {
        // Not relevant for NullL1 class.
    }

    public function testExists()
    {
        // Not relevant for NullL1 class.
    }

    public function testPoolSharing()
    {
        // Not relevant for NullL1 class.
    }

    public function testNegativeCache()
    {
        // Not relevant for NullL1 class.
    }
}
