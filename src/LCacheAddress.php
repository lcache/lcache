<?php

namespace LCache\LCache;

final class LCacheAddress implements \Serializable
{
    protected $bin;
    protected $key;
    public function __construct($bin = null, $key = null)
    {
        assert(!is_null($bin) || is_null($key));
        assert(strpos($bin, ':') === false);
        $this->bin = $bin;
        $this->key = $key;
    }

    public function getBin()
    {
        return $this->bin;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function isEntireBin()
    {
        return is_null($this->key);
    }

    public function isEntireCache()
    {
        return is_null($this->bin);
    }

    public function isMatch(LCacheAddress $address)
    {
        if (!is_null($address->getBin()) && !is_null($this->bin) && $address->getBin() !== $this->bin) {
            return false;
        }
        if (!is_null($address->getKey()) && !is_null($this->key) && $address->getKey() !== $this->key) {
            return false;
        }
        return true;
    }

  // The serialized form must:
  //  - Place the bin first
  //  - Return a prefix matching all entries in a bin with a NULL key
  //  - Return a prefix matching all entries with a NULL bin
    public function serialize()
    {
        if (is_null($this->bin)) {
            return '';
        } elseif (is_null($this->key)) {
            return $this->bin . ':';
        }
        return $this->bin . ':' . $this->key;
    }

    public function unserialize($serialized)
    {
        $entries = explode(':', $serialized, 2);
        $this->bin = null;
        $this->key = null;
        if (count($entries) === 2) {
            list($this->bin, $this->key) = $entries;
        }
        if ($this->key === '') {
            $this->key = null;
        }
    }
}


// Operate properly when testing in case Drupal isn't running this code.
if (!defined('REQUEST_TIME')) {
    define('REQUEST_TIME', time());
}
