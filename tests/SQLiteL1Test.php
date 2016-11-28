<?php

namespace LCache;

//use phpunit\framework\TestCase;

class SQLiteL1Test extends \PHPUnit_Framework_TestCase
{

    use L1TestHelpers;

    public function testSQLiteL1SchemaErrorHandling()
    {
        $pool_name = uniqid('', true) . '-' . mt_rand();
        $l1_a = new SQLiteL1($pool_name);

        // Opening a second instance of the same pool should work.
        $l1_b = new SQLiteL1($pool_name);

        $myaddr = new Address('mybin', 'mykey');

        $l1_a->set(1, $myaddr, 'myvalue');

        // Reading from the second handle should show the value written to the
        // first.
        $this->assertEquals('myvalue', $l1_b->get($myaddr));
    }

    public function testSQLiteL1Tombstone()
    {
        $l1 = new SQLiteL1();
        $this->performTombstoneTest($l1);
    }

    public function testSQLiteL1SetGetDelete()
    {
        $l1 = new SQLiteL1();
        $this->performSetGetDeleteTest($l1);
    }

    public function testExistsSQLiteL1()
    {
        $l1 = new SQLiteL1();
        $this->performExistsTest($l1);
    }


    public function testSynchronizationSQLiteL1()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());

        $this->performClearSynchronizationTest($central, new SQLiteL1(), new StaticL1());
        $this->performClearSynchronizationTest($central, new StaticL1(), new SQLiteL1());
        $this->performClearSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());
    }


    public function testSQLite1Antirollback()
    {
        $l1 = new SQLiteL1();
        $this->performL1AntirollbackTest($l1);
    }

    public function testSQLiteL1Counters()
    {
        $this->performHitSetCounterTest(new SQLiteL1());
    }

    public function testSQLiteL1HitMiss()
    {
        $l1 = new SQLiteL1();
        $this->performL1HitMissTest($l1);
    }


    public function testSQLiteL1ExcessiveOverheadSkipping()
    {
        $this->performExcessiveOverheadSkippingTest(new SQLiteL1());
    }

}
