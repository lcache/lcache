<?php

namespace LCache;

//use phpunit\framework\TestCase;

class DatabaseL2Test extends \PHPUnit_Extensions_Database_TestCase
{
    use L2TestHelpers;
    use L1TestHelpers;
    use DatabaseTestTrait;


    protected function setUp() {
        parent::setUp();
        $this->createSchema();
        $this->l2  = new DatabaseL2($this->dbh);
    }

    public function testSynchronizationDatabase()
    {
        $central = new DatabaseL2($this->dbh);
        $this->performSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1'), new StaticL1('testSynchronizationDatabase2'));
        $this->performClearSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1a'), new StaticL1('testSynchronizationDatabase2a'));
    }

    public function testTaggedSynchronizationDatabase()
    {
        $central = new DatabaseL2($this->dbh);
        $this->performTaggedSynchronizationTest($central, new StaticL1('testTaggedSynchronizationDatabase1'), new StaticL1('testTaggedSynchronizationDatabase2'));
    }

    public function testDatabaseL2SyncWithNoWrites()
    {
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = new StaticL1('first');
        $pool = new Integrated($l1, $l2);
        $pool->synchronize();
    }

    public function testExistsDatabaseL2()
    {
        $myaddr = new Address('mybin', 'mykey');
        $this->l2->set('mypool', $myaddr, 'myvalue');
        $this->assertTrue($this->l2->exists($myaddr));
        $this->l2->delete('mypool', $myaddr);
        $this->assertFalse($this->l2->exists($myaddr));
    }

    public function testEmptyCleanUpDatabaseL2()
    {
        // @todo, I don't know what this test does.
        $l2 = $this->l2;
    }


    public function testDatabaseL2Prefix()
    {
        $this->createSchema('myprefix_');
        $l2 = new DatabaseL2($this->dbh, 'myprefix_');
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testDatabaseL2FailedUnserialization()
    {
        $this->performFailedUnserializationTest($this->l2);
        $this->performCaughtUnserializationOnGetTest($this->l2);
    }

    /**
     * @expectedException LCache\UnserializationException
     */
    public function testDatabaseL2FailedUnserializationOnGet()
    {
        $this->performFailedUnserializationOnGetTest($this->l2);
    }


    public function testDatabaseL2GarbageCollection()
    {
        $this->performGarbageCollectionTest($this->l2);
    }



    public function testDatabaseL2BatchDeletion()
    {
        $myaddr = new Address('mybin', 'mykey');
        $this->l2->set('mypool', $myaddr, 'myvalue');

        $mybin = new Address('mybin', null);
        $this->l2->delete('mypool', $mybin);

        $this->assertNull($this->l2->get($myaddr));
    }


    public function testBrokenDatabaseFallback()
    {
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

}
