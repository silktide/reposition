<?php

namespace Silktide\Reposition\Collection;

/**
 * CollectionFactory
 */
class CollectionFactory
{

    public function create(array $entities = [])
    {
        return new Collection($entities);
    }

}