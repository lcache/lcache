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

    /**
     * @group L1
     */
    public function testHitMiss()
    {
        $cache = $this->createL1();
        $myaddr = new Address('mybin', 'mykey');

        $cache->set(1, $myaddr, 'myvalue');

        $this->assertNull($cache->get($myaddr));
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    /**
     * @group L1
     */
    public function testStateStorage()
    {
        $lastEventId = $this->createL1()->getLastAppliedEventID();
        $this->assertEquals(PHP_INT_MAX, $lastEventId);
    }

    /**
     * @group L1
     */
    public function testSetGetDelete()
    {
        // Not relevant for NullL1 class.
    }

    /**
     * @group L1
     */
    public function testPreventRollback()
    {
        // Not relevant for NullL1 class.
    }

    /**
     * @group L1
     */
    public function testExists()
    {
        // Not relevant for NullL1 class.
    }

    /**
     * @group L1
     */
    public function testPoolSharing()
    {
        // Not relevant for NullL1 class.
    }

    /**
     * @group L1
     */
    public function testNegativeCache()
    {
        // Not relevant for NullL1 class.
    }
}
