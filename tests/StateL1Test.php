<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of StateL1Test
 *
 * @author ndobromirov
 */
abstract class StateL1Test extends \PHPUnit_Framework_TestCase
{
    abstract protected function driverName();

    protected function getInstance($name = null)
    {
        $driver = $name ? $name : $this->driverName();
        $pool = "pool-" . uniqid('', true) . '-' . mt_rand();
        return (new StateL1Factory())->create($driver, $pool);
    }

    public function testL1StateFactory()
    {
        $staticL1 = $this->getInstance('static');
        $invalidL1 = $this->getInstance('invalid_cache_driver');
        $this->assertEquals(get_class($staticL1), get_class($invalidL1));
    }


    public function testCreation()
    {
        $state = $this->getInstance();
        $this->assertTrue($state instanceof StateL1Interface);
        $this->assertEquals(0, $state->getHits());
        $this->assertEquals(0, $state->getMisses());
        $this->assertNull($state->getLastAppliedEventID());
        return $state;
    }

    public function testHitMissClear()
    {
        $state = $this->getInstance();

        // Hits.
        $this->assertTrue($state->recordHit());
        $this->assertEquals(1, $state->getHits());

        // Miss.
        $this->assertTrue($state->recordMiss());
        $this->assertEquals(1, $state->getMisses());

        // Clear them
        $this->assertTrue($state->clear());
        $this->assertEquals(0, $state->getHits());
        $this->assertEquals(0, $state->getMisses());
    }

    public function testSettingEventId()
    {
        $state = $this->getInstance();

        // No changes when invalid input (smaller).
        $this->assertFalse($state->setLastAppliedEventID(-1));
        $this->assertNull($state->getLastAppliedEventID());

        // Allows init with zero.
        $this->assertTrue($state->setLastAppliedEventID(0));
        $this->assertEquals(0, $state->getLastAppliedEventID());

        // Allows to set newer events.
        $this->assertTrue($state->setLastAppliedEventID(2));
        $this->assertEquals(2, $state->getLastAppliedEventID());

        // Allows setting same events.
        $this->assertTrue($state->setLastAppliedEventID(2));
        $this->assertEquals(2, $state->getLastAppliedEventID());

        // Does not allow for setting older events in.
        $this->assertFalse($state->setLastAppliedEventID(1));
        $this->assertEquals(2, $state->getLastAppliedEventID());
    }
}
