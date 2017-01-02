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

    /**
     * https://phpunit.de/manual/3.7/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
     *
     * @return array
     */
    public function l1DriverNameProvider()
    {
        return ['apcu', 'static', 'sqlite'];
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
}
