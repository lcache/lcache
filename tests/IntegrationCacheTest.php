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
        return new L1CacheFactory($state);;
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
}
