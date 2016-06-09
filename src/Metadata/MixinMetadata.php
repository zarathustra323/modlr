<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines the metadata for an entity mixin.
 * A mixin is like a PHP trait, in that properties can be reused by multiple models.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class MixinMetadata implements Interfaces\MetadataInterface
{
    /**
     * Uses properties.
     */
    use Traits\PropertiesTrait;

    /**
     * READ-ONLY.
     * The mixin name/key.
     *
     * @var string
     */
    public $name;

    /**
     * Constructor.
     *
     * @param   string  $name   The mixin name.
     */
    public function __construct($name)
    {
        $this->name = (String) $name;
    }

    /**
     * Prevents serialization of sub-property properties.
     *
     * @return  array
     */
    public function __sleep()
    {
        return $this->getPropertySleepVars();
    }

    /**
     * Gets the mixin key.
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->name;
    }

    /**
     * Is an alias for @see getKey().
     *
     * @deprecated
     * @return  string
     */
    public function getName()
    {
        return $this->getKey();
    }
}
