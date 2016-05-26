<?php

namespace As3\Modlr\Models;

use As3\Modlr\Metadata\EntityMetadata;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class InternalModel
{
    /**
     * The model changeset.
     *
     */
    protected $changset;

    /**
     * The model properties.
     *
     * @var array
     */
    protected $properties = [
        'attributes'    => [],
        'hasOne'        => [],
    ];

    /**
     * The id value of this model.
     * Always converted to a string when in the model context.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The metadata that defines this Model.
     *
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * The model state.
     *
     * @var array
     */
    private $state = [
        'empty'     => true,
        'loaded'    => false,
        'dirty'     => false,
        'deleting'  => false,
        'deleted'   => false,
        'new'       => false,
    ];

    /**
     * The Model Store for handling lifecycle operations.
     *
     * @var Store
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param   EntityMetadata  $metadata
     * @param   string          $identifier
     * @param   Store           $store
     * @param   array|null      $properties
     */
    public function __construct(EntityMetadata $metadata, $identifier, Store $store, array $properties = null)
    {
        $this->metadata = $metadata;
        $this->identifier = $identifier;
        $this->store = $store;
        $this->initialize($properties);
    }

    /**
     * Gets a model property.
     * Returns null if the property does not exist on the model or is not set.
     *
     * @api
     * @param   string  $key    The property field key.
     * @return  Model|Model[]|Embed|Collections\EmbedCollection|null|mixed
     */
    public function get($key)
    {
        if (true === $this->isAttribute($key)) {
            return $this->getAttribute($key);
        }
        if (true === $this->isRelationship($key)) {
            return $this->getRelationship($key);
        }
        if (true === $this->isEmbed($key)) {
            return $this->getEmbed($key);
        }
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

    /**
     * Gets the metadata for this model.
     *
     * @api
     * @return  EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Gets the model type.
     *
     * @return  string
     */
    public function getType()
    {
        return $this->metadata->type;
    }

    /**
     * Initializes the model and loads its attributes and relationships.
     *
     * @param   array|null  $properties     The db properties to apply.
     * @return  self
     */
    public function initialize(array $properties = null)
    {
        if (null === $properties) {
            return $this;
        }

        foreach ($properties as $key => $value) {
            if (true === $this->isAttribute($key)) {
                // Load attribute.
                $this->properties['attributes'][$key] = $value;
            } else if (true === $this->isHasOne($key)) {
                // Load hasOne relationship.
                $this->properties['hasOne'][$key] = [ $value['type'], $value['id'] ];
                continue;
            }

            // } else if (true === $this->isEmbedHasOne($key) && is_array($value)) {
            //     // Load embed one.
            //     $embedOne[$key] = $this->getStore()->loadEmbed($this->getMetadata()->getEmbed($key)->embedMeta, $value);
            // }
        }
        $this->setLoaded();
        return $this;
    }

    /**
     * Determines if a property key is an attribute.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isAttribute($key)
    {
        return $this->metadata->hasAttribute($key);
    }

    /**
     * Determines if an attribute key is calculated.
     *
     * @param   string  $key    The attribute key.
     * @return  bool
     */
    public function isCalculatedAttribute($key)
    {
        if (false === $this->isAttribute($key)) {
            return false;
        }
        return $this->metadata->getAttribute($key)->isCalculated();
    }

    /**
     * Determines if a property key is a has-one relationship.
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isHasOne($key)
    {
        if (false === $this->isRelationship($key)) {
            return false;
        }
        return $this->metadata->getRelationship($key)->isOne();
    }

    /**
     * Determines if a property key is a relationship (either has-one or has-many).
     *
     * @api
     * @param   string  $key    The property key.
     * @return  bool
     */
    public function isRelationship($key)
    {
        return $this->metadata->hasRelationship($key);
    }

    /**
     * Determines if the model is in a certain state.
     *
     * @param   string  $status
     * @return  bool
     */
    public function stateIs($status)
    {
        return $this->state[$status];
    }

    /**
     * Converts an attribute value to the appropriate data type.
     *
     * @param   string|null $key
     * @param   mixed       $value
     * @return  mixed
     */
    private function convertAttributeValue($key, $value)
    {
        $dataType = $this->getDataType($key);
        if (null === $dataType) {
            return $value;
        }
        return $this->store->convertAttributeValue($dataType, $value);
    }

    /**
     * Gets an attribute value.
     *
     * @param   string  $key    The attribute key (field) name.
     * @return  mixed
     */
    private function getAttribute($key)
    {
        if (true === $this->isCalculatedAttribute($key)) {
            $value = $this->getCalculatedAttribute($key);
        } else {
            $this->touch();
            if (isset($this->properties['attributes'][$key])) {
                $value = $this->properties['attributes'][$key];
            } else {
                $attrMeta = $this->metadata->getAttribute($key);
                $value = (isset($attrMeta->defaultValue)) ? $attrMeta->defaultValue : null;
            }
        }
        return $this->convertAttributeValue($key, $value);
    }

    /**
     * Gets a calculated attribute value.
     *
     * @param   string  $key    The attribute key (field) name.
     * @return  mixed
     */
    private function getCalculatedAttribute($key)
    {
        $attrMeta = $this->metadata->getAttribute($key);
        $class  = $attrMeta->calculated['class'];
        $method = $attrMeta->calculated['method'];

        return $class::$method($this);
    }

    /**
     * Gets a data type for an attribute key.
     *
     * @param   string  $key The attribute key.
     * @return  string|null
     */
    private function getDataType($key)
    {
        $meta = $this->metadata->getAttribute($key);
        if (null === $meta) {
            return;
        }
        return $meta->dataType;
    }

    /**
     * Gets a relationship value.
     *
     * @param   string  $key    The relationship key (field) name.
     * @return  Model|array|null
     * @throws  \RuntimeException If hasMany relationships are accessed directly.
     */
    private function getRelationship($key)
    {
        if (true === $this->isHasOne($key)) {
            $this->touch();

            if (!isset($this->properties['hasOne'][$key])) {
                return;
            }

            $value = $this->properties['hasOne'][$key];
            if (!$value instanceof Model) {
                $value = $this->store->loadProxyModel($value[0], $value[1]);
                $this->properties['hasOne'][$key] = $value;
            }
            return $value;
        }
        throw new \BadMethodCallException('Getting a hasMany relationship is NYI.');

        // if (true === $this->isHasMany($key)) {
        //     $this->touch();
        //     $collection = $this->hasManyRelationships->get($key);
        //     if ($collection->isLoaded($collection)) {
        //         return iterator_to_array($collection);
        //     }
        //     return (true === $this->collectionAutoInit) ? iterator_to_array($collection) : $collection->allWithoutLoad();
        // }
        return null;
    }

    /**
     * Sets the model state to loaded.
     *
     * @return  self
     */
    private function setLoaded()
    {
        $this->state['empty'] = false;
        $this->state['loaded'] = true;
        return $this;
    }

    /**
     * Touches the model and loads from the db.
     *
     * @param   bool    $force  Whether to force the load, even if the model is currently loaded.
     * @return  self
     */
    private function touch($force = false)
    {
        if (true === $this->state['deleted']) {
            return $this;
        }
        if (true === $this->state['empty'] || true === $force) {
            $record = $this->store->retrieveRecord($this->getType(), $this->getId());
            $this->initialize($record['properties']);
        }
        return $this;
    }
}
