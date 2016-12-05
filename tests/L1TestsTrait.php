<?php

namespace LCache;

// These are the tests that every functioning L1 should implement.
// NullL1 does not use these tests because the point of NullL1 is to not
// actually cache.

trait L1TestsTrait
{
    public function testAntirollback()
    {
        $this->performL1AntirollbackTest($this->l1);
    }

    public function testExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest($this->l1);
    }

    public function testExists()
    {
        $this->performExistsTest($this->l1);
    }

    public function testCounters()
    {
        $this->performHitSetCounterTest($this->l1);
    }


    public function testTombstone()
    {
        $this->performTombstoneTest($this->l1);
    }


    public function testHitMiss()
    {
        $this->performL1HitMissTest($this->l1);
    }

    public function testSynchronization()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, $this->l1, $this->l1_beta);
    }

  // This method was originally used only on StaticL1.
    public function testL1FullDelete()
    {
        $event_id = 1;
        $cache = $this->l1;

        $myaddr = new Address('mybin', 'mykey');

        // Set an entry and clear the storage.
        $cache->set($event_id++, $myaddr, 'myvalue');
        $cache->delete($event_id++, new Address());
        $entry = $cache->get($myaddr);
        $this->assertEquals(null, $entry);
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    public function testL1Expiration()
    {
        $event_id = 1;
        $cache = $this->l1;

        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry.
        $cache->set($event_id++, $myaddr, 'myvalue', -1);
        $this->assertNull($cache->get($myaddr));
        $this->assertEquals(0, $cache->getHits());
        $this->assertEquals(1, $cache->getMisses());
    }

    public function testL1IntegratedExpiration()
    {
        $l1 = new SQLiteL1();
        $this->performIntegratedExpiration($l1);
    }

    public function testStaticL1SetGetDelete()
    {
        $this->performSetGetDeleteTest($this->l1);
    }
}
