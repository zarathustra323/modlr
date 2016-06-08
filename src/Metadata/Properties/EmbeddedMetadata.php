<?php

namespace As3\Modlr\Metadata\Properties;

use As3\Modlr\Exception\MetadataException;
use As3\Modlr\Metadata\EmbedMetadata;

/**
 * Defines metadata for an embedded property.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class EmbeddedMetadata extends PropertyMetadata
{
    /**
     * READ-ONLY.
     * The embedded metadata for this embedded property.
     *
     * @var EmbedMetadata
     */
    public $embedMeta;

    /**
     * READ-ONLY.
     * The embed type: one or many
     *
     * @var string
     */
    public $embedType;

    /**
     * Constructor.
     *
     * @param   string          $key
     * @param   string          $embedType
     * @param   EmbedMetadata   $embedMeta
     * @param   bool            $mixin
     */
    public function __construct($key, $embedType, EmbedMetadata $embedMeta, $mixin = false)
    {
        $this->embedMeta = $embedMeta;
        $this->setEmbedType($embedType);
        parent::__construct($key, $mixin);
    }

    /**
     * Gets the embed metadata instance.
     *
     * @return  EmbedMetadata
     */
    public function getEmbedMetadata()
    {
        return $this->embedMeta;
    }

    /**
     * Gets the embed type: one or many.
     *
     * @return  string
     */
    public function getEmbedType()
    {
        return $this->embedType;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return sprintf('embed-%s', $this->embedType);
    }

    /**
     * Determines if this is a many embed.
     *
     * @return bool
     */
    public function isMany()
    {
        return 'many' === $this->embedType;
    }

    /**
     * Determines if this is a one (single) embed.
     *
     * @return  bool
     */
    public function isOne()
    {
        return 'one' === $this->embedType;
    }

    /**
     * Sets the embed type: one or many.
     *
     * @param   string  $embedType
     * @return  self
     */
    public function setEmbedType($embedType)
    {
        $embedType = strtolower($embedType);
        $this->validateType($embedType);
        $this->embedType = $embedType;
        return $this;
    }

    /**
     * Validates the embed type.
     *
     * @param   string  $embedType
     * @return  bool
     * @throws  MetadataException
     */
    protected function validateType($embedType)
    {
        $valid = ['one', 'many'];
        if (!in_array($embedType, $valid)) {
            throw MetadataException::invalidRelType($embedType, $valid);
        }
        return true;
    }
}
