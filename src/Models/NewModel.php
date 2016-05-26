<?php

namespace As3\Modlr\Models;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class NewModel
{
    /**
     * The internal model.
     *
     * @var InternalModel
     */
    private $internalModel;

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
        $this->internalModel = new InternalModel($metadata, $identifier, $store, $properties);
    }

    public function get($key)
    {
        return $this->internalModel->get($key);
    }

    /**
     * Gets the internal model.
     * This should only be used by the internals of Modlr, and not by the end user.
     *
     * @return  InternalModel
     */
    public function getInternalModel()
    {
        return $this->internalModel;
    }
}
