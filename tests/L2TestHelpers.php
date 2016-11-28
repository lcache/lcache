<?php

namespace LCache;

//use phpunit\framework\TestCase;

trait L2TestHelpers
{


    protected function performTaggedSynchronizationTest($central, $first_l1, $second_l1)
    {
        // Create two integrated pools with independent L1s.
        $pool1 = new Integrated($first_l1, $central);
        $pool2 = new Integrated($second_l1, $central);

        $myaddr = new Address('mybin', 'mykey');

        // Test deleting a tag that doesn't exist yet.
        $pool1->deleteTag('mytag');

        // Set and get an entry in Pool 1.
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals([$myaddr], $central->getAddressesForTag('mytag'));
        $this->assertEquals('myvalue', $pool1->get($myaddr));
        $this->assertEquals(1, $pool1->getHitsL1());
        $this->assertEquals(0, $pool1->getHitsL2());
        $this->assertEquals(0, $pool1->getMisses());

        // Read the entry in Pool 2.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $this->assertEquals(0, $pool2->getHitsL1());
        $this->assertEquals(1, $pool2->getHitsL2());
        $this->assertEquals(0, $pool2->getMisses());


        // Initialize Pool 2 synchronization.
        $pool2->synchronize();

        // Delete the tag. The item should now be missing from Pool 1.
        $pool1->deleteTag('mytag'); // TKTK
        $this->assertNull($central->get($myaddr));
        $this->assertNull($first_l1->get($myaddr));
        $this->assertNull($pool1->get($myaddr));


        // Pool 2 should hit its L1 again with the tag-deleted item.
        // Synchronizing should fix it.
        $this->assertEquals('myvalue', $pool2->get($myaddr));
        $applied = $pool2->synchronize();
        $this->assertEquals(1, $applied);
        $this->assertNull($pool2->get($myaddr));

        // Ensure the addition of a second tag still works for deletion.
        $myaddr2 = new Address('mybin', 'mykey2');
        $pool1->set($myaddr2, 'myvalue', null, ['mytag']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->deleteTag('mytag2');
        $this->assertNull($pool1->get($myaddr2));

        // Ensure updating a second item with a tag doesn't remove it from the
        // first.
        $pool1->delete(new Address());
        $pool1->set($myaddr, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr2, 'myvalue', null, ['mytag', 'mytag2']);
        $pool1->set($myaddr, 'myvalue', null, ['mytag']);

        $found_addresses = $central->getAddressesForTag('mytag2');
        // getAddressesForTag() may return additional addresses, but it should
        // always return at least the current tagged address.
        $found = false;
        foreach ($found_addresses as $found_address) {
            if ($found_address->serialize() === $myaddr2->serialize()) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }


    protected function performFailedUnserializationTest($l2)
    {
        $l1 = new StaticL1();
        $pool = new Integrated($l1, $l2);
        $myaddr = new Address('mybin', 'mykey');

        $invalid_object = 'O:10:"HelloWorl":0:{}';

        // Set the L1's high water mark.
        $pool->set($myaddr, 'valid');
        $changes = $pool->synchronize();
        $this->assertNull($changes);  // Just initialized event high water mark.
        $this->assertEquals(1, $l1->getLastAppliedEventID());

        // Put an invalid object into the L2 and synchronize again.
        $l2->set('anotherpool', $myaddr, $invalid_object, null, [], true);
        $changes = $pool->synchronize();
        $this->assertEquals(1, $changes);
        $this->assertEquals(2, $l1->getLastAppliedEventID());

        // The sync should delete the item from the L1, causing it to miss.
        $this->assertNull($l1->get($myaddr));
        $this->assertEquals(0, $l1->getHits());
        $this->assertEquals(1, $l1->getMisses());
    }

    protected function performCaughtUnserializationOnGetTest($l2)
    {
        $l1 = new StaticL1();
        $pool = new Integrated($l1, $l2);
        $invalid_object = 'O:10:"HelloWorl":0:{}';
        $myaddr = new Address('mybin', 'performCaughtUnserializationOnGetTest');
        $l2->set('anypool', $myaddr, $invalid_object, null, [], true);
        try {
            $pool->get($myaddr);
            $this->assertTrue(false);  // Should not reach here.
        } catch (UnserializationException $e) {
            $this->assertEquals($invalid_object, $e->getSerializedData());

            // The text of the exception should include the class name, bin, and key.
            $this->assertRegExp('/^' . preg_quote('LCache\UnserializationException: Cache') . '/', strval($e));
            $this->assertRegExp('/bin "' . preg_quote($myaddr->getBin()) . '"/', strval($e));
            $this->assertRegExp('/key "' . preg_quote($myaddr->getKey()) . '"/', strval($e));
        }
    }

    public function performGarbageCollectionTest($l2)
    {
        $pool = new Integrated(new StaticL1(), $l2);
        $myaddr = new Address('mybin', 'mykey');
        $this->assertEquals(0, $l2->countGarbage());
        $pool->set($myaddr, 'myvalue', -1);
        $this->assertEquals(1, $l2->countGarbage());
        $pool->collectGarbage();
        $this->assertEquals(0, $l2->countGarbage());
    }

    public function performGarbageCollectionTest($l2)
    {
        $pool = new Integrated(new StaticL1(), $l2);
        $myaddr = new Address('mybin', 'mykey');
        $this->assertEquals(0, $l2->countGarbage());
        $pool->set($myaddr, 'myvalue', -1);
        $this->assertEquals(1, $l2->countGarbage());
        $pool->collectGarbage();
        $this->assertEquals(0, $l2->countGarbage());
    }

    // Callers should expect an UnserializationException.
    protected function performFailedUnserializationOnGetTest($l2)
    {
        $l1 = new StaticL1();
        $pool = new Integrated($l1, $l2);
        $invalid_object = 'O:10:"HelloWorl":0:{}';
        $myaddr = new Address('mybin', 'performFailedUnserializationOnGetTest');
        $l2->set('anypool', $myaddr, $invalid_object, null, [], true);
        $pool->get($myaddr);
    }
}
