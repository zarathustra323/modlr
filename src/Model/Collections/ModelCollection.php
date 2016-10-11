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
    protected $references;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   Store           $store
     * @param   array           $references
     * @param   int             $totalCount
     * @param   bool            $loaded
     */
    public function __construct(EntityMetadata $metadata, Store $store, array $references, $totalCount)
    {
        $this->references = $references;
        parent::__construct($metadata, $store, $totalCount);
    }

    /**
     * {@inheritdoc}
     */
    protected function proxy()
    {
        if (true === $this->isLoaded() || true === $this->isProxied()) {
            return $this;
        }

        $this->proxied = true;

        $models = $this->store->_getLoader()->createProxyModels($this->references, $this->store);
        $this->setModels($models);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function touch()
    {
        if (true === $this->isLoaded()) {
            return $this;
        }

        $this->proxy();
        $this->loaded = true;

        $identifiers = [];
        foreach ($this->original as $model) {
            if (false === $model->isLoaded()) {
                $identifiers[] = $model->getId();
            }
        }

        if (empty($identifiers)) {
            return $this;
        }

        $records = $this->store->retrieveRecords($this->getType(), $identifiers);
        $models = $this->store->_getLoader()->createModels($this->getType(), $records, $this->store);
        $this->setModels($models);

        return $this;
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
}
