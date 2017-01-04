<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LCache\L2;

use LCache\Address;

/**
 * Description of DatabaseTest
 *
 * @author ndobromirov
 */
class DatabaseTest extends \LCache\L2CacheTest
{
    use \LCache\Utils\LCacheDBTestTrait;

    protected function l2FactoryOptions()
    {
        $this->createSchema($this->dbPrefix);
        return ['database', [
            'handle' => $this->dbh,
            'prefix' => $this->dbPrefix,
        ]];
    }

    public function testDatabaseL2Prefix()
    {
        $this->dbPrefix = 'myprefix_';
        $myaddr = new Address('mybin', 'mykey');

        $l2 = $this->createL2();

        $l2->set('mypool', $myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }
}
