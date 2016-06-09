<?php

namespace As3\Modlr\Metadata\Properties;

use As3\Modlr\Exception\MetadataException;

/**
 * Defines metadata for a relationship property.
 * Should be loaded using the MetadataFactory, not instantiated directly.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class RelationshipMetadata extends PropertyMetadata
{
    /**
     * READ-ONLY.
     * The model type this is related to.
     *
     * @var string
     */
    public $entityType;

    /**
     * READ-ONLY.
     * Determines if this is an inverse (non-owning) relationship.
     *
     * @var bool
     */
    public $isInverse = false;

    /**
     * READ-ONLY.
     * The inverse field.
     *
     * @var bool
     */
    public $inverseField;

    /**
     * READ-ONLY.
     * Child model types the related model owns.
     * Only used for polymorphic relationships.
     */
    public $ownedTypes = [];

    /**
     * READ-ONLY.
     * Determines if the related model is polymorphic.
     * Hence determines if this is a polymorphic relationship.
     *
     * @var bool
     */
    public $polymorphic = false;

    /**
     * READ-ONLY.
     * The relationship type: one or many
     *
     * @var string
     */
    public $relType;

    /**
     * Constructor.
     *
     * @param   string  $key        The relationship property key.
     * @param   string  $relType    The relationship type.
     * @param   string  $modelType  The model type key.
     * @param   bool    $mixin
     */
    public function __construct($key, $relType, $modelType, $mixin = false)
    {
        parent::__construct($key, $mixin);
        $this->setRelType($relType);
        $this->entityType = $modelType;
    }

    /**
     * Is an alias for @see getModelType().
     *
     * @deprecated
     * @return  string
     */
    public function getEntityType()
    {
        return $this->getModelType();
    }

    /**
     * Gets the model type that this property is related to.
     *
     * @return  string
     */
    public function getModelType()
    {
        return $this->entityType;
    }

    /**
     * Gets the relationship type.
     *
     * @return  string
     */
    public function getRelType()
    {
        return $this->relType;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return sprintf('relationship-%s', $this->getRelType());
    }

    /**
     * Determines if this is an inverse (non-owning) relationship.
     *
     * @return  bool
     */
    public function isInverse()
    {
        return $this->isInverse;
    }

    /**
     * Determines if this is a has-many relationship.
     *
     * @return bool
     */
    public function isMany()
    {
        return 'many' === $this->getRelType();
    }

    /**
     * Determines if this is a has-one relationship.
     *
     * @return  bool
     */
    public function isOne()
    {
        return 'one' === $this->getRelType();
    }

    /**
     * Determines whether the relationship is polymorphic.
     *
     * @return  bool
     */
    public function isPolymorphic()
    {
        return $this->polymorphic;
    }

    /**
     * Flags the relationship as polymorphic.
     *
     * @param   bool    $bit
     * @return  self
     */
    public function setPolymorphic($bit = true)
    {
        $this->polymorphic = (Boolean) $bit;
        return $this;
    }

    /**
     * Sets the relationship type: one or many.
     *
     * @param   string  $relType
     * @return  self
     */
    public function setRelType($relType)
    {
        $relType = strtolower($relType);
        $this->validateType($relType);
        $this->relType = $relType;
        return $this;
    }

    /**
     * Validates the relationship type.
     *
     * @param   string  $relType
     * @return  bool
     * @throws  MetadataException
     */
    protected function validateType($relType)
    {
        $valid = ['one', 'many'];
        if (!in_array($relType, $valid)) {
            throw MetadataException::invalidRelType($relType, $valid);
        }
        return true;
    }
}
