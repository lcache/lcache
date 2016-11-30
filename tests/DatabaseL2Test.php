<?php

namespace LCache;

//use phpunit\framework\TestCase;

class DatabaseL2Test extends \PHPUnit_Extensions_Database_TestCase
{
    use L2TestHelpers;
    use L1TestHelpers;
    use DatabaseTestTrait;


    public function testSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1'), new StaticL1('testSynchronizationDatabase2'));
        $this->performClearSynchronizationTest($central, new StaticL1('testSynchronizationDatabase1a'), new StaticL1('testSynchronizationDatabase2a'));
    }

    public function testTaggedSynchronizationDatabase()
    {
        $this->createSchema();
        $central = new DatabaseL2($this->dbh);
        $this->performTaggedSynchronizationTest($central, new StaticL1('testTaggedSynchronizationDatabase1'), new StaticL1('testTaggedSynchronizationDatabase2'));
    }

    public function testDatabaseL2SyncWithNoWrites()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh, '', true);
        $l1 = new StaticL1('first');
        $pool = new Integrated($l1, $l2);
        $pool->synchronize();
    }

    public function testExistsDatabaseL2()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertTrue($l2->exists($myaddr));
        $l2->delete('mypool', $myaddr);
        $this->assertFalse($l2->exists($myaddr));
    }

    public function testEmptyCleanUpDatabaseL2()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
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
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performFailedUnserializationTest($l2);
        $this->performCaughtUnserializationOnGetTest($l2);
    }





    /**
     * @expectedException LCache\UnserializationException
     */
    public function testDatabaseL2FailedUnserializationOnGet()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performFailedUnserializationOnGetTest($l2);
    }


    public function testDatabaseL2GarbageCollection()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $this->performGarbageCollectionTest($l2);
    }



    public function testDatabaseL2BatchDeletion()
    {
        $this->createSchema();
        $l2 = new DatabaseL2($this->dbh);
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');

        $mybin = new Address('mybin', null);
        $l2->delete('mypool', $mybin);

        $this->assertNull($l2->get($myaddr));
    }

}
