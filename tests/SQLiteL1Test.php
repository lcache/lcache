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
}
