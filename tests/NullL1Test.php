<?php

namespace LCache;

//use phpunit\framework\TestCase;

class NullL1Test extends \PHPUnit_Framework_TestCase {

    public function testNullL1() {
        $event_id = 1;
        $cache = new NullL1();
        $myaddr = new Address('mybin', 'mykey');
        $cache->set($event_id++, $myaddr, 'myvalue');
        $entry = $cache->get($myaddr);
        $this->assertNull($entry);
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());

        // Because this cache stores nothing it should be perpetually
        // up-to-date.
        $this->assertEquals(PHP_INT_MAX, $cache->getLastAppliedEventID());
    }

}