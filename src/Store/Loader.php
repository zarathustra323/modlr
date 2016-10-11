<?php

namespace As3\Modlr\Store;

use As3\Modlr\Events\EventDispatcher;
use As3\Modlr\Metadata\EmbedMetadata;
use As3\Modlr\Metadata\MetadataFactory;
use As3\Modlr\Metadata\Properties\EmbeddedMetadata;
use As3\Modlr\Metadata\Properties\RelationshipMetadata;
use As3\Modlr\Model\Collections;
use As3\Modlr\Model\Embed;
use As3\Modlr\Model\Model;
use As3\Modlr\Persister\RecordSetInterface;

class Loader
{
    /**
     * Contains all models currently loaded in memory.
     *
     * @var Cache
     */
    private $cache;

    /**
     * The event dispatcher for firing model lifecycle events.
     *
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var MetadataFactory
     */
    private $mf;

    /**
     * Constructor.
     *
     * @param   MetadataFactory     $mf
     * @param   EventDispatcher     $dispatcher
     */
    public function __construct(MetadataFactory $mf, EventDispatcher $dispatcher)
    {
        $this->mf = $mf;
        $this->cache = new Cache();
        $this->dispatcher = $dispatcher;
    }

    /**
     * Loads a has-many embed collection.
     *
     * @param   EmbeddedMetadata    $embeddedMeta
     * @param   array|null          $embedDocs
     * @param   Store               $store
     * @return  Collections\EmbedCollection
     */
    public function createEmbedCollection(EmbeddedMetadata $embeddedMeta, array $embedDocs = null, Store $store)
    {
        $metadata = $embeddedMeta->getEmbedMetadata();
        $embedDocs = $embedDocs ?: [];

        $embeds = [];
        foreach ($embedDocs as $embedDoc) {
            $embeds[] = $this->createEmbedModel($metadata, (array) $embedDoc, $store);
        }
        return new Collections\EmbedCollection($metadata, $store, $embeds);
    }

    /**
     * Creates an Embed model.
     *
     * @param   EmbedMetadata   $embedMeta
     * @param   array           $data
     * @param   Store           $store
     * @param   bool            $new
     * @return  Embed
     */
    public function createEmbedModel(EmbedMetadata $embedMeta, array $data, Store $store, $new = false)
    {
        return new Embed($embedMeta, $store, $data, (boolean) $new);
    }

    /**
     * Creates a model from a persistence layer Record.
     *
     * @param   string  $typeKey    The model type.
     * @param   array   $record     The persistence layer record.
     * @param   Store   $store      The model store.
     * @return  Model
     */
    public function createModel($typeKey, array $record, Store $store)
    {
        $this->mf->validateResourceTypes($typeKey, $record['type']);
        // Must use the type from the record to cover polymorphic models.
        $metadata = $this->mf->getMetadataForType($record['type']);

        if (null !== $model = $this->cache->get($record['type'], $record['identifier'])) {
            if (false === $model->isLoaded()) {
                // Reinitialize the model with the record.
                $model->_getProperties()->reinitialize($record['properties']);
                // @todo Fire lifecycle event here.
            }
            return $model;
        }

        $start = microtime(true);
        $model = new Model($metadata, $record['identifier'], $store, $record['properties']);
        var_dump(round(((microtime(true) - $start) * 1000), 4) . 'ms');

        // $this->dispatchLifecycleEvent(Events::postLoad, $model);

        $this->cache->push($model);
        return $model;
    }

    /**
     * Creates multiple models from persistence layer Records.
     *
     * @param   string              $typeKey    The model type.
     * @param   RecordSetInterface  $records    The persistence layer records.
     * @param   Store               $store      The model store.
     * @return  Model[]
     */
    public function createModels($typeKey, RecordSetInterface $records, Store $store)
    {
        $models = [];
        foreach ($records as $record) {
            $models[] = $this->createModel($typeKey, $record, $store);
        }
        return $models;
    }

    /**
     * Loads a relationship-many model collection.
     *
     * @param   RelationshipMetadata    $relMeta
     * @param   array                   $references
     * @param   Store                   $store
     * @return  Collections\Collection
     */
    public function createModelCollection(RelationshipMetadata $relMeta, array $references, Store $store)
    {
        $metadata = $this->mf->getMetadataForType($relMeta->getModelType());

        $identifiers = [];
        foreach ($references as $ref) {
            if (!isset($ref['id'])) {
                continue;
            }
            $identifiers[] = $ref['id'];
        }
        return new Collections\ModelCollection($metadata, $store, $identifiers, count($identifiers));
    }

    /**
     * Creates a proxy Model.
     * If the Model is already loaded in-memory, will use that instance instead.
     *
     * @return  Model
     */
    public function createProxyModel($modelType, $identifier, Store $store)
    {
        $identifier = $store->convertId($identifier);
        if (true === $this->cache->has($modelType, $identifier)) {
            return $this->cache->get($modelType, $identifier);
        }
        $metadata = $this->mf->getMetadataForType($modelType);
        $model = new Model($metadata, $identifier, $store);

        $this->cache->push($model);
        return $model;
    }

    /**
     * Creates multiple proxy models from a set of identifiers
     *
     * @param   string  $modelType      The model type.
     * @param   array   $identifiers    The persistence layer records.
     * @param   Store   $store          The model store.
     * @return  Model[]
     */
    public function createProxyModels($modelType, array $identifiers, Store $store)
    {
        $models = [];
        foreach ($identifiers as $identifier) {
            $models[] = $this->createProxyModel($modelType, $identifier, $store);
        }
        return $models;
    }

    /**
     * @return  MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->mf;
    }

    /**
     * @return  Cache
     */
    public function getModelCache()
    {
        return $this->cache;
    }
}
