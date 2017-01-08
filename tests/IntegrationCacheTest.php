<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache;

/**
 * Description of IntegrationCacheTest
 *
 * @author ndobromirov
 */
abstract class IntegrationCacheTest extends \PHPUnit_Framework_TestCase
{
    use \LCache\Utils\LCacheDBTestTrait {
        \LCache\Utils\LCacheDBTestTrait::setUp as dbTraitSetUp;
    }

    protected function setUp()
    {
        $this->dbTraitSetUp();
        \LCache\StaticL2::resetStorageState();
        $this->createSchema();
    }

    public function supportedL1Drivers()
    {
        return ['static', 'apcu', 'sqlite'];
    }

    public function supportedL2Drivers($name = null)
    {
        $data = [
            'static' => [],
            'database' => ['handle' => $this->dbh],
        ];
        return $name ? $data[$name] : $data;
    }

    public function createStateL1Factory()
    {
        return new StateL1Factory();
    }

    public function createL1Factory($state)
    {
        return new L1CacheFactory($state);
    }

    /**
     *
     * @param string $name
     * @param string $pool
     * @return L1
     */
    public function createL1($name, $pool = null)
    {
        $state = $this->createStateL1Factory();
        $factory = $this->createL1Factory($state);
        $l1 = $factory->create($name, $pool);
        return $l1;
    }

    /**
     * @return L2
     */
    protected function createL2($name)
    {
        $options = $this->supportedL2Drivers($name);
        $factory = new L2CacheFactory([$name => $options]);
        $l2 = $factory->create($name);
        return $l2;
    }

    abstract protected function getDriverInstance(L1 $l1, L2 $l2, $threshold = null);

    /**
     *
     * @param type $l1Name
     * @param type $l2Name
     * @param type $threshold
     * @return Integrated
     */
    public function createPool($l1Name, $l2Name, $threshold = null)
    {
        $l1 = $this->createL1($l1Name);
        $l2 = $this->createL2($l2Name);
        $pool = $this->getDriverInstance($l1, $l2, $threshold);
        return $pool;
    }

    public function layersProvider()
    {
        $allL1 = $this->supportedL1Drivers();
        $allL2 = array_keys($this->supportedL2Drivers());

        $results = [];
        foreach ($allL1 as $l1) {
            foreach ($allL2 as $l2) {
                $results["Integrating L1:$l1 and L2:$l2"] = [$l1, $l2];
            }
        }

        return $results;
    }

    public function twoPoolsProvider()
    {
        $allL1 = $this->supportedL1Drivers();
        $allL2 = array_keys($this->supportedL2Drivers());

        $results = [];
        foreach ($allL1 as $l11) {
            foreach ($allL1 as $l12) {
                foreach ($allL2 as $l2) {
                    $name = "Pool-1 L1:$l11-L2:$l2 and Pool-2 L1:$l12-L2:$l2";
                    $results[$name] = [$l2, $l11, $l12];
                }
            }
        }

        return $results;
    }

    /**
     * @group integration
     * @dataProvider layersProvider
     */
    public function testNewPoolSynchronization($l1Name, $l2Name)
    {
        $myaddr = new Address('mybin', 'mykey');

        // Initialize sync for Pool 1.
        $pool1 = $this->createPool($l1Name, $l2Name);
        $this->assertNull($pool1->synchronize());
        $current_event_id = $pool1->getLastAppliedEventID();
        $this->assertEquals(0, $current_event_id);

        // Add a new entry to Pool 1. The last applied event should be our
        // change. However, because the event is from the same pool, applied
        // should be zero.
        $pool1->set($myaddr, 'myvalue');
        $this->assertEquals(0, $pool1->synchronize());
        $this->assertEquals($current_event_id + 1, $pool1->getLastAppliedEventID());

        // Add a new pool. Sync should return NULL applied changes but should
        // bump the last applied event ID.
        $pool2 = $this->createPool($l1Name, $l2Name);
        $this->assertNull($pool2->synchronize());
        $this->assertEquals($pool1->getLastAppliedEventID(), $pool2->getLastAppliedEventID());
    }

    /**
     * @group integration
     * @dataProvider layersProvider
     */
    public function testCreation($l1Name, $l2Name)
    {
        $pool = $this->createPool($l1Name, $l2Name);

        // Empty L1 state.
        $this->assertEquals(0, $pool->getHitsL1());
        $this->assertEquals(0, $pool->getMissesL1());
        $this->assertNull($pool->getLastAppliedEventID());
        $this->assertEquals(0, $pool->collectGarbageL1());

        // Empty L2 state.
        $this->assertEquals(0, $pool->getHitsL2());
        $this->assertEquals(0, $pool->getMissesL2());
        $this->assertEquals(0, $pool->collectGarbageL2());
    }

    /**
     * @group integration
     * @dataProvider layersProvider
     */
    public function testTombstone($l1Name, $l2Name)
    {
        $pool = $this->createPool($l1Name, $l2Name);
        $address = new Address('mypool', 'mykey-dne');

        // This should create a tombstone, after missing both L1 and L2.
        $this->assertNull($pool->get($address));
        $this->assertEquals(1, $pool->getMissesL1());
        $this->assertEquals(1, $pool->getMissesL2());
        $this->assertEquals(0, $pool->getHitsL1());
        $this->assertEquals(0, $pool->getHitsL2());

        // Forecully get it and assert only a HIT in L1.
        $tombstone = $pool->getEntry($address, true);
        $this->assertNotNull($tombstone);
        $this->assertNull($tombstone->value);
        $this->assertEquals(1, $pool->getMissesL1());
        $this->assertEquals(1, $pool->getMissesL2());
        $this->assertEquals(1, $pool->getHitsL1());
        $this->assertEquals(0, $pool->getHitsL2());

        // The tombstone should also count as non-existence.
        $this->assertFalse($pool->exists($address));

        // This is a no-op for most L1 implementations, but it should not
        // return false, regardless.
        $this->assertTrue(false !== $pool->collectGarbage());
    }

    /**
     * @group integration
     * @dataProvider twoPoolsProvider
     */
    public function testSynchronization($central, $l1First, $l1Second)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = $this->createPool($l1First, $central);
        $pool2 = $this->createPool($l1Second, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Set and get an entry in Pool 1.
        $pool1->set($myaddr, 'myvalue');
        $this->assertEquals('myvalue', $pool1->get($myaddr));
        $this->assertEquals(1, $pool1->getHitsL1());
        $this->assertEquals(0, $pool1->getHitsL2());
        $this->assertEquals(0, $pool1->getMisses());

        // Read the entry in Pool 2.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $this->assertEquals(0, $pool2->getHitsL1());
        $this->assertEquals(1, $pool2->getHitsL2());
        $this->assertEquals(0, $pool2->getMisses());

        // Initialize Pool 2 synchronization.
        $changes = $pool2->synchronize();
        $this->assertNull($changes);
        $this->assertEquals(1, $pool2->getLastAppliedEventID());

        // Alter the item in Pool 1. Pool 2 should hit its L1 again
        // with the out-of-date item. Synchronizing should fix it.
        $pool1->set($myaddr, 'myvalue2');
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertEquals('myvalue2', $pool2->get($myaddr));

        // Delete the item in Pool 1. Pool 2 should hit its L1 again
        // with the now-deleted item. Synchronizing should fix it.
        $pool1->delete($myaddr);
        $this->assertEquals('myvalue2', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertNull($pool2->get($myaddr));

        // Try to get an entry that has never existed.
        $myaddr_nonexistent = new Address('mybin', 'mykeynonexistent');
        $this->assertNull($pool1->get($myaddr_nonexistent));

        // Test out bins and clearing.
        $mybin1_mykey = new Address('mybin1', 'mykey');
        $mybin1 = new Address('mybin1');
        $mybin2_mykey = new Address('mybin2', 'mykey');
        $pool1->set($mybin1_mykey, 'myvalue1');
        $pool1->set($mybin2_mykey, 'myvalue2');
        $pool2->synchronize();
        $pool1->delete($mybin1);

        // The deleted bin should be evident in pool1 but not in pool2.
        $this->assertNull($pool1->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool1->get($mybin2_mykey));
        $this->assertEquals('myvalue1', $pool2->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool2->get($mybin2_mykey));

        // Synchronizing should propagate the bin clearing to pool2.
        $pool2->synchronize();
        $this->assertNull($pool2->get($mybin1_mykey));
        $this->assertEquals('myvalue2', $pool2->get($mybin2_mykey));
    }
}
