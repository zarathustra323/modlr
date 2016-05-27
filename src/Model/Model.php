<?php

namespace As3\Modlr\Model;

use As3\Modlr\Model\Core\CoreModel;
use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Model
{
    /**
     * The core model.
     *
     * @var CoreModel
     */
    private $coreModel;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   Store           $store
     * @param   array|null      $properties
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, array $properties = null)
    {
        $this->coreModel = new CoreModel($metadata, $identifier, $store, $properties);
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
        return $this->coreModel->get($key);
    }

    /**
     * Gets the core model.
     * This should only be used by the internals of Modlr, and not by the end user.
     *
     * @return  CoreModel
     */
    public function getCoreModel()
    {
        return $this->coreModel;
    }
}
