<?php

namespace As3\Modlr\Metadata\Properties;

/**
 * Defines metadata for an attribute property.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class AttributeMetadata extends PropertyMetadata
{
    /**
     * READ-ONLY.
     * Whether this attribute is flagged to be stored as an autocomplete field in search.
     *
     * @var bool
     */
    public $autocomplete = false;

    /**
     * READ-ONLY.
     * Contains the caculated field parameters.
     *
     * @var array
     */
    public $calculated = [
        'class'     => null,
        'method'    => null,
    ];

    /**
     * READ-ONLY.
     * The attribute type, such as string, integer, float, etc.
     *
     * @var string
     */
    public $dataType;

    /**
     * READ-ONLY.
     * The attribute's default value, if set.
     *
     * @var mixed
     */
    public $defaultValue;

    /**
     * Constructor.
     *
     * @param   string  $key        The property field key.
     * @param   string  $dataType   The attribute data type.
     * @param   bool    $mixin      Whether the attribute derived from a mixin.
     */
    public function __construct($key, $dataType, $mixin = false)
    {
        parent::__construct($key, $mixin);
        $this->dataType = $dataType;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'attribute';
    }

    /**
     * Determines whether this attribute is flagged to be stored as an autocomplete field in search.
     *
     * @return  bool
     */
    public function hasAutocomplete()
    {
        return $this->autocomplete;
    }

    /**
     * Determines if this attribute is calculated.
     *
     * @return  bool
     */
    public function isCalculated()
    {
        return null !== $this->calculated['class'] && null !== $this->calculated['method'];
    }

    /**
     * Sets whether this attribute will be set as an autocomplete field in search.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setAutocomplete($bit = true)
    {
        $this->autocomplete = (Boolean) $bit;
        $this->setSearchProperty($bit);
        return $this;
    }
}
