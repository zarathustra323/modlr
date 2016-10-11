<?php

namespace As3\Modlr\Model\Core;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Metadata\Properties\AttributeMetadata;
use As3\Modlr\Metadata\Properties\EmbeddedMetadata;
use As3\Modlr\Metadata\Properties\PropertyMetadata;
use As3\Modlr\Metadata\Properties\RelationshipMetadata;
use As3\Modlr\Model\AbstractModel;
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
     * @var ModelMetadataInterface
     */
    protected $metadata;

    /**
     * Modified property values.
     * Will only contain internally touched/converted values.
     * Stored as $propKey => $propValue.
     *
     * @var array
     */
    protected $modified = [];

    /**
     * Original property values.
     * Can be a mix of original, persistence layer values or internally touched values.
     * Stored as $propKey => $propValue.
     *
     * @var array
     */
    protected $original = [];

    /**
     * Flags properties that have been marked for removal.
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
     * Flags properties that have been touched and converted to internal values.
     * Stored as $propKey => true.
     *
     * @var array
     */
    protected $touched = [];

    /**
     * Constructor.
     *
     * @param   array   $original   Any original properties to apply.
     */
    public function __construct(ModelMetadataInterface $metadata, Store $store, array $original = null, $new = false)
    {
        $this->setStateNew($new);
        $this->touched = $this->original;
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
     * Applies an array of raw (but formatted) model properties to the model instance.
     * Is generally provided from the API adapter side.
     *
     * @param   array   $properties     The properties to apply.
     * @return  self
     */
    public function apply(array $properties)
    {
        foreach ($properties as $key => $value) {
            if (null === $propMeta = $this->metadata->getProperty($key)) {
                continue;
            }
            if ($propMeta->isAttribute()) {
                $this->set($key, $value);
            }
            if ($propMeta->isRelationshipOne()) {
                $this->applyRelationshipOne($key, $value);
            }
            if ($propMeta->isRelationshipMany()) {
                $this->applyRelationshipMany($propMeta, $key, $value);
            }
            if ($propMeta->isEmbedOne()) {
                $this->applyEmbedOne($key, $value);
            }
            if ($propMeta->isEmbedMany()) {
                $this->applyEmbedMany($propMeta, $key, $value);
            }
        }
        return $this;
    }

    /**
     * Determines if any of the properties are dirty.
     *
     * @return  bool
     */
    public function areDirty()
    {
        if (true === $this->areNew()) {
            return true;
        }
        if (!empty($this->modified) || !empty($this->remove)) {
            return true;
        }
        foreach ($this->touched as $key => $value) {
            $propMeta = $this->metadata->getProperty($key);

            if (true === $propMeta->isEmbedOne()) {
                $embed = $this->getOriginalValue($propMeta);
                if (null !== $embed && true === $embed->isDirty()) {
                    return true;
                }
            }
            if (true === $propMeta->isRelationshipMany() || true === $propMeta->isEmbedMany()) {
                if (true === $this->get($propMeta->getKey())->isDirty()) {
                    return true;
                }
            }
        }
        return false;
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
     * Calculates any property changes.
     *
     * @return  array
     */
    public function calculateChangeSet()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
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
     * Clears a property value.
     * For an attributes or has-ones, will set the value to null.
     * For collections, will clear the collection contents.
     *
     * @param   string  $key    The property key.
     * @return  self
     */
    public function clear($key)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return $this;
        }
        if (true === $propMeta->isAttribute() || true === $propMeta->isEmbedOne() || true === $propMeta->isRelationshipOne()) {
            return $this->set($key, null);
        }
        // Clear the has-many / embed-many collection.
        $this->get($key)->clear();
        return $this;
    }

    /**
     * Creates a new Embed model instance for the provided property key.
     *
     * @param   string  $key
     * @return  Embed
     * @throws  \RuntimeException
     */
    public function createEmbedFor($key)
    {
        $propMeta = $this->metadata->getProperty($key);
        if (null === $propMeta || false === $propMeta->isEmbed()) {
            throw new \RuntimeException(sprintf('Unable to create an Embed instance for property key "%s" - the property is not an embed.', $key));
        }
        return $this->getLoader()->createEmbedModel($propMeta->getEmbedMetadata(), [], $this->store, true);
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
            // The value is flagged for removal.
            return;
        }
        if (isset($this->modified[$key])) {
            // The value was modified since loading from the persistence layer.
            return $this->modified[$key];
        }
        // Get the original value (touch/convert if necessary).
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
     * Pushes a Model into a has-many collection.
     *
     * @param   string          $key
     * @param   AbstractModel   $model
     * @return  self
     */
    public function push($key, AbstractModel $model)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return $this;
        }
        if (true === $propMeta->isRelationshipMany() || true === $propMeta->isEmbedMany()) {
            $this->get($key)->push($model);
        }
        return $this;
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
     * Removes a model from a collection
     *
     * @param   string          $key    The property key.
     * @param   AbstractModel   $model  The model to remove.
     * @return  self
     */
    public function remove($key, AbstractModel $model)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return $this;
        }
        if (true === $propMeta->isRelationshipMany() || true === $propMeta->isEmbedMany()) {
            $this->get($key)->remove($model);
        }
        return $this;
    }

    /**
     * Rolls back the properties to their original state.
     * Preserves previously touched, original values.
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
     * Sets a new value to a property.
     *
     * @param   string  $key    The property key.
     * @param   mixed   $value  The value to set.
     * @return  self
     */
    public function set($key, $value)
    {
        if (null === $propMeta = $this->metadata->getProperty($key)) {
            return $this;
        }
        if (true === $propMeta->isAttribute()) {
            return $this->setAttribute($propMeta, $value);
        }

        if (true === $propMeta->isRelationship()) {
            return $this->setRelationship($propMeta, $value);
        }

        if (true === $propMeta->isEmbed()) {
            return $this->setEmbed($propMeta, $value);
        }
    }

    /**
     * Applies an embed-many value.
     *
     * @param   EmbeddedMetadata    $embedMeta
     * @param   string              $key
     * @param   mixed               $value
     */
    private function applyEmbedMany(EmbeddedMetadata $embedMeta, $key, $value)
    {
        if (empty($value)) {
            $this->clear($key);
            return;
        }
        if (!is_array($value)) {
            return;
        }

        $collection = $this->getLoader()->createEmbedCollection($embedMeta, $value, $this->store);
        if ($collection->getHash() === $this->get($key)->getHash()) {
            // The current collection is the same as the incoming collection.
            return;
        }

        // The incoming collection is different. Clear the current collection and push the new values.
        $this->clear($key);
        foreach ($collection as $embed) {
            $this->push($key, $embed);
        }
    }

    /**
     * Applies an embed-one value.
     *
     * @param   string  $key
     * @param   mixed   $value
     */
    private function applyEmbedOne($key, $value)
    {
        if (empty($value)) {
            $this->clear($key);
            return;
        }
        if (!is_array($value)) {
            return;
        }

        $embed = $this->get($key) ?: $this->createEmbedFor($key);
        $embed->apply($value);
        $this->set($key, $embed);
    }

    /**
     * Applies a rel-many value.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   string                  $key
     * @param   mixed                   $value
     */
    private function applyRelationshipMany(RelationshipMetadata $relMeta, $key, $value)
    {
        if ($relMeta->isInverse()) {
            return;
        }
        $value = (array) $value;
        $this->clear($key);
        $collection = $this->getLoader()->createModelCollection($relMeta, $value, $this->store);
        foreach ($collection as $model) {
            $this->push($key, $model);
        }
    }

    /**
     * Applies a rel-one value.
     *
     * @param   string  $key
     * @param   mixed   $value
     */
    private function applyRelationshipOne($key, $value)
    {
        if (empty($value) || !is_array($value)) {
            $value = null;
        } else {
            $value = $this->getLoader()->createProxyModel($value['type'], $value['id'], $this->store);
        }
        $this->set($key, $value);
    }

    /**
     * Clears any existing property state for the provided key.
     *
     * @param   string  $key
     * @return  self
     */
    private function clearPropertyStateFor($key)
    {
        if (isset($this->remove[$key])) {
            unset($this->remove[$key]);
        }
        if (isset($this->modified[$key])) {
            unset($this->modified[$key]);
        }
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
            return $this->getLoader()->createProxyModel($this->original[$key]['type'], $this->original[$key]['id'], $this->store);
        }
        if (true === $propMeta->isRelationshipMany()) {
            return $this->getLoader()->createModelCollection($propMeta, (array) $this->original[$key], $this->store);
        }
        if (true === $propMeta->isEmbedOne()) {
            return $this->getLoader()->createEmbedModel($propMeta->getEmbedMetadata(), (array) $this->original[$key], $this->store);
        }
        if (true === $propMeta->isEmbedMany()) {
            return $this->getLoader()->createEmbedCollection($propMeta, (array) $this->original[$key], $this->store);
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
            return $this->getLoader()->createModelCollection($propMeta, [], $this->store);
        }
        if (true === $propMeta->isEmbedMany()) {
            // Create empty embed-many collection.
            return $this->getLoader()->createEmbedCollection($propMeta, [], $this->store);
        }
    }

    private function getLoader()
    {
        return $this->store->_getLoader();
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
        if (!isset($this->touched[$key])) {
            $this->original[$key] = $this->convertValue($propMeta);
            $this->touched[$key] = true;
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
        // @todo This will likely need to, at least, set any default values! Or the changeset will have to account for it.
        if (true === $this->areNew() || null === $properties) {
            return $this;
        }
        foreach ($properties as $key => $value) {
            $this->original[$key] = $value;
        }
        $this->setStateLoaded();
    }

    /**
     * Sets an attribute value.
     *
     * @todo    Add caclulated attribute setting.
     * @param   AttributeMetadata   $attrMeta
     * @param   mixed               $value
     * @return  self
     */
    private function setAttribute(AttributeMetadata $attrMeta, $value)
    {
        if (true === $attrMeta->isCalculated()) {
            // @todo Add support for calculated attribute setting.
            return $this;
        }
        $key = $attrMeta->getKey();
        // Clear any existing attribute state.
        $this->clearPropertyStateFor($key);

        // Convert the incoming value.
        $current = $this->store->convertAttributeValue($attrMeta->dataType, $value);
        if (null === $current) {
            // Fill with the default value.
            $current = $attrMeta->defaultValue;
        }

        // Compare the current and original values.
        $original = $this->getOriginalValue($attrMeta);
        if ('date' === $attrMeta->dataType) {
            // Dates need to compare timestamps (due to potential localization differences).
            if ($current->getTimestamp() === $original->getTimestamp()) {
                return $this;
            }
        } else if ($current === $original) {
            // No change.
            return $this;
        }
        // Change detected.
        if (null === $current) {
            $this->remove[$key] = true;
            return $this;
        }
        $this->modified[$key] = $current;
        return $this;
    }

    /**
     * Sets an embed value.
     *
     * @param   EmbeddedMetadata    $embedMeta
     * @param   Embed|null          $current
     * @return  self
     */
    private function setEmbed(EmbeddedMetadata $embedMeta, Embed $current = null)
    {
        if (true === $embedMeta->isMany()) {
            throw new \RuntimeException('You cannot directly set a has-many embed. Use `push,` `clear` and/or `remove` instead.');
        }

        $key = $embedMeta->getKey();
        // Clear any existing relationship state.
        $this->clearPropertyStateFor($key);

        $original = $this->getOriginalValue($embedMeta);
        if (null === $current) {
            if (null !== $original) {
                $this->remove[$key] = true;
            }
            return $this;
        }

        // Validate that this embed can add the provided model.
        $this->store->validateEmbedSet($embedMeta->getEmbedMetadata(), $current->getType());

        if (null === $original || $current->getHash() !== $original->getHash()) {
            $this->modified[$key] = $current;
        }
        return $this;
    }

    /**
     * Sets a relationship value.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   Model|null              $current
     * @return  self
     */
    private function setRelationship(RelationshipMetadata $relMeta, Model $current = null)
    {
        if (true === $relMeta->isMany()) {
            throw new \RuntimeException('You cannot directly set a has-many relationship. Use `push,` `clear` and/or `remove` instead.');
        }

        $key = $relMeta->getKey();
        // Clear any existing relationship state.
        $this->clearPropertyStateFor($key);

        $original = $this->getOriginalValue($relMeta);
        if (null === $current) {
            if (null !== $original) {
                $this->remove[$key] = true;
            }
            return $this;
        }

        // Validate that this relationship can add the provided model.
        $metadata = $this->store->getMetadataForType($relMeta->getModelType());
        $this->store->validateRelationshipSet($metadata, $current->getType());

        if (null === $original || $current->getId() !== $original->getId()) {
            $this->modified[$key] = $current;
        }
        return $this;
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
}
