<?php

namespace LCache;

//use phpunit\framework\TestCase;

class StaticL1Test extends \PHPUnit_Framework_TestCase
{
    use L1TestHelpers;




    public function testStaticL1SetGetDelete()
    {
        $l1 = new StaticL1();
        $this->performSetGetDeleteTest($l1);
    }

    public function testStaticL1Antirollback()
    {
        $l1 = new StaticL1();
        $this->performL1AntirollbackTest($l1);
    }

    public function testStaticL1FullDelete()
    {
        $event_id = 1;
        $cache = new StaticL1();

        $myaddr = new Address('mybin', 'mykey');

        // Set an entry and clear the storage.
        $cache->set($event_id++, $myaddr, 'myvalue');
        $cache->delete($event_id++, new Address());
        $entry = $cache->get($myaddr);
        $this->assertEquals(null, $entry);
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    public function testStaticL1Expiration()
    {
        $event_id = 1;
        $cache = new StaticL1();

        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry.
        $cache->set($event_id++, $myaddr, 'myvalue', -1);
        $this->assertNull($cache->get($myaddr));
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }


    public function testSynchronizationStatic()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, new StaticL1(), new StaticL1());
    }

}
