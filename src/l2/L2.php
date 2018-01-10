<?php

namespace LCache\l2;

use LCache\Address;
use LCache\LX;
use LCache\l1\L1;

abstract class L2 extends LX
{
    abstract public function applyEvents(L1 $l1);
    abstract public function set($pool, Address $address, $value = null, $expiration = null, array $tags = [], $value_is_serialized = false);
    abstract public function delete($pool, Address $address);
    abstract public function deleteTag(L1 $l1, $tag);
    abstract public function getAddressesForTag($tag);
    abstract public function countGarbage();
}
