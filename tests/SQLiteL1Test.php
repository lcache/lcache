<?php

namespace LCache;

//use phpunit\framework\TestCase;

class SQLiteL1Test extends \PHPUnit_Framework_TestCase
{

    use L1TestHelpersTrait;
    use L1TestsTrait;


    protected function setUp() {
        parent::setUp();
        $this->l1 = new SQLiteL1();
        // Some tests require comparing two L1s against each other.
        $this->l1_beta = new SQLiteL1();
    }

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

    // This method overrides a less complex parent method.
    public function testSynchronization()
    {
        $central = new StaticL2();
        $this->performSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());

        $this->performClearSynchronizationTest($central, new SQLiteL1(), new StaticL1());
        $this->performClearSynchronizationTest($central, new StaticL1(), new SQLiteL1());
        $this->performClearSynchronizationTest($central, new SQLiteL1(), new SQLiteL1());
    }
}
