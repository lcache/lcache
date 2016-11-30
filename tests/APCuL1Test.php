<?php

namespace LCache;

//use phpunit\framework\TestCase;

class APCuL1Test extends \PHPUnit_Framework_TestCase
{

    use L1TestHelpers;
    use L1TestsTrait;

    protected function setUp() {
        parent::setUp();
        $this->l1 = new APCuL1($this->getName());
        $this->l1_beta = new APCuL1($this->getName() . '_beta');
    }


    public function testAPCuL1PoolIDs()
    {
        // Test unique ID generation.
        $l1 = new APCuL1();
        $this->assertNotNull($l1->getPool());

        // Test host-based generation.
        $_SERVER['SERVER_ADDR'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $l1 = new APCuL1();
        $this->assertEquals('localhost-80', $l1->getPool());
    }

    public function testSynchronization()
    {
        // Warning: As long as LCache\APCuL1 flushes all of APCu on a wildcard
        // deletion, it is not possible to test such functionality in a
        // single process.

        $run_test = false;
        if (function_exists('apcu_store')) {
            apcu_store('test_key', 'test_value');
            $value = apcu_fetch('test_key');
            if ($value === 'test_value') {
                $run_test = true;
            }
        }

        if ($run_test) {
            $central = new StaticL2();
            $this->performSynchronizationTest($central, new APCuL1('testSynchronizationAPCu1'), new APCuL1('testSynchronizationAPCu2'));

            // Because of how APCu only offers full cache clears, we test against a static cache for the other L1.
            $this->performClearSynchronizationTest($central, new APCuL1('testSynchronizationAPCu1b'), new StaticL1());
            $this->performClearSynchronizationTest($central, new StaticL1(), new APCuL1('testSynchronizationAPCu1c'));
        } else {
            $this->markTestSkipped('The APCu extension is not installed, enabled (for the CLI), or functional.');
        }
    }
}
