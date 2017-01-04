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

    /**
     *
     * @param type $event_id
     * @param type $pool
     * @param \LCache\Address $address
     * @param type $value
     * @param type $created
     * @param type $expiration
     * @param array $tags
     */
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
     * @return \LCache\Address
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
        if ($this->expiration > $_SERVER['REQUEST_TIME']) {
            return $this->expiration - $_SERVER['REQUEST_TIME'];
        }
        return 0;
    }
}
