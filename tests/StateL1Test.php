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
        return $state;
    }

    /**
     * @depends testCreation
     */
    public function testEmpty(StateL1Interface $state)
    {
        $this->assertEquals(0, $state->getHits());
        $this->assertEquals(0, $state->getMisses());
        $this->assertNull($state->getLastAppliedEventID());
        return $state;
    }

    /**
     * @depends testEmpty
     */
    public function testHits(StateL1Interface $state)
    {
        $this->assertTrue($state->recordHit());
        $this->assertEquals(1, $state->getHits());
        return $state;
    }

    /**
     * @depends testEmpty
     */
    public function testMisses(StateL1Interface $state)
    {
        $this->assertTrue($state->recordMiss());
        $this->assertEquals(1, $state->getMisses());
        return $state;
    }

    /**
     * @depends testHits
     * @depends testMisses
     */
    public function testClear(StateL1Interface $state)
    {
        $this->assertTrue($state->clear());
        $this->assertEquals(0, $state->getHits());
        $this->assertEquals(0, $state->getMisses());
    }

    /**
     * @depends testEmpty
     */
    public function testSettingEventId(StateL1Interface $state)
    {
        $this->assertTrue($state->setLastAppliedEventID(2));
        return $state;
    }

    /**
     * @depends testSettingEventId
     */
    public function testSettingEqualEventId(StateL1Interface $state)
    {
        $this->assertTrue($state->setLastAppliedEventID(2));
        return $state;
    }

    /**
     * @depends testSettingEqualEventId
     */
    public function testSettingOldEventId(StateL1Interface $state)
    {
        $this->assertFalse($state->setLastAppliedEventID(1));
        return $state;
    }
}
