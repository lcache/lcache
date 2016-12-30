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

    public function testCleanupAfterWrite()
    {
        $myaddr = new Address('mybin', 'mykey');

        // Write to the key with the first client.
        $l2_client_a = $this->createL2();
        $event_id_a = $l2_client_a->set('mypool', $myaddr, 'myvalue');

        // Verify that the first event exists and has the right value.
        $event = $l2_client_a->getEvent($event_id_a);
        $this->assertEquals('myvalue', $event->value);

        // Use a second client. This gives us a fresh event_id_low_water,
        // just like a new PHP request.
        $l2_client_b = $this->createL2();

        // Write to the same key with the second client.
        $event_id_b = $l2_client_b->set('mypool', $myaddr, 'myvalue2');

        // Verify that the second event exists and has the right value.
        $event = $l2_client_b->getEvent($event_id_b);
        $this->assertEquals('myvalue2', $event->value);

        // Call the same method as on destruction. This second client should
        // now prune any writes to the key from earlier requests.
        $l2_client_b->pruneReplacedEvents();

        // Verify that the first event no longer exists.
        $event = $l2_client_b->getEvent($event_id_a);
        $this->assertNull($event);
    }
}
