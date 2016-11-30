<?php

namespace LCache;

//use phpunit\framework\TestCase;

class APCuL1Test extends \PHPUnit_Framework_TestCase
{

    use L1TestHelpers;
    use L1RequiredTests;

    protected function setUp() {
        parent::setUp();
        $this->l1 = new APCuL1($this->getName());
    }

    public function testAPCuL1Tombstone()
    {
        $l1 = new APCuL1('testAPCuL1Tombstone');
        $this->performTombstoneTest($l1);
    }



    public function testAPCuL1PoolIDs()
    {
        // Test unique ID generation.
        $l1 = new APCuL1();
        $this->assertNotNull($l1->getPool());

        // Test host-based generation.
        $_SERVER['SERVER_ADDR'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $l1 = new APCuL1();
        $this->assertEquals('localhost-80', $l1->getPool());
    }



    public function testAPCuL1HitMiss()
    {
        $l1 = new APCuL1('testAPCuL1HitMiss');
        $this->performL1HitMissTest($l1);
    }







    public function testAPCuL1Expiration()
    {
        $l1 = new APCuL1();
        $pool = new Integrated($l1, new StaticL2());
        $myaddr = new Address('mybin', 'mykey');
        $pool->set($myaddr, 'value', 1);
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $l1->getEntry($myaddr)->expiration);

        // Setting items with past expirations should result in a nothing stored.
        $myaddr2 = new Address('mybin', 'mykey2');
        $l1->set(0, $myaddr2, 'value', $_SERVER['REQUEST_TIME'] - 1);
        $this->assertNull($l1->get($myaddr2));

        // Setting an TTL/expiration more than request time should be treated
        // as an expiration.
        $pool->set($myaddr, 'value', $_SERVER['REQUEST_TIME'] + 1);
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $l1->getEntry($myaddr)->expiration);
    }
}
