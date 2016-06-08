<?php

namespace As3\Modlr\Metadata\Properties;

use As3\Modlr\Exception\MetadataException;

/**
 * Abstract property metadata class.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class PropertyMetadata
{
    /**
     * READ-ONLY.
     * A friendly description of the property.
     *
     * @var string
     */
    public $description;

    /**
     * READ-ONLY.
     * The property/field key.
     *
     * @var string
     */
    public $key;

    /**
     * READ-ONLY.
     * Determines if the property came from a mixin.
     *
     * @var bool
     */
    public $mixin;

    /**
     * READ-ONLY.
     * Whether this property should be persisted.
     *
     * @var bool
     */
    public $save = true;

    /**
     * READ-ONLY.
     * Determines whether this propety is stored in search.
     *
     * @var bool
     */
    public $searchProperty = false;

    /**
     * READ-ONLY.
     * Whether this property should be serialized.
     *
     * @var bool
     */
    public $serialize = true;

    /**
     * Constructor.
     *
     * @param   string  $key
     * @param   bool    $mixin
     */
    public function __construct($key, $mixin = false)
    {
        $this->validateKey($key);
        $this->mixin = (Boolean) $mixin;
        $this->key = $key;
    }

    /**
     * Enables or disables saving of this property.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function enableSave($bit = true)
    {
        $this->save = (bool) $bit;
        return $this;
    }

    /**
     * Enables or disables serialization of this property.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function enableSerialize($bit = true)
    {
        $this->serialize = (bool) $bit;
        return $this;
    }

    /**
     * Gets the property key.
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the property type, such as an attribute, relationship, etc.
     *
     * @return  string
     */
    abstract public function getType();

    /**
     * Determines whether this propety is stored in search.
     *
     * @return  bool
     */
    public function isSearchProperty()
    {
        return $this->searchProperty;
    }

    /**
     * Sets whether this property is stored in search.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setSearchProperty($bit = true)
    {
        $this->searchProperty = (Boolean) $bit;
        return $this;
    }

    /**
     * Whether this property should be saved/persisted to the data layer.
     *
     * @return  bool
     */
    public function shouldSave()
    {
        return $this->save;
    }

    /**
     * Whether this property should be serialized.
     *
     * @return  bool
     */
    public function shouldSerialize()
    {
        return $this->serialize;
    }

    /**
     * Validates that the property key is not reserved.
     *
     * @param   string  $key
     * @throws  MetadataException
     */
    private function validateKey($key)
    {
        $reserved = ['type', 'id'];
        if (in_array(strtolower($key), $reserved)) {
            throw MetadataException::reservedFieldKey($key, $reserved);
        }
    }
}
