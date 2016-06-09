<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\Properties\PropertyMetadata;

/**
 * Interface for standard model metadata instances.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface ModelMetadataInterface extends MetadataInterface, MixinInterface
{
    /**
     * Determines if the model is embedded.
     *
     * @return  bool
     */
    public function isEmbedded();
}
