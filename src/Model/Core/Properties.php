<?php

namespace As3\Modlr\Model\Core;

use As3\Modlr\Metadata\ModelMetadata;
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
     * @var ModelMetadata
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
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   array   $original   Any original properties to apply.
     */
    public function __construct(ModelMetadata $metadata, Store $store, array $original = null)
    {
        $this->converted = $this->original;
        $this->metadata = $metadata;
        $this->store = $store;
        $this->initialize($original);
    }

    /**
     * Gets the current value of an property.
     *
     * @param   string  $key    The property key.
     * @return  mixed
     */
    public function get($key)
    {
        if (true === $this->isCalculatedAttribute($key)) {
            // Calculated attribute. Retrieve the appropriate value.
            // Must always run in case the model's values change.
            return $this->getCalculatedAttrValue($key);
        }
        if (isset($this->remove[$key])) {
            // The value is marked for removal.
            // @todo This should still return an empty collection for hasMany items.
            return;
        }
        if (isset($this->modified[$key])) {
            // The value was modified since loading from the persistence layer.
            return $this->modified[$key];
        }
        return $this->getOriginalValue($key);
    }

    /**
     * Gets the metadata that represents this model's properties.
     *
     * @return  ModelMetadata
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
        if (null === $attrMeta = $this->metadata->getAttribute($key)) {
            return false;
        }
        return $attrMeta->isCalculated();
    }

    /**
     * Sets a new value to a property.
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
     * @param   string  $key
     * @return  mixed
     */
    protected function convertValue($key)
    {
        if (!isset($this->original[$key])) {
            if (null !== $attrMeta = $this->metadata->getAttribute($key)) {
                // Load the default attribute value
                return $attrMeta->defaultValue;
            }
            // @todo elseif isHasMany return empty Collection; elseif isEmbedMany return empty Collection;
            return;
        }

        if (null !== $attrMeta = $this->metadata->getAttribute($key)) {
            return $this->store->convertAttributeValue($attrMeta->dataType, $this->original[$key]);
        }
        if (null !== $relMeta = $this->metadata->getRelationship($key)) {
            if (true === $relMeta->isOne()) {
                // Load the proxy model.
                return $this->store->loadProxyModel($this->original[$key]['type'], $this->original[$key]['id']);
            } else {
                // Load the lazy model collection.
            }
        }
    }

    /**
     * Gets a calculated attribute value.
     *
     * @param   string  $key    The attribute key.
     * @return  mixed
     */
    protected function getCalculatedAttrValue($key)
    {
        $attrMeta = $this->metadata->getAttribute($key);
        $class  = $attrMeta->calculated['class'];
        $method = $attrMeta->calculated['method'];

        return $class::$method($this);
    }

    /**
     * Gets an original property value.
     *
     * @param   string  $key
     * @return  mixed
     */
    protected function getOriginalValue($key)
    {
        if (!isset($this->converted[$key])) {
            $this->original[$key] = $this->convertValue($key);
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
    protected function initialize(array $properties = null)
    {
        if (null === $properties) {
            return $this;
        }

        foreach ($properties as $key => $value) {
            $this->original[$key] = $value;
        }
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
     * Deteremines if the properties have different values from their original state.
     *
     * @return  bool
     */
    public function areDirty()
    {
        return !empty($this->current) || !empty($this->remove);
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
