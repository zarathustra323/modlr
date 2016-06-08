<?php

namespace As3\Modlr\Metadata\Interfaces;

/**
 * Defines the storage layer metadata for a model.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 * Contains information about the database schema, such as db/collection/table names, indexes, etc.
 * Each implementing class must define it's own merging criteria, and handle it's own properties/methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface StorageLayerInterface extends MergeableInterface
{
    /**
     * Returns the unique key for this storage layer metadata.
     *
     * @return  string
     */
    public function getKey();
}
