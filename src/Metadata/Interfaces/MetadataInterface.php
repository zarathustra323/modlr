<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\Properties\PropertyMetadata;

/**
 * Interface for standard metadata instances
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface MetadataInterface extends MetadataPropertiesInterface
{
    /**
     * Gets the key that uniquely indetifies this metadata instance.
     *
     * @return  string
     */
    public function getKey();
}
