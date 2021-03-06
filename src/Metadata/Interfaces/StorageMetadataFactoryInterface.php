<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\EntityMetadata;

/**
 * Creates Storage Layer Metadata (persistence/search) instances in the implementing format.
 * Is used by the metadata driver and/or factory for creating new instances, setting values, and performing validation.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface StorageMetadataFactoryInterface
{
    /**
     * Creates a new instance based on a driver mapping.
     *
     * @return  StorageLayerInterface
     */
    public function createInstance(array $mapping);

    /**
     * Handles additional metadata operations on the Factory load.
     *
     * @param   EntityMetadata
     */
    public function handleLoad(EntityMetadata $metadata);

    /**
     * Handles additional validation specific to this storage layaer.
     *
     * @param   EntityMetadata
     * @throws  \As3\Modlr\Exception\MetadataException On invalid metadata.
     */
    public function handleValidate(EntityMetadata $metadata);
}
