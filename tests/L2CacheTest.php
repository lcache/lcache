<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of L2CacheTest
 *
 * @author ndobromirov
 */
abstract class L2CacheTest extends \PHPUnit_Framework_TestCase
{

    abstract protected function l2FactoryOptions();

    /**
     * @return L2
     */
    protected function createL2()
    {
        list ($name, $options) = $this->l2FactoryOptions();
        $factory = new L2CacheFactory([$name => $options]);
        $l2 = $factory->create($name);
        return $l2;
    }

    protected function suportedL1Drivers()
    {
        return ['apcu', 'static', 'sqlite'];
    }

    /**
     * https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
     *
     * @return array
     */
    public function l1DriverNameProvider()
    {
        return array_map(function ($name) {
            return [$name];
        }, $this->suportedL1Drivers());
    }

    /**
     *
     * @param string $driverName
     * @param string $customPool
     * @return L1
     */
    public function createL1($driverName, $customPool = null)
    {
        return (new L1CacheFactory())->create($driverName, $customPool);
    }

    public function testExists()
    {
        $l2 = $this->createL2();
        $myaddr = new Address('mybin', 'mykey');

        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertTrue($l2->exists($myaddr));
        $l2->delete('mypool', $myaddr);
        $this->assertFalse($l2->exists($myaddr));
    }

    public function testEmptyCleanUp()
    {
        $l2 = $this->createL2();
    }

    public function testBatchDeletion()
    {
        $l2 = $this->createL2();

        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');

        $mybin = new Address('mybin', null);
        $l2->delete('mypool', $mybin);

        $this->assertNull($l2->get($myaddr));
    }

    public function testL2Factory()
    {
        $factory = new L2CacheFactory();
        $staticL1 = $factory->create('static');
        $invalidL1 = $factory->create('invalid_cache_driver');
        $this->assertEquals(get_class($staticL1), get_class($invalidL1));
    }


    public function testCleanupAfterWrite()
    {
        $myaddr = new Address('mybin', 'mykey');

        // Write to the key with the first client.
        $l2_client_a = $this->createL2();
        $event_id_a = $l2_client_a->set('mypool', $myaddr, 'myvalue');

        // Verify that the first event exists and has the right value.
        $event = $l2_client_a->getEvent($event_id_a);
        $this->assertEquals('myvalue', $event->value);

        // Use a second client. This gives us a fresh event_id_low_water,
        // just like a new PHP request.
        $l2_client_b = $this->createL2();

        // Write to the same key with the second client.
        $event_id_b = $l2_client_b->set('mypool', $myaddr, 'myvalue2');

        // Verify that the second event exists and has the right value.
        $event = $l2_client_b->getEvent($event_id_b);
        $this->assertEquals('myvalue2', $event->value);

        // Call the same method as on destruction. This second client should
        // now prune any writes to the key from earlier requests.
        $l2_client_b->pruneReplacedEvents();

        // Verify that the first event no longer exists.
        $event = $l2_client_b->getEvent($event_id_a);
        $this->assertNull($event);
    }

    /**
     * @dataProvider l1DriverNameProvider
     */
    public function testApplyEvents($driverName)
    {
        $l1_1 = $this->createL1($driverName);
        $l1_2 = $this->createL1($driverName);
        $l2 = $this->createL2();

        // Empty L1 & L2.
        $this->assertNull($l1_1->getLastAppliedEventID());
        $this->assertNull($l1_2->getLastAppliedEventID());
        $this->assertNull($l2->applyEvents($l1_1));
        $this->assertNull($l2->applyEvents($l1_2));
        $this->assertEquals(0, $l1_1->getLastAppliedEventID());
        $this->assertEquals(0, $l1_2->getLastAppliedEventID());

        // Two writes to L2, one from each L1.
        $this->assertEquals(1, $l2->set($l1_1->getPool(), new Address('bin', 'key1'), 'test'));
        $this->assertEquals(2, $l2->set($l1_2->getPool(), new Address('bin', 'key2'), 'test'));

        // Validate state transfer.
        $this->assertEquals(1, $l2->applyEvents($l1_1));
        $this->assertEquals(1, $l2->applyEvents($l1_2));
        $this->assertEquals(2, $l1_1->getLastAppliedEventID());
        $this->assertEquals(2, $l1_2->getLastAppliedEventID());
    }
}
