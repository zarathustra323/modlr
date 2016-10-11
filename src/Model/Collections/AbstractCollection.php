<?php

namespace As3\Modlr\Model\Collections;

use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use As3\Modlr\Metadata\Interfaces\MetadataInterface;
use As3\Modlr\Model\Core\CollectionIterator;
use As3\Modlr\Model\AbstractModel;
use As3\Modlr\Store\Store;

/**
 * Collection that contains record representations from a persistence (database) layer.
 * These representations can either be of first-order Models or fragmentted Embeds.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
abstract class AbstractCollection implements IteratorAggregate, Countable
{
    /**
     * Models added to this collection.
     * Tracks newly added models for rollback/change purposes.
     *
     * @var AbstractModel[]
     */
    protected $added = [];

    /**
     * Current models assigned to this collection.
     * Needed for iteration, access, and count purposes.
     *
     * @var AbstractModel[]
     */
    protected $current = [];

    /**
     * Whether the collection has been loaded with data from the persistence layer
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var MetadataInterface
     */
    protected $metadata;

    /**
     * Original models assigned to this collection.
     *
     * @var AbstractModel[]
     */
    protected $original = [];

    /**
     * Whether the collection has been loaded with proxy models from the original identifiers.
     *
     * @var bool
     */
    protected $proxied = false;

    /**
     * Models removed from this collection.
     * Tracks removed models for rollback/change purposes.
     *
     * @var AbstractModel[]
     */
    protected $removed = [];

    /**
     * The store for handling storage operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * The total count of the collection,
     * Acts as if no offsets or limits were originally applied to the Model set.
     *
     * @var int
     */
    protected $totalCount;

    /**
     * Constructor.
     *
     * @param   MetadataInterface   $metadata
     * @param   Store               $store
     * @param   int                 $totalCount
     */
    public function __construct(MetadataInterface $metadata, Store $store, $totalCount)
    {
        $this->metadata = $metadata;
        $this->store = $store;
        $this->totalCount = (integer) $totalCount;
    }

    /**
     * Supresses the store and metadata properties when using var_dump.
     *
     * @return  array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        foreach (['store', 'metadata'] as $key) {
            $vars[$key] = sprintf('(Dump class %s directly to view)', get_class($vars[$key]));
        }
        return $vars;
    }

    /**
     * Calculates the change set of this collection.
     *
     * @return  array
     */
    public function calculateChangeSet()
    {
        // @todo Vet touch.
        $this->touch();
        if (false === $this->isDirty()) {
            return [];
        }
        return [
            'old' => empty($this->original) ? null : $this->original,
            'new' => empty($this->current)  ? null : $this->current,
        ];
    }

    /**
     * Clears/empties the collection.
     *
     * @return  self
     */
    public function clear()
    {
        $this->proxy();
        $this->current = [];
        $this->added = [];
        $this->removed = $this->original;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $this->proxy();
        return count($this->current);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->proxy();
        return new CollectionIterator($this->current, function() {
            $this->touch();
        });
    }

    /**
     * Gets the metadata for the model collection.
     *
     * @return  EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets a single model result from the collection.
     *
     * @return  AbstractModel|null
     */
    public function getSingleResult()
    {
        if (0 === $this->count()) {
            return null;
        }
        return reset($this->current);
    }

    /**
     * Gets the 'total' model count, as if a limit and offset were not applied.
     *
     * @return  int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Gets the model collection type.
     *
     * @return  string
     */
    public function getType()
    {
        return $this->metadata->getKey();
    }

    /**
     * Determines if the Model is included in the collection.
     *
     * @param   AbstractModel   $model  The model to check.
     * @return  bool
     */
    public function has(AbstractModel $model)
    {
        $this->proxy();
        $key = $model->getCompositeKey();
        return isset($this->current[$key]);
    }

    /**
     * Determines if any models in this collection are dirty (have changes).
     *
     * @return  bool
     */
    public function hasDirtyModels()
    {
        // @todo Vet touch.
        $this->touch();
        foreach ($this->current as $model) {
            if (true === $model->isDirty()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the collection is dirty.
     *
     * @return  bool
     */
    public function isDirty()
    {
        return !empty($this->added) || !empty($this->removed);
    }

    /**
     * Determines if this collection is empty.
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * Determines if models in this collection have been loaded from the persistence layer.
     *
     * @return  bool
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Determines if models in this collection have been proxied.
     *
     * @return  bool
     */
    public function isProxied()
    {
        return $this->proxied;
    }

    /**
     * Pushes a Model into the collection.
     *
     * @param   AbstractModel   $model  The model to push.
     * @return  self
     */
    public function push(AbstractModel $model)
    {
        $this->proxy();
        $this->validateAdd($model);
        $key = $model->getCompositeKey();
        if (isset($this->added[$key])) {
            return $this;
        }

        if (isset($this->removed[$key])) {
            $this->evict('removed', $model);
            $this->set('current', $model);
            return $this;
        }

        if (isset($this->original[$key])) {
            return $this;
        }
        $this->set('added', $model);
        $this->set('current', $model);
        return $this;
    }

    /**
     * Removes a model from the collection.
     *
     * @param   AbstractModel   $model  The model to remove.
     * @return  self
     */
    public function remove(AbstractModel $model)
    {
        $this->proxy();
        $this->validateAdd($model);
        $key = $model->getCompositeKey();
        if (isset($this->removed[$key])) {
            return $this;
        }

        if (isset($this->added[$key])) {
            $this->evict('added', $model);
            $this->evict('current', $model);
            return $this;
        }

        if (isset($this->original[$key])) {
            $this->evict('current', $model);
            $this->set('removed', $model);
        }
        return $this;
    }

    /**
     * Rolls back the collection it it's original state.
     *
     * @return  self
     */
    public function rollback()
    {
        $this->proxy();
        $this->current = $this->original;
        $this->added = [];
        $this->removed = [];
        return $this;
    }

    /**
     * Adds a model to this collection.
     * Is used during initial collection construction.
     *
     * @param   AbstractModel   $model
     * @return  self
     */
    protected function add(AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        $this->validateAdd($model);
        $this->current[$key] = $model;
        $this->original[$key] = $model;
        return $this;
    }

    /**
     * Evicts a model from a collection property (original, added, removed, current).
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to set.
     * @return  self
     */
    protected function evict($property, AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        if (isset($this->{$property}[$key])) {
            unset($this->{$property}[$key]);

            if ('current' === $property) {
                $this->totalCount--;
            }
        }
        return $this;
    }

    /**
     * Sets a model to a collection property (original, added, removed, models).
     *
     * @param   string          $property   The property key
     * @param   AbstractModel   $model      The model to set.
     * @return  self
     */
    protected function set($property, AbstractModel $model)
    {
        $key = $model->getCompositeKey();
        if (!isset($this->{$property}[$key])) {
            $this->{$property}[$key] = $model;

            if ('current' === $property) {
                $this->totalCount++;
            }
        }
        return $this;
    }

    /**
     * Sets an array of models to the collection.
     *
     * @param   AbstractModel[]     $models
     * @return  self
     */
    protected function setModels(array $models)
    {
        $this->current  = [];
        $this->original = [];
        foreach ($models as $model) {
            $this->add($model);
        }
        return $this;
    }

    /**
     * Proxies models from the initial identifier set, if applicable.
     *
     * @abstract
     * @return  self
     */
    abstract protected function proxy();

    /**
     * Loads the collection from the persistence layer, if necessary.
     *
     * @abstract
     * @return  self
     */
    abstract protected function touch();

    /**
     * Validates that the collection supports the incoming model.
     *
     * @abstract
     * @param   AbstractModel   $model  The model to validate.
     * @throws  \InvalidArgumentException
     */
    abstract protected function validateAdd(AbstractModel $model);
}
