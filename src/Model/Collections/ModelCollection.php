<?php

namespace As3\Modlr\Model\Collections;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Model\AbstractModel;
use As3\Modlr\Model\Model;
use As3\Modlr\Persister\RecordSetInterface;
use As3\Modlr\Store\Store;

/**
 * Model collection that contains record representations from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class ModelCollection extends AbstractCollection
{
    /**
     * @var string[]
     */
    protected $identifiers;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   Store           $store
     * @param   AbstractModel[] $models
     * @param   int             $totalCount
     * @param   bool            $loaded
     */
    public function __construct(EntityMetadata $metadata, Store $store, array $identifiers, $totalCount)
    {
        $this->identifiers = $identifiers;
        parent::__construct($metadata, $store, $totalCount);
    }

    /**
     * {@inheritdoc}
     *
     * Overloaded to ensure proxies models are initialized.
     */
    public function allWithoutLoad()
    {
        $this->initializeProxies();
        return parent::allWithoutLoad();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAdd(AbstractModel $model)
    {
        if (!$model instanceof Model) {
            throw new \InvalidArgumentException('The model must be an instanceof of Model');
        }
        $this->store->validateRelationshipSet($this->getMetadata(), $model->getType());
    }

    /**
     * Loads the collection from the persistence layer, if necessary.
     *
     * @abstract
     * @param   bool    $force
     * @return  self
     */
    protected function touch($force = false)
    {
        if (true === $this->isLoaded() && false == $force) {
            return $this;
        }

        $identifiers = $this->getIdentifiers();
        $records = $this->store->retrieveRecords($this->getType(), $identifiers);
        $models = $this->store->_getLoader()->createModels($this->getType(), $records, $this->store);
        $this->setModels($models);
        $this->loaded = true;
        return $this;
    }

    /**
     * Gets the apporpiate indetifiers for querying/loading this collection.
     *
     * @return  string[]
     */
    private function getIdentifiers()
    {
        $this->initializeProxies();
        $identifiers = [];
        foreach ($this->original as $model) {
            if (false === $model->isLoaded()) {
                $identifiers[] = $model->getId();
            }
        }

        return $identifiers;
    }

    /**
     * Initializes proxy models on this collection.
     *
     * @return  self
     */
    private function initializeProxies()
    {
        if (!empty($this->original)) {
            return $this;
        }
        foreach ($this->identifiers as $identifier) {
            $model = $this->store->_getLoader()->createProxyModel($this->getType(), $identifier, $this->store);
            $this->add($model);
        }
        return $this;
    }
}
