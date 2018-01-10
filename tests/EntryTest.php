<?php

namespace LCache;

/**
 * Entry class tests.
 *
 * @author ndobromirov
 */
class EntryTest extends \PHPUnit_Framework_TestCase
{

    public function testEntryTTL()
    {
        $created_time = time();
        $myaddr = new Address('mybin', 'mykey');
        $entry = new Entry(0, 'mypool', $myaddr, 'value', $created_time, $created_time + 1);
        $this->assertEquals(1, $entry->getTTL());

        // TTL should be zero for already-expired items.
        $old_entry = new Entry(0, 'mypool', $myaddr, 'value', $created_time, $created_time - 1);
        $this->assertEquals(0, $old_entry->getTTL());
    }
}
