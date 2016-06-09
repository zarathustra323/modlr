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
     * @param   bool                    $new
     */
    public function __construct(ModelMetadataInterface $metadata, $identifier, Store $store, array $properties = null, $new = false)
    {
        $properties = (array) $properties; // Will ensure that embeds are always flagged as loaded.
        parent::__construct($metadata, $store, $properties, $new);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompositeKey()
    {
        return spl_object_hash($this);
    }
}