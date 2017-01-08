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
            'database' => [
                'handle' => $this->dbh,
                'log' => $this->dbErrorsLog,
            ],
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
    public function createPool($l1Name, $l2Name, array $options = [])
    {
        $options += [
            'l1-pool' => null,
            'integrated-threshold' => null,
        ];
        $l1 = $this->createL1($l1Name, $options['l1-pool']);
        $l2 = $this->createL2($l2Name);
        $pool = $this->getDriverInstance($l1, $l2, $options['integrated-threshold']);
        return $pool;
    }

    public function l1Provider()
    {
        $result = [];
        foreach ($this->supportedL1Drivers() as $l1) {
            $result["L1 driver: $l1"] = [$l1];
        }
        return $result;
    }

    public function poolProvider()
    {
        return $this->poolProviderHelper($this->supportedL2Drivers());
    }

    public function poolProviderForLimitedGarbageCollecionSupport()
    {
        $allL2 = $this->supportedL2Drivers();
        unset($allL2['database']);
        return $this->poolProviderHelper($allL2);
    }

    protected function poolProviderHelper($customL2)
    {
        $results = [];
        $allL1 = $this->supportedL1Drivers();
        foreach (array_keys($customL2) as $l2) {
            foreach ($allL1 as $l1) {
                $results["Integrating L1:$l1 and L2:$l2"] = [$l1, $l2];
            }
        }
        return $results;
    }

    public function twoPoolsProvider()
    {
        $results = [];
        $allL1 = $this->supportedL1Drivers();
        foreach (array_keys($this->supportedL2Drivers()) as $l2) {
            foreach ($allL1 as $l11) {
                foreach ($allL1 as $l12) {
                    $name = "Pool-1 L1:$l11-L2:$l2 and Pool-2 L1:$l12-L2:$l2";
                    $results[$name] = [$l2, $l11, $l12];
                }
            }
        }
        return $results;
    }

    /**
     * @group integration
     * @dataProvider poolProvider
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
     * @dataProvider poolProvider
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
     * @dataProvider poolProvider
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

    /**
     * @group integration
     * @dataProvider twoPoolsProvider
     */
    public function testClearSynchronization($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = $this->createPool($first_l1, $central);
        $pool2 = $this->createPool($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Create an item, synchronize, and then do a complete clear.
        $pool1->set($myaddr, 'mynewvalue');
        $this->assertEquals('mynewvalue', $pool1->get($myaddr));
        $pool2->synchronize();
        $this->assertEquals('mynewvalue', $pool2->get($myaddr));
        $pool1->delete(new Address());
        $this->assertNull($pool1->get($myaddr));

        // Pool 2 should lag until it synchronizes.
        $this->assertEquals('mynewvalue', $pool2->get($myaddr));
        $pool2->synchronize();
        $this->assertNull($pool2->get($myaddr));
    }

    /**
     * @group integration
     * @dataProvider twoPoolsProvider
     */
    public function testTaggedSynchronization($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = $this->createPool($first_l1, $central);
        $pool2 = $this->createPool($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Test deleting a tag that doesn't exist yet.
        $pool1->deleteTag('mytag');

        // Set and get an entry in Pool 1.
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals([$myaddr], $pool1->getAddressesForTag('mytag'));
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
        $pool2->synchronize();

        // Delete the tag. The item should now be missing from Pool 1.
        $pool1->deleteTag('mytag'); // TKTK
        $this->assertNull($pool1->get($myaddr));
        $this->assertEquals(1, $pool1->getMissesL1());
        $this->assertEquals(1, $pool1->getMissesL2());


        // Pool 2 should hit its L1 again with the tag-deleted item.
        // Synchronizing should fix it.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertNull($pool2->get($myaddr));

        // Ensure the addition of a second tag still works for deletion.
        $myaddr2 = new Address('mybin', 'mykey2');
        $pool1->set($myaddr2, 'myvalue', null, ['mytag']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->deleteTag('mytag2');
        $this->assertNull($pool1->get($myaddr2));

        // Ensure updating a second item with a tag doesn't remove it from the
        // first.
        $pool1->delete(new Address());
        $pool1->set($myaddr, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);

        // The function getAddressesForTag() may return additional addresses,
        // but it should always return at least the current tagged address.
        $found_addresses = $pool2->getAddressesForTag('mytag2');
        $this->assertEquals([$myaddr2], array_values(array_filter($found_addresses, function ($addr) use ($myaddr2) {
            return $addr->serialize() === $myaddr2->serialize();
        })));
    }

    /**
     * @group integration
     * @dataProvider l1Provider
     */
    public function testBrokenDatabaseFallback($l1)
    {
        $this->dbErrorsLog = true;

        $myaddr = new Address('mybin', 'mykey');
        $myaddr2 = new Address('mybin', 'mykey2');
        $pool = $this->createPool($l1, 'database');

        // Break the schema and try operations.
        $this->dbh->exec('DROP TABLE lcache_tags');
        $this->assertNull($pool->set($myaddr, 'myvalue', null, ['mytag']));
//        $this->assertGreaterThanOREqual(1, count($l2->getErrors()));
        $this->assertNull($pool->deleteTag('mytag'));
        $pool->synchronize();

        // Break
        $this->dbh->exec('DROP TABLE lcache_events');
        $this->assertNull($pool->synchronize());
        $this->assertNull($pool->get($myaddr2));
        $this->assertNull($pool->exists($myaddr2));
        $this->assertNull($pool->set($myaddr, 'myvalue'));
        $this->assertNull($pool->delete($myaddr));
        $this->assertNull($pool->delete(new Address()));
        $this->assertNull($pool->getAddressesForTag('mytag'));

        // Try applying events to an uninitialized L1.
        $pool2 = $this->createPool($l1, 'database');
        $this->assertNull($pool2->synchronize());

        // Try garbage collection routines.
        $this->assertEquals(0, $pool->collectGarbage());
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testSyncWithNoWrites($l1, $l2)
    {
        $this->dbErrorsLog = true;
        $this->assertNull($this->createPool($l1, $l2)->synchronize());
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testExists($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');

        $this->assertNotNull($pool->set($myaddr, 'myvalue'));
        $this->assertTrue($pool->exists($myaddr));
        $this->assertNotNull($pool->delete($myaddr));
        $this->assertFalse($pool->exists($myaddr));
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testPoolValueAccessor($l1, $l2)
    {
        $poolName = 'test-pool-name';
        $pool = $this->createPool($l1, $l2, ['l1-pool' => $poolName]);
        $this->assertEquals($poolName, $pool->getPool());
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testExpiration($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');

        $this->assertNotNull($pool->set($myaddr, 'value', 1));
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $pool->getEntry($myaddr)->expiration);

        // Setting an TTL/expiration more than request time should be treated
        // as an expiration.
        $this->assertNotNull($pool->set($myaddr, 'value', $_SERVER['REQUEST_TIME'] + 1));
        $this->assertEquals('value', $pool->get($myaddr));
        $this->assertEquals($_SERVER['REQUEST_TIME'] + 1, $pool->getEntry($myaddr)->expiration);
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testExcessiveOverheadSkipping($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2, ['integrated-threshold' => 2]);
        $myaddr = new Address('mybin', 'mykey');

        // These should go through entirely.
        $this->assertNotNull($pool->set($myaddr, 'myvalue1'));
        $this->assertNotNull($pool->set($myaddr, 'myvalue2'));

        // This should return an event_id but delete the item.
        $this->assertEquals(2, $pool->getL1()->getKeyOverhead($myaddr));
        $this->assertFalse($pool->getL1()->isNegativeCache($myaddr));
        $this->assertNotNull($pool->set($myaddr, 'myvalue3'));
        $this->assertFalse($pool->exists($myaddr));

        // A few more sets to offset the existence check, which some L1s may
        // treat as a hit. This should put us firmly in excessive territory.
        $pool->set($myaddr, 'myvalue4');
        $pool->set($myaddr, 'myvalue5');
        $pool->set($myaddr, 'myvalue6');

        // Now, with the local negative cache, these shouldn't even return
        // an event_id.
        $this->assertNull($pool->set($myaddr, 'myvalueA1'));
        $this->assertNull($pool->set($myaddr, 'myvalueA2'));

        // Test a lot of sets but with enough hits to drop below the threshold.
        $myaddr2 = new Address('mybin', 'mykey2');
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertEquals('myvalue', $pool->get($myaddr2));
        $this->assertEquals('myvalue', $pool->get($myaddr2));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
        $this->assertNotNull($pool->set($myaddr2, 'myvalue'));
    }

    /**
     * $this overlaps with L1CacheTest::testStateStorage().
     *
     * @group integration
     * @dataProvider poolProvider
     */
    public function testHitSetCounter($l1, $l2)
    {
        $myaddr = new Address('mybin', 'mykey');
        $pool = $this->createPool($l1, $l2);
        $l1Driver = $pool->getL1();

        $this->assertEquals(0, $l1Driver->getKeyOverhead($myaddr));
        $pool->set($myaddr, 'myvalue');
        $this->assertEquals(1, $l1Driver->getKeyOverhead($myaddr));
        $pool->get($myaddr);
        $this->assertEquals(0, $l1Driver->getKeyOverhead($myaddr));
        $pool->set($myaddr, 'myvalue2');
        $this->assertEquals(1, $l1Driver->getKeyOverhead($myaddr));

        // An unknown get should create negative overhead, generally
        // in anticipation of a set.
        $myaddr2 = new Address('mybin', 'mykey2');
        $this->assertNull($pool->get($myaddr2));
        $this->assertEquals(-1, $l1Driver->getKeyOverhead($myaddr2));
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     * @expectedException LCache\UnserializationException
     */
    public function testFailedUnserializationOnGet($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2);
        $invalid_object = 'O:10:"HelloWorl":0:{}';
        $myaddr = new Address('mybin', 'performFailedUnserializationOnGetTest');

        $pool->getL2()->set('anypool', $myaddr, $invalid_object, null, [], true);
        $pool->get($myaddr);
    }

    /**
     * @group integration
     * @dataProvider poolProvider
     */
    public function testGarbageCollection($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');

        $this->assertEquals(0, $pool->getL2()->countGarbage());
        $pool->set($myaddr, 'myvalue', -1);
        $this->assertEquals(1, $pool->getL2()->countGarbage());
        $pool->collectGarbage();
        $this->assertEquals(0, $pool->getL2()->countGarbage());
    }

    /**
     * @group integration
     * @dataProvider poolProviderForLimitedGarbageCollecionSupport
     */
    public function testLimitedGarbageCollection($l1, $l2)
    {
        $pool = $this->createPool($l1, $l2);
        $myaddr1 = new Address('mybin', 'mykey1');
        $myaddr2 = new Address('mybin', 'mykey2');

        $this->assertEquals(0, $pool->collectGarbage());
        $pool->set($myaddr1, 'myvalue', -1);
        $pool->set($myaddr2, 'myvalue', -1);
        $this->assertEquals(2, $pool->getL2()->countGarbage());
        $pool->collectGarbage(1);
        $this->assertEquals(1, $pool->getL2()->countGarbage());
    }
}
