<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines the metadata for an embed.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbedMetadata implements Interfaces\ModelMetadataInterface
{
    /**
     * Uses mixins.
     */
    use Traits\MixinsTrait;

    /**
     * Uses properties.
     */
    use Traits\PropertiesTrait;

    /**
     * READ-ONLY.
     * The embed name/key.
     *
     * @var string
     */
    public $name;

    /**
     * Constructor.
     *
     * @param   string  $name   The embed name.
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
     * Gets the embed key.
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

    /**
     * {@inheritdoc}
     */
    public function isEmbedded()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyMixinProperties(MixinMetadata $mixin)
    {
        foreach ($mixin->getProperties() as $property) {
            if (true === $this->hasProperty($property->getKey())) {
                throw MetadataException::mixinPropertyExists($this->type, $mixin->name, 'property', $property->getKey());
            }
            $this->addProperty($property);
        }
    }
}
