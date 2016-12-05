<?php

namespace LCache;

//use phpunit\framework\TestCase;

class IntegratedTest extends \PHPUnit_Extensions_Database_TestCase
{
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

    public function testPoolIntegrated()
    {
        $l2 = new StaticL2();
        $l1 = new APCuL1('first');
        $pool = new Integrated($l1, $l2);
        $this->assertEquals('first', $pool->getPool());
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
