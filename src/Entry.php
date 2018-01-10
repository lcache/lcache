<?php

namespace LCache;

final class Entry
{
    public $event_id;
    public $pool;
    protected $address;
    public $value;
    public $created;
    public $expiration;
    public $tags;

    public function __construct($event_id, $pool, Address $address, $value, $created, $expiration = null, array $tags = [])
    {
        $this->event_id = $event_id;
        $this->pool = $pool;
        $this->address = $address;
        $this->value = $value;
        $this->created = $created;
        $this->expiration = $expiration;
        $this->tags = $tags;
    }

    /**
     * Return the Address for this entry.
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Return the time-to-live for this entry.
     * @return integer
     */
    public function getTTL()
    {
        if (is_null($this->expiration)) {
            return null;
        }
        $current = time();

        if ($this->expiration > $current) {
            return $this->expiration - $current;
        }
        return 0;
    }
}
