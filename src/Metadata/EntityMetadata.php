<?php

namespace As3\Modlr\Metadata;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\Properties\PropertyMetadata;

/**
 * Defines the metadata for a model.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class EntityMetadata implements Interfaces\MergeableInterface, Interfaces\MetadataPropertiesInterface, Interfaces\MixinInterface
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
     * The id key name and type.
     */
    const ID_KEY  = 'id';
    const ID_TYPE = 'string';

    /**
     * The model type key.
     */
    const TYPE_KEY = 'type';

    /**
     * READ-ONLY.
     * Whether this class is abstract.
     *
     * @var bool
     */
    public $abstract = false;

    /**
     * READ-ONLY.
     * An array of attribute default values for this model.
     * Keyed by field name.
     *
     * @var array
     */
    public $defaultValues = [];

    /**
     * READ-ONLY.
     * The model type this model extends.
     *
     * @var string|null
     */
    public $extends;

    /**
     * READ-ONLY.
     * Child model types this model owns.
     * Only used for polymorphic entities.
     *
     * @var array
     */
    public $ownedTypes = [];

    /**
     * READ-ONLY.
     * The persistence metadata for this model.
     *
     * @var Interfaces\StorageLayerInterface
     */
    public $persistence;

    /**
     * READ-ONLY.
     * Whether this class is considered polymorphic.
     *
     * @var bool
     */
    public $polymorphic = false;

    /**
     * READ-ONLY.
     * The search metadata for this model.
     *
     * @var Interfaces\StorageLayerInterface
     */
    public $search;

    /**
     * READ-ONLY.
     * Uniquely defines the type of model.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param   string  $type   The model type.
     */
    public function __construct($type)
    {
        $this->setType($type);
    }

    /**
     * Gets the parent model type.
     * For models that are extended.
     *
     * @return  string|null
     */
    public function getParentEntityType()
    {
        return $this->extends;
    }

    /**
     * Whether this metadata represents an abstract model.
     *
     * @return  bool
     */
    public function isAbstract()
    {
        return (Boolean) $this->abstract;
    }

    /**
     * Determines if this is a child model of another model.
     *
     * @return  bool
     */
    public function isChildEntity()
    {
        return null !== $this->getParentModelType();
    }

    /**
     * Whether this metadata represents a polymorphic model.
     *
     * @return  bool
     */
    public function isPolymorphic()
    {
        return (Boolean) $this->polymorphic;
    }

    /**
     * Deteremines whether search is enabled for this model.
     *
     * @return  bool
     */
    public function isSearchEnabled()
    {
        return null !== $this->search;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(Interfaces\MergeableInterface $metadata)
    {
        if (!$metadata instanceof ModelMetadata) {
            throw new MetadataException('Unable to merge metadata. The provided metadata instance is not compatible.');
        }

        $this->setType($metadata->type);
        $this->setPolymorphic($metadata->isPolymorphic());
        $this->setAbstract($metadata->isAbstract());
        $this->extends = $metadata->extends;
        $this->ownedTypes = $metadata->ownedTypes;
        $this->defaultValues = array_merge($this->defaultValues, $metadata->defaultValues);

        $this->persistence->merge($metadata->persistence);
        $this->search->merge($metadata->search);

        $this->mergeProperties($metadata->getProperties());
        $this->mergeMixins($metadata->getMixins());
        return $this;
    }

    /**
     * Sets this metadata as representing an abstract model.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setAbstract($bit = true)
    {
        $this->abstract = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the persistence metadata for this model.
     *
     * @param   Interfaces\StorageLayerInterface    $persistence
     * @return  self
     */
    public function setPersistence(Interfaces\StorageLayerInterface $persistence)
    {
        $this->persistence = $persistence;
        return $this;
    }

    /**
     * Sets this metadata as representing a polymorphic model.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setPolymorphic($bit = true)
    {
        $this->polymorphic = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the search metadata for this model.
     *
     * @param   Interfaces\StorageLayerInterface    $search
     * @return  self
     */
    public function setSearch(Interfaces\StorageLayerInterface $search)
    {
        $this->search = $search;
        return $this;
    }

    /**
     * Sets the model type.
     *
     * @param   string  $type
     * @return  self
     * @throws  MetadataException   If the type is not a string or is empty.
     */
    public function setType($type)
    {
        if (!is_string($type) || empty($type)) {
            throw MetadataException::invalidEntityType($type);
        }
        $this->type = $type;
        return $this;
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

    /**
     * Merges properties with this instance's properties.
     *
     * @param   PropertyMetadata[]     $toAdd
     * @return  self
     */
    private function mergeProperties(array $toAdd)
    {
        foreach ($toAdd as $property) {
            $this->addProperty($property);
        }
        return $this;
    }

    /**
     * Merges mixins with this instance's mixins.
     *
     * @param   MixinMetadata[]     $toAdd
     * @return  self
     */
    private function mergeMixins(array $toAdd)
    {
        foreach ($toAdd as $mixin) {
            if (!isset($this->mixins[$mixin->name])) {
                $this->mixins[$mixin->name] = $mixin;
            }
        }
        return $this;
    }
}
