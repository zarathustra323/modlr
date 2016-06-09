<?php

namespace As3\Modlr\Model;

use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Model extends AbstractModel
{
    /**
     * The model identifier.
     *
     * @var string
     */
    private $identifier;

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
        $this->identifier = $identifier;
        parent::__construct($metadata, $store, $properties, $new);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompositeKey()
    {
        return sprintf('%s.%s', $this->getType(), $this->getId());
    }

    /**
     * Gets the unique identifier of this model.
     *
     * @api
     * @return  string
     */
    public function getId()
    {
        return $this->identifier;
    }
}
