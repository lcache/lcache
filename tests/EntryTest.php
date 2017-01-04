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
        $this->assertEquals(1, $this->getEntry(1)->getTTL());

        // TTL should be zero for already-expired items.
        $this->assertEquals(0, $this->getEntry(-1)->getTTL());

        // TODO: How to classify this type of item?
        $this->assertEquals(0, $this->getEntry(0)->getTTL());
    }

    public function testExpiry()
    {
        $this->assertTrue($this->getEntry(-1)->isExpired());
        $this->assertFalse($this->getEntry(1)->isExpired());

        // TODO: How to classify this one?
        $this->assertFalse($this->getEntry(0)->isExpired());
    }

    /**
     * Entry objects factory needed for testing purposes.
     *
     * @param int $ttl
     * @return \LCache\Entry
     */
    protected function getEntry($ttl = 0)
    {
        $pool = 'my-pool';
        $now = $_SERVER['REQUEST_TIME'];
        $address = new Address('bin', 'key');
        $entry = new Entry(0, $pool, $address, 'value', $now, $now + $ttl);
        return $entry;
    }
}
