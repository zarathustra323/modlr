<?php

namespace As3\Modlr\Model\Collections;

use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Model\AbstractModel;
use As3\Modlr\Model\Embed;
use As3\Modlr\Store\Store;

/**
 * Model collection that contains embedded fragments from a persistence (database) layer.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbedCollection extends AbstractCollection
{
    /**
     * Constructor.
     *
     * @param   EmbedMetadata   $metadata
     * @param   Store           $store
     * @param   AbstractModel[] $models
     */
    public function __construct(EmbedMetadata $metadata, Store $store, array $models = [])
    {
        parent::__construct($metadata, $store, count($models));
        $this->loaded = true;
        $this->setModels($models);

    }

    /**
     * Creates a new Embed model instance based on the collection
     *
     * @return  Embed
     */
    public function createNewEmbed()
    {
        $embed = $this->store->_getLoader()->createEmbedModel($this->metadata, [], $this->store, true);
        return $embed;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirty()
    {
        if (true === $this->hasDirtyModels()) {
            return true;
        }
        return parent::isDirty();
    }

    /**
     * {@inheritdoc}
     */
    protected function touch($force = false)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAdd(AbstractModel $model)
    {
        if (!$model instanceof Embed) {
            throw new \InvalidArgumentExcepton('The model must be an instanceof of Embed');
        }
        $this->store->validateEmbedSet($this->metadata, $model->getType());
    }
}
