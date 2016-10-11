<?php

namespace As3\Modlr\Model;

use As3\Modlr\Metadata\Interfaces\ModelMetadataInterface;
use As3\Modlr\Metadata\Properties\AttributeMetadata;
use As3\Modlr\Metadata\Properties\EmbeddedMetadata;
use As3\Modlr\Store\Store;

/**
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class Embed extends AbstractModel
{
    /**
     * Constructor.
     *
     * @param   ModelMetadataInterface  $metadata
     * @param   Store                   $store
     * @param   array|null              $properties
     * @param   bool                    $new
     */
    public function __construct(ModelMetadataInterface $metadata, Store $store, array $properties = null, $new = false)
    {
        $properties = (array) $properties; // Will ensure that embeds are always flagged as loaded.
        parent::__construct($metadata, $store, $properties, $new);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompositeKey()
    {
        return spl_object_hash($this);
    }

    /**
     * Generates an identifying hash for the embed.
     *
     * @return  string
     */
    public function getHash()
    {
        $hash = [];
        foreach ($this->properties->getMetadata()->getProperties() as $key => $propMeta) {
            if (true === $propMeta->isAttribute()) {
                $hash[$key] = $this->prepareAttributeForHash($propMeta);
            }

            if (true === $propMeta->isEmbed()) {
                $hash[$key] = $this->prepareEmbedForHash($propMeta);
            }

        }
        ksort($hash);
        return md5(serialize($hash));
    }

    /**
     * Prepares an attriubute value for the embed hash.
     *
     * @param   AttributeMetadata   $attrMeta
     * @return  mixed
     */
    private function prepareAttributeForHash(AttributeMetadata $attrMeta)
    {
        $key   = $attrMeta->getKey();
        $value = $this->get($key);

        if (null === $value) {
            return;
        }
        switch ($attrMeta->dataType) {
            case 'date':
                $value = $value->getTimestamp();
                break;
            case 'object':
                $value = (array) $object;
                ksort($value);
                break;
            case 'mixed':
                $value = serialize($value);
                break;
            case 'array':
                sort($value);
                break;
        }
        return $value;
    }

    /**
     * Prepares an embed value for the embed hash.
     *
     * @param   EmbeddedMetadata    $embedMeta
     * @return  string|null
     */
    private function prepareEmbedForHash(EmbeddedMetadata $embedMeta)
    {
        if (true === $propMeta->isOne()) {
            $embed = $this->get($key);
            return (null === $embed) ? null : $embed->getHash();
        }
        return $this->get($key)->getHash();
    }
}
