<?php

namespace LCache;

/**
 * Address class tests.
 *
 * @author ndobromirov
 */
class AddressTest extends \PHPUnit_Framework_TestCase
{

    public function testSerialization()
    {
        $mybin_mykey = new Address('mybin', 'mykey');
        $this->performSerializationTest($mybin_mykey);

        // An entire bin address should match against any entry in the bin.
        $entire_mybin = new Address('mybin');
        $this->performSerializationTest($entire_mybin);
        $this->assertEquals(strpos($entire_mybin->serialize(), $mybin_mykey->serialize()), 0);

        // An entire bin address should match against any entry.
        $entire_cache = new Address();
        $this->performSerializationTest($entire_cache);
        $this->assertEquals(strpos($entire_mybin->serialize(), $mybin_mykey->serialize()), 0);
    }

    protected function performSerializationTest($address)
    {
        // The bin and key should persist across native serialization and
        // unserialization.
        $rehydrated = unserialize(serialize($address));
        $this->assertEquals($rehydrated->getKey(), $address->getKey());
        $this->assertEquals($rehydrated->getBin(), $address->getBin());

        if (is_null($address->getBin())) {
            $this->assertNull($rehydrated->getBin());
        }
        if (is_null($address->getKey())) {
            $this->assertNull($rehydrated->getKey());
        }

        // Same for non-native.
        $rehydrated = new Address();
        $rehydrated->unserialize($address->serialize());
        $this->assertEquals($rehydrated->getKey(), $address->getKey());
        $this->assertEquals($rehydrated->getBin(), $address->getBin());
    }

    public function testMatching()
    {
        $entire_cache = new Address();
        $entire_mybin = new Address('mybin');
        $mybin_mykey = new Address('mybin', 'mykey');
        $mybin_mykey2 = new Address('mybin', 'mykey2');
        $mybin2_mykey2 = new Address('mybin2', 'mykey2');

        $this->assertTrue($entire_cache->isMatch($mybin_mykey));
        $this->assertTrue($mybin_mykey->isMatch($entire_cache));

        $this->assertTrue($entire_mybin->isMatch($mybin_mykey));
        $this->assertTrue($mybin_mykey->isMatch($entire_mybin));

        $this->assertFalse($mybin_mykey->isMatch($mybin_mykey2));
        $this->assertFalse($mybin_mykey2->isMatch($mybin_mykey));

        $this->assertFalse($entire_mybin->isMatch($mybin2_mykey2));
        $this->assertFalse($mybin2_mykey2->isMatch($entire_mybin));
    }
}
