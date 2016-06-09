<?php

namespace As3\Modlr\Metadata\Interfaces;

use As3\Modlr\Metadata\Properties\PropertyMetadata;

/**
 * Interface for Metadata objects containing properties.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
interface MetadataPropertiesInterface
{
    /**
     * Adds a property to this instance.
     *
     * @param   PropertyMetadata    $property
     * @return  self
     */
    public function addProperty(PropertyMetadata $property);

    /**
     * Determines if an attribute supports autocomplete functionality.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    public function attrSupportsAutocomplete($key);

    /**
     * Gets all properties that are flagged for autocomplete in search.
     *
     * @return  PropertyMetadata[]
     */
    public function getAutocompleteAttributes();

    /**
     * Gets all properties that this instance contains.
     *
     * @return  PropertyMetadata[]
     */
    public function getProperties();

    /**
     * Gets a property.
     * Returns null if the property does not exist.
     *
     * @param   string  $key
     * @return  PropertyMetadata|null
     */
    public function getProperty($key);

    /**
     * Gets all properties that are flagged for storage in search.
     *
     * @return  PropertyMetadata[]
     */
    public function getSearchProperties();

    /**
     * Determines if a property exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasProperty($key);

    /**
     * Determines whether any search properties are defined.
     *
     * @return  bool
     */
    public function hasSearchProperties();

    /**
     * Determines if the property is an attribute.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isAttribute($key);

    /**
     * Determines if the property is an embed.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbed($key);

    /**
     * Determines if the property is an embed-many.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbedMany($key);

    /**
     * Determines if the property is an embed-one.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbedOne($key);

    /**
     * Determines if the property is a relationship.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationship($key);

    /**
     * Determines if the property is an relationship-many.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationshipMany($key);

    /**
     * Determines if the property is an relationship-one.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationshipOne($key);

    /**
     * Determines if a property (attribute or relationship) is indexed for search.
     *
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function propertySupportsSearch($key);
}
