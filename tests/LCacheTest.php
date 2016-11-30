<?php

namespace LCache;


//use phpunit\framework\TestCase;

class LCacheTest extends \PHPUnit_Extensions_Database_TestCase
{
    use L1TestHelpers;
    use L2TestHelpers;
    use DatabaseTestTrait;

    public function testNewPoolSynchronization()
    {
        $central = new StaticL2();
        $pool1 = new Integrated(new StaticL1(), $central);

        $myaddr = new Address('mybin', 'mykey');

        // Initialize sync for Pool 1.
        $applied = $pool1->synchronize();
        $this->assertNull($applied);
        $current_event_id = $pool1->getLastAppliedEventID();
        $this->assertEquals(0, $current_event_id);

        // Add a new entry to Pool 1. The last applied event should be our
        // change. However, because the event is from the same pool, applied
        // should be zero.
        $pool1->set($myaddr, 'myvalue');
        $applied = $pool1->synchronize();
        $this->assertEquals(0, $applied);
        $this->assertEquals($current_event_id + 1, $pool1->getLastAppliedEventID());

        // Add a new pool. Sync should return NULL applied changes but should
        // bump the last applied event ID.
        $pool2 = new Integrated(new StaticL1(), $central);
        $applied = $pool2->synchronize();
        $this->assertNull($applied);
        $this->assertEquals($pool1->getLastAppliedEventID(), $pool2->getLastAppliedEventID());
    }


    public function testBrokenDatabaseFallback()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = new StaticL1('first');
        $pool = new Integrated($l1, $l2);

        $myaddr = new Address('mybin', 'mykey');

        // Break the schema and try operations.
        $this->dbh->exec('DROP TABLE lcache_tags');
        $this->assertNull($pool->set($myaddr, 'myvalue', null, ['mytag']));
        $this->assertGreaterThanOREqual(1, count($l2->getErrors()));
        $this->assertNull($pool->deleteTag('mytag'));
        $pool->synchronize();

        $myaddr2 = new Address('mybin', 'mykey2');

        $this->dbh->exec('DROP TABLE lcache_events');
        $this->assertNull($pool->synchronize());
        $this->assertNull($pool->get($myaddr2));
        $this->assertNull($pool->exists($myaddr2));
        $this->assertNull($pool->set($myaddr, 'myvalue'));
        $this->assertNull($pool->delete($myaddr));
        $this->assertNull($pool->delete(new Address()));
        $this->assertNull($l2->getAddressesForTag('mytag'));

        // Try applying events to an uninitialized L1.
        $this->assertNull($l2->applyEvents(new StaticL1()));

        // Try garbage collection routines.
        $pool->collectGarbage();
        $count = $l2->countGarbage();
        $this->assertNull($count);
    }


    public function testExistsIntegrated()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $l1 = new APCuL1('first');
        $pool = new Integrated($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');
        $pool->set($myaddr, 'myvalue');
        $this->assertTrue($pool->exists($myaddr));
        $pool->delete($myaddr);
        $this->assertFalse($pool->exists($myaddr));
    }
}
