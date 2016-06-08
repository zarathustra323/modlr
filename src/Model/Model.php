<?php

namespace As3\Modlr\Model;

use As3\Modlr\Model\Core\Properties;
use As3\Modlr\Metadata\ModelMetadata;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Model
{
    /**
     * The model identifier.
     *
     * @var string
     */
    private $identifier;

    /**
     * The model properties.
     *
     * @var Properties
     */
    private $properties;

    /**
     * Constructor.
     *
     * @param   ModelMetadata  $metadata
     * @param   string          $identifier
     * @param   Store           $store
     * @param   array|null      $properties
     */
    public function __construct(ModelMetadata $metadata, $identifier, Store $store, array $properties = null)
    {
        $this->identifier = $identifier;
        $this->properties = new Properties($metadata, $store, $properties);
    }

    /**
     * Clears a model property value.
     * For an attribute, will set the value to null.
     * For collections, will clear the collection contents.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  self
     */
    public function clear($key)
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Marks the record for deletion.
     * Will not remove from the database until $this->save() is called.
     *
     * @api
     * @return  self
     * @throws  \RuntimeException   If a new (unsaved) model is deleted.
     */
    public function delete()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Gets a model property.
     * Returns null if the property does not exist on the model or is not set.
     *
     * @api
     * @todo    Update the return annotation once new collections have been defined.
     * @param   string  $key    The property field key.
     * @return  Model|Model[]|Embed|Collections\EmbedCollection|null|mixed
     */
    public function get($key)
    {
        return $this->properties->get($key);
    }

    /**
     * Gets the composite key of the model.
     *
     * @api
     * @return  string
     */
    public function getCompositeKey()
    {
        return sprintf('%s.%s', $this->getType(), $this->getId());
    }

    /**
     * Gets the current change set of properties.
     *
     * @api
     * @return  array
     */
    public function getChangeSet()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Gets the unique identifier of this model.
     *
     * @api
     * @return  string
     */
    public function getId()
    {
        return $this->identifier;
    }

    /**
     * Gets the metadata for this model.
     *
     * @api
     * @return  ModelMetadata
     */
    public function getMetadata()
    {
        return $this->properties->getMetadata();
    }

    /**
     * Gets the model store.
     *
     * @api
     * @return  Store
     */
    public function getStore()
    {
        return $this->properties->getStore();
    }

    /**
     * Gets the model type.
     *
     * @api
     * @return  string
     */
    public function getType()
    {
        return $this->getMetadata()->type;
    }

    /**
     * Determines if the model is in a deleted state.
     *
     * @api
     * @return  bool
     */
    public function isDeleted()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }
    /**
     * Determines if the model is in a dirty state.
     *
     * @api
     * @return  bool
     */
    public function isDirty()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Determines if the model is in a loaded state.
     * A return value of `false` signifies that the model has not yet been retrieved from the persistence layer.
     *
     * @api
     * @return  bool
     */
    public function isLoaded()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Determines if the model is in a new state.
     * A return value of `true` signifies that the model does not yet have a corresponding record in the persistence layer.
     *
     * @api
     * @return  bool
     */
    public function isNew()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Pushes a Model into a has-many relationship collection.
     * This method must be used for has-many relationships. Direct set is not supported.
     * To completely replace a has-many, call clear() first and then push() the new Models.
     *
     * @api
     * @param   string  $key
     * @param   Model   $model
     * @return  self
     */
    public function push($key, Model $model)
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Reloads the model from the database.
     *
     * @api
     * @return  self
     */
    public function reload()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Removes a specific Model from a has-many relationship collection.
     *
     * @api
     * @param   string  $key    The has-many relationship key.
     * @param   Model   $model  The model to remove from the collection.
     * @return  self
     */
    public function remove($key, Model $model)
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Rolls back a model to its original values.
     *
     * @api
     * @return  self
     */
    public function rollback()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Saves the model.
     *
     * @api
     * @param   Implement cascade relationship saves. Or should the store handle this?
     * @return  self
     */
    public function save()
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Sets a model property.
     *
     * @api
     * @param   string  $key            The property field key.
     * @param   Model|Embed|null|mixed  The value to set.
     * @return  self
     */
    public function set($key, $value)
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Determines if the model uses a particlar mixin.
     *
     * @api
     * @param   string  $name
     * @return  bool
     */
    public function usesMixin($name)
    {
        return $this->getMetadata()->hasMixin($name);
    }

    /**
     * Gets the core model properties.
     * This should only be used by the internals of Modlr, and not by the end user.
     *
     * @return  Properties
     */
    public function _getProperties()
    {
        return $this->properties;
    }
}
