<?php

namespace As3\Modlr\Model;

use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Embed extends AbstractModel
{
    /**
     * Constructor.
     *
     * @param   ModelMetadataInterface  $metadata
     * @param   string                  $identifier
     * @param   Store                   $store
     * @param   array|null              $properties
     */
    public function __construct(ModelMetadataInterface $metadata, $identifier, Store $store, array $properties = null)
    {
        parent::__construct($metadata, $store, $properties);
        // $this->setLoaded();
    }

    /**
     * {@inheritdoc}
     */
    public function getCompositeKey()
    {
        return spl_object_hash($this);
    }
}
