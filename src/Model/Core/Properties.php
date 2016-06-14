<?php

namespace As3\Modlr\Model\Core;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Metadata\Properties\PropertyMetadata;
use As3\Modlr\Metadata\Properties\RelationshipMetadata;
use As3\Modlr\Model\Embed;
use As3\Modlr\Model\Model;
use As3\Modlr\Models\Collections;
use As3\Modlr\Store\Store;

/**
 * Represents the properties of a Model.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Properties
{
    /**
     * Properties that have been converted to internal values.
     * Stored as $propKey => true.
     *
     * @var array
     */
    protected $converted = [];

    /**
     * @var ModelMetadataInterface
     */
    protected $metadata;

    /**
     * Modified property values.
     * Will only contain internally converted values.
     * Stored as $propKey => $propValue.
     *
     * @var array
     */
    protected $modified = [];

    /**
     * Original property values.
     * Can be a mix of original, persistence layer values or internally converted values.
     * Stored as $propKey => $propValue.
     *
     * @var array
     */
    protected $original = [];

    /**
     * Properties that have been flagged for removal.
     * Stored as $propKey => true.
     *
     * @var array
     */
    protected $remove = [];

    /**
     * The properties' state.
     *
     * @var bool[]
     */
    protected $state = [
        'new'       => false,
        'loaded'    => false,
    ];

    /**
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   array   $original   Any original properties to apply.
     */
    public function __construct(ModelMetadataInterface $metadata, Store $store, array $original = null, $new = false)
    {
        $this->setStateNew($new);
        $this->converted = $this->original;
        $this->metadata = $metadata;
        $this->store = $store;
        $this->initialize($original);
    }

    /**
     * Supresses the metadata and store properties when using var_dump.
     *
     * @return  array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        foreach (['metadata', 'store'] as $key) {
            $vars[$key] = sprintf('(Dump class %s directly to view)', get_class($vars[$key]));
        }
        return $vars;
    }

    /**
     * Determines if any of the properties are dirty.
     *
     * @return  bool
     */
    public function areDirty()
    {
        return !empty($this->modified) || !empty($this->remove);
    }

    /**
     * Determines if the properties have been loaded from the persistence layer.
     *
     * @return  bool
     */
    public function areLoaded()
    {
        return $this->state['loaded'];
    }

    /**
     * Determines if the properties are new
     *
     * @return  bool
     */
    public function areNew()
    {
        return $this->state['new'];
    }

    /**
     * Gets the current value of an property.
     *
     * @param   string  $key    The property key.
     * @return  mixed
     */
    public function get($key)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return;
        }
        if (true === $this->isCalculatedAttribute($key)) {
            // Calculated attribute. Retrieve the appropriate value.
            // Must always run in case the model's values change.
            return $this->getCalculatedAttrValue($key);
        }
        if (isset($this->remove[$key])) {
            // Ensures the default/converted values are still returned for the specified types.
            $useDefaults = ['attribute' => true, 'relationship-many' => true, 'embed-many' => true];
            if (isset($useDefaults[$propMeta->getType()])) {
                return $this->getOriginalValue($propMeta);
            }
            return;
        }
        if (isset($this->modified[$key])) {
            // The value was modified since loading from the persistence layer.
            return $this->modified[$key];
        }
        return $this->getOriginalValue($propMeta);
    }

    /**
     * Gets the metadata that represents this model's properties.
     *
     * @return  ModelMetadataInterface
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets the model store.
     *
     * @return  Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Determines if an attribute key is calculated.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    public function isCalculatedAttribute($key)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return false;
        }
        if (false === $propMeta->isAttribute()) {
            return false;
        }
        return $propMeta->isCalculated();
    }

    /**
     * Reinitializes the model properties from a persistence layer record.
     *
     * @param   array   $properties
     * @return  self
     */
    public function reinitialize(array $properties)
    {
        return $this->initialize($properties);
    }

    /**
     * Flags a property for removal.
     *
     * @param   string  $key    The property key.
     * @return  self
     */
    public function remove($key)
    {
        $this->remove[$key] = true;
        if (isset($this->modified[$key])) {
            unset($this->modified[$key]);
        }
        return $this;
    }

    /**
     * Rolls back the properties to their original state.
     * Preserves previously converted original values.
     *
     * @return  self
     */
    public function rollback()
    {
        $this->modified = [];
        $this->remove = [];
        return $this;
    }

    /**
     * Converts a raw property value to an internal value.
     *
     * @param   PropertyMetadata    $propMeta
     * @return  mixed
     */
    private function convertValue(PropertyMetadata $propMeta)
    {
        $key = $propMeta->getKey();
        $loader = $this->store->_getLoader();

        if (true === $propMeta->isRelationshipMany() && true === $propMeta->isInverse()) {
            throw new \BadMethodCallException('Inverse relationship loading is not yet implemented.');
        }

        if (!isset($this->original[$key])) {
            return $this->getDefaultValue($propMeta);
        }

        if (true === $propMeta->isAttribute()) {
            return $this->store->convertAttributeValue($propMeta->dataType, $this->original[$key]);
        }
        if (true === $propMeta->isRelationshipOne()) {
            return $loader->createProxyModel($this->original[$key]['type'], $this->original[$key]['id'], $this->store);
        }
        if (true === $propMeta->isRelationshipMany()) {
            return $loader->createModelCollection($propMeta, (array) $this->original[$key], $this->store);
        }
        if (true === $propMeta->isEmbedOne()) {
            return $loader->createEmbedModel($propMeta->getEmbedMetadata(), (array) $this->original[$key], $this->store);
        }
        if (true === $propMeta->isEmbedMany()) {
            return $loader->createEmbedCollection($propMeta, (array) $this->original[$key], $this->store);
        }
    }

    /**
     * Gets a calculated attribute value.
     *
     * @param   string  $key    The attribute key.
     * @return  mixed
     */
    private function getCalculatedAttrValue($key)
    {
        $attrMeta = $this->metadata->getProperty($key);
        $class  = $attrMeta->calculated['class'];
        $method = $attrMeta->calculated['method'];

        return $class::$method($this);
    }

    /**
     * Gets the default value for a property.
     *
     * @param   PropertyMetadata    $propMeta
     * @return  mixed
     */
    private function getDefaultValue(PropertyMetadata $propMeta)
    {
        if (true === $propMeta->isAttribute()) {
            // Load the default attribute value.
            return $propMeta->defaultValue;
        }
        if (true === $propMeta->isRelationshipMany()) {
            // Create empty relationship-many collection.
            return $loader->createModelCollection($propMeta, [], $this->store);
        }
        if (true === $propMeta->isEmbedMany()) {
            // Create empty embed-many collection.
            return $loader->createEmbedCollection($propMeta, [], $this->store);
        }
    }

    /**
     * Gets an original property value.
     *
     * @param   PropertyMetadata    $propMeta
     * @return  mixed
     */
    private function getOriginalValue(PropertyMetadata $propMeta)
    {
        $key = $propMeta->getKey();
        if (!isset($this->converted[$key])) {
            $this->original[$key] = $this->convertValue($propMeta);
            $this->converted[$key] = true;
        }
        return $this->original[$key];
    }

    /**
     * Initializes the model properties.
     *
     * @param   array|null  $properties     The record properties to apply.
     * @return  self
     */
    private function initialize(array $properties = null)
    {
        if (true === $this->areNew() || null === $properties) {
            return $this;
        }
        foreach ($properties as $key => $value) {
            $this->original[$key] = $value;
        }
        $this->setStateLoaded();
    }

    /**
     * Sets the properties state to loaded.
     *
     * @param   bool    $bit
     * @return  self
     */
    private function setStateLoaded($bit = true)
    {
        $this->state['loaded'] = (boolean) $bit;
        return $this;
    }

    /**
     * Sets the properties state to new.
     *
     * @param   bool    $bit
     * @return  self
     */
    private function setStateNew($bit = true)
    {
        $this->state['new'] = (boolean) $bit;
        if (true === $bit) {
            $this->setStateLoaded();
        }
        return $this;
    }

    // ********



    /**
     * Sets a new value to an property.
     *
     * @param   string  $key    The property key.
     * @param   mixed   $value  The value to set.
     * @return  mixed
     */
    public function set($key, $value)
    {
        if (null === $value) {
            return $this->remove($key);
        }
        $this->clearRemoval($key);

        $original = $this->getOriginal($key);
        if ($value === $original) {
            $this->clearChange($key);
        } else {
            if (($value instanceof \DateTime && $original instanceof \DateTime) && ($value->getTimestamp() === $original->getTimestamp())) {
                $this->clearChange($key);
            } else {
                $this->current[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Replaces the current properties with new ones.
     * Will revert/rollback any current changes.
     *
     * @param   array   $original
     * @return  self
     */
    public function replace(array $original)
    {
        $this->rollback();
        $this->original = $original;
        return $this;
    }

    /**
     * Calculates any property changes.
     *
     * @return  array
     */
    public function calculateChangeSet()
    {
        $set = [];
        foreach ($this->current as $key => $current) {
            $original = isset($this->original[$key]) ? $this->original[$key] : null;
            $set[$key]['old'] = $original;
            $set[$key]['new'] = $current;
        }
        foreach ($this->remove as $key) {
            $set[$key]['old'] = $this->original[$key];
            $set[$key]['new'] = null;
        }
        ksort($set);
        return $set;
    }

    /**
     * Clears an property from the removal queue.
     *
     * @param   string  $key    The field key.
     * @return  self
     */
    protected function clearRemoval($key)
    {
        if (false === $this->willRemove($key)) {
            return $this;
        }
        $key = array_search($key, $this->remove);
        unset($this->remove[$key]);
        $this->remove = array_values($this->remove);
        return $this;
    }

    /**
     * Clears an property as having been changed.
     *
     * @param   string  $key    The field key.
     * @return  self
     */
    protected function clearChange($key)
    {
        if (true === $this->willChange($key)) {
            unset($this->current[$key]);
        }
        return $this;
    }

    /**
     * Determines if an property is in the removal queue.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function willRemove($key)
    {
        return in_array($key, $this->remove);
    }

    /**
     * Determines if an property has a new value.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function willChange($key)
    {
        return null !== $this->getCurrent($key);
    }

    /**
     * Determines if an property has an original value.
     *
     * @param   string  $key    The field key.
     * @return  bool
     */
    protected function hasOriginal($key)
    {
        return null !== $this->getOriginal($key);
    }

    /**
     * Gets the property's original value.
     *
     * @param   string  $key    The field key.
     * @return  mixed
     */
    protected function getOriginal($key)
    {
        if (isset($this->original[$key])) {
            return $this->original[$key];
        }
        return null;
    }

    /**
     * Gets all original properties.
     *
     * @return  array
     */
    protected function getOriginalAll()
    {
        return $this->original;
    }

    /**
     * Gets all current properties.
     *
     * @return  array
     */
    protected function getCurrentAll()
    {
        return $this->current;
    }

    /**
     * Gets the property's current value.
     *
     * @param   string  $key    The field key.
     * @return  mixed
     */
    protected function getCurrent($key)
    {
        if (isset($this->current[$key])) {
            return $this->current[$key];
        }
        return null;
    }
}
