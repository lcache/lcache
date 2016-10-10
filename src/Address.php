<?php

namespace LCache;

/**
 * Represents a specific address in a cache, or everything in one bin,
 * or everything in the entire cache.
 */
final class Address implements \Serializable
{
    /** @var string|null */
    protected $bin;
    /** @var string|null */
    protected $key;

    /**
     * Address constructor.
     *
     * @param string|null $bin
     * @param string|null $key
     */
    public function __construct($bin = null, $key = null)
    {
        assert(!is_null($bin) || is_null($key));
        $this->bin = $bin;
        $this->key = $key;
    }

    /**
     * Get the bin.
     * @return string|null
     */
    public function getBin()
    {
        return $this->bin;
    }

    /**
     * Get the key.
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Return true if address refers to everything in the entire bin.
     * @return boolean
     */
    public function isEntireBin()
    {
        return is_null($this->key);
    }

    /**
     * Return true if address refers to everything in the entire cache.
     * @return boolean
     */
    public function isEntireCache()
    {
        return is_null($this->bin);
    }

    /**
     * Return true if this object refers to any of the same objects as the
     * provided Address object.
     * @param Address $address
     * @return boolean
     */
    public function isMatch(Address $address)
    {
        if (!is_null($address->getBin()) && !is_null($this->bin) && $address->getBin() !== $this->bin) {
            return false;
        }
        if (!is_null($address->getKey()) && !is_null($this->key) && $address->getKey() !== $this->key) {
            return false;
        }
        return true;
    }

    /**
     * Serialize this object, returning a string representing this address.
     *
     * The serialized form must:
     *
     *  - Place the bin first
     *  - Return a prefix matching all entries in a bin with a NULL key
     *  - Return a prefix matching all entries with a NULL bin
     *
     * @return string
     */
    public function serialize()
    {
        if (is_null($this->bin)) {
            return '';
        }

        $length_prefixed_bin = strlen($this->bin) . ':' . $this->bin;

        if (is_null($this->key)) {
            return $length_prefixed_bin . ':';
        }
        return $length_prefixed_bin . ':' . $this->key;
    }

    /**
     * Unpack a serialized Address into this object.
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $entries = explode(':', $serialized, 2);
        $this->bin = null;
        $this->key = null;
        if (count($entries) === 2) {
            list($bin_length, $bin_and_key) = $entries;
            $bin_length = intval($bin_length);
            $this->bin = substr($bin_and_key, 0, $bin_length);
            $this->key = substr($bin_and_key, $bin_length + 1);
        }

        // @TODO: Remove check against false for PHP 7+
        if ($this->key === false || $this->key === '') {
            $this->key = null;
        }
    }
}
