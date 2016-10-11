<?php

namespace As3\Modlr\Model;

use As3\Modlr\Model\Core\Properties;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractModel
{
    /**
     * The model properties.
     *
     * @var Properties
     */
    protected $properties;

    /**
     * Whether the model has been flagged for deletion.
     *
     * @var bool
     */
    private $deleted = false;

    /**
     * Constructor.
     *
     * @param   ModelMetadataInterface  $metadata
     * @param   Store                   $store
     * @param   array|null              $properties
     * @param   bool                    $new
     */
    public function __construct(ModelMetadataInterface $metadata, Store $store, array $properties = null, $new = false)
    {
        $this->properties = new Properties($metadata, $store, $properties, $new);
    }

    /**
     * Clears a property value.
     * For an attributes or has-ones, will set the value to null.
     * For collections, will clear the collection contents.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  self
     */
    public function clear($key)
    {
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this->properties->clear($key);
    }

    /**
     * Creates a new Embed model instance for the provided property key.
     *
     * @param   string  $key
     * @return  Embed
     */
    public function createEmbedFor($key)
    {
        return $this->properties->createEmbedFor($key);
    }

    /**
     * Marks the record for deletion.
     * Will not remove from the database until $this->save() is called.
     *
     * @api
     * @return  self
     * @throws  \UnexpectedValueException   If a new (unsaved) model is deleted.
     */
    public function delete()
    {
        if (true === $this->getMetadata()->isEmbedded()) {
            // @todo Should this actually remove the embed completely?
            return $this;
        }
        if (true === $this->isNew()) {
            throw new \UnexpectedValueException('You cannot delete a new model.');
        }
        $this->deleted = true;
        return $this;
    }

    /**
     * Gets the value of model property.
     * Returns null if the property does not exist on the model or is not set.
     *
     * @api
     * @param   string  $key    The property field key.
     * @return  Model|Embed|Collections\ModelCollection|Collections\EmbedCollection|null|mixed
     */
    public function get($key)
    {
        $this->touch();
        return $this->properties->get($key);
    }

    /**
     * Gets the composite key of the model.
     *
     * @api
     * @return  string
     */
    abstract public function getCompositeKey();

    /**
     * Gets the current change set of properties.
     *
     * @api
     * @return  array
     */
    public function getChangeSet()
    {
        // @todo Make sure default values (default attr, empty has-one, has-many) are not included in the changeset.
        throw new \BadMethodCallException(sprintf('%s not yet implemented.', __METHOD__));
        return $this;
    }

    /**
     * Gets the metadata for this model.
     *
     * @api
     * @return  ModelMetadataInterface
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
        return $this->getMetadata()->getKey();
    }

    /**
     * Determines if the model is in a deleted state.
     *
     * @api
     * @return  bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Determines if the model is in a dirty state.
     *
     * @api
     * @return  bool
     */
    public function isDirty()
    {
        return $this->properties->areDirty();
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
        return $this->properties->areLoaded();
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
        return $this->properties->areNew();
    }

    /**
     * Pushes a Model into a has-many collection.
     * This method must be used for collections. Direct set is not supported.
     * To completely replace a has-many, call clear() first and then push() the new models.
     *
     * @api
     * @param   string          $key
     * @param   AbstractModel   $model
     * @return  self
     */
    public function push($key, AbstractModel $model)
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
        $this->touch(true);
        return $this;
    }

    /**
     * Removes a specific model from a has-many collection.
     *
     * @api
     * @param   string          $key    The has-many property key.
     * @param   AbstractModel   $model  The model to remove from the collection.
     * @return  self
     */
    public function remove($key, AbstractModel $model)
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
        $this->touch();
        $this->properties->set($key, $value);
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
     * Accessing/modifying in userland code will cause stability problems. Do not use.
     *
     * @return  Properties
     */
    public function _getProperties()
    {
        return $this->properties;
    }

    /**
     * Touches the model and loads from the persistence layer if currently unloaded.
     *
     * @param   bool    $force  Whether to force the load, even if the model is currently loaded.
     * @return  self
     */
    protected function touch($force = false)
    {
        if (true === $this->getMetadata()->isEmbedded() || true === $this->isDeleted()) {
            return $this;
        }
        if (false === $this->isLoaded() || true == $force) {
            $store = $this->getStore();
            $metadata = $this->getMetadata();

            $record = $store->retrieveRecord($this->getType(), $this->getId());
            $this->properties = new Properties($metadata, $store, $record['properties']);
        }
        return $this;
    }
}
