<?php

namespace As3\Modlr\Model\Core;

use \ArrayIterator;
use \Closure;

/**
 * Handles iteration of model collections.
 *
 * @author Jacob Bare <jacob.bare@gmail.com>
 */
class CollectionIterator extends ArrayIterator
{
    /**
     * @var Closure|null
     */
    private $loader;

    /**
     * @param   array           $array
     * @param   Closure|null    $loader
     * @param   int             $flags
     */
    public function __construct(array $array = [], Closure $loader = null, $flags = 0)
    {
        $this->loader = $loader;
        parent::__construct($array, $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $model = parent::current();
        if (null === $this->loader || true === $model->isLoaded()) {
            return $model;
        }
        $model->_setCollectionLoader($this->loader);
        return $model;
    }
}
