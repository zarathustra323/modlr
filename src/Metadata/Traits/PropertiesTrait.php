<?php

namespace As3\Modlr\Metadata\Traits;

use As3\Modlr\Metadata\Properties\AttributeMetadata;
use As3\Modlr\Metadata\Properties\PropertyMetadata;

/**
 * Common property metadata methods.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
trait PropertiesTrait
{
    /**
     * READ-ONLY.
     * Properties assigned to this metadata instance.
     *
     * @var PropertyMetadata[]
     */
    public $properties = [];

    /**
     * Adds a property to this instance.
     *
     * @param   PropertyMetadata    $property
     * @return  self
     */
    public function addProperty(PropertyMetadata $property)
    {
        $this->properties[$property->getKey()] = $property;
        ksort($this->properties);
        return $this;
    }

    /**
     * Determines if an attribute supports autocomplete functionality.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    public function attrSupportsAutocomplete($key)
    {
        return isset($this->getAutocompleteAttributes()[$key]);
    }

    /**
     * Gets all attribute properties.
     *
     * @deprecated
     * @return  PropertyMetadata[]
     */
    public function getAttributes()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }
        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $this->isAttribute($key)) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Gets all properties that are flagged for autocomplete in search.
     *
     * @return  PropertyMetadata[]
     */
    public function getAutocompleteAttributes()
    {
        static $attrs;
        if (null !== $attrs) {
            return $attrs;
        }

        $attrs = [];
        foreach ($this->getProperties() as $key => $attribute) {
            if (false === $this->isAttribute($key) && false === $attribute->hasAutocomplete()) {
                continue;
            }
            $attrs[$key] = $attribute;
        }
        return $attrs;
    }

    /**
     * Gets all embed properties.
     *
     * @deprecated
     * @return  PropertyMetadata[]
     */
    public function getEmbeds()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }
        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $this->isEmbed($key)) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Gets all properties that this instance contains.
     *
     * @return  PropertyMetadata[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Gets a property.
     * Returns null if the property does not exist.
     *
     * @param   string  $key
     * @return  PropertyMetadata|null
     */
    public function getProperty($key)
    {
        if (!isset($this->properties[$key])) {
            return null;
        }
        return $this->properties[$key];
    }

    /**
     * Gets all relationship properties.
     *
     * @deprecated
     * @return  PropertyMetadata[]
     */
    public function getRelationships()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }
        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $this->isRelationship($key)) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Gets all properties that are flagged for storage in search.
     *
     * @return  PropertyMetadata[]
     */
    public function getSearchProperties()
    {
        static $props;
        if (null !== $props) {
            return $props;
        }

        $props = [];
        foreach ($this->getProperties() as $key => $property) {
            if (false === $this->isAttribute($key) && false === $property->isSearchProperty()) {
                continue;
            }
            $props[$key] = $property;
        }
        return $props;
    }

    /**
     * Alias for @see isAttribute().
     *
     * @deprecated
     * @param   string  $key
     * @return  bool
     */
    public function hasAttribute($key)
    {
        return $this->isAttribute($key);
    }

    /**
     * Determines any attribute properties exist.
     *
     * @deprecated
     * @return  bool
     */
    public function hasAttributes()
    {
        $props = $this->getAttributes();
        return !empty($props);
    }

    /**
     * Alias for @see isEmbed().
     *
     * @deprecated
     * @param   string  $key
     * @return  bool
     */
    public function hasEmbed($key)
    {
        return $this->isEmbed($key);
    }

    /**
     * Determines any embed properties exist.
     *
     * @deprecated
     * @return  bool
     */
    public function hasEmbeds()
    {
        $props = $this->getEmbeds();
        return !empty($props);
    }

    /**
     * Determines if a property exists.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasProperty($key)
    {
        return isset($this->properties[$key]);
    }

    /**
     * Alias for @see isRelationship().
     *
     * @deprecated
     * @param   string  $key
     * @return  bool
     */
    public function hasRelationship($key)
    {
        return $this->isRelationship($key);
    }

    /**
     * Determines any relationship properties exist.
     *
     * @deprecated
     * @return  bool
     */
    public function hasRelationships()
    {
        $props = $this->getRelationships();
        return !empty($props);
    }

    /**
     * Determines whether any search properties are defined.
     *
     * @return  bool
     */
    public function hasSearchProperties()
    {
        $propertes = $this->getSearchProperties();
        return !empty($propertes);
    }

    /**
     * Determines if the property is an attribute.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isAttribute($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'attribute' === $property->getType();
    }

    /**
     * Determines if the property is an embed.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbed($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'embed-one'  === $property->getType()
            || 'embed-many' === $property->getType()
        ;
    }

    /**
     * Determines if the property is an embed-many.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbedMany($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'embed-many' === $property->getType();
    }

    /**
     * Determines if the property is an embed-one.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isEmbedOne($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'embed-one' === $property->getType();
    }

    /**
     * Determines if the property is a relationship.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationship($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'relationship-one'  === $property->getType()
            || 'relationship-many' === $property->getType()
        ;
    }

    /**
     * Determines if the property is an relationship-many.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationshipMany($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'relationship-many' === $property->getType();
    }

    /**
     * Determines if the property is an relationship-one.
     *
     * @param   string  $key
     * @return  bool
     */
    public function isRelationshipOne($key)
    {
        if (null === $property = $this->getProperty($key)) {
            return false;
        }
        return 'relationship-one' === $property->getType();
    }

    /**
     * Determines if a property (attribute or relationship) is indexed for search.
     *
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function propertySupportsSearch($key)
    {
        return isset($this->getSearchProperties()[$key]);
    }
}
