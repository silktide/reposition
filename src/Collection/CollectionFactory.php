<?php

namespace Silktide\Reposition\Collection;

/**
 * CollectionFactory
 */
class CollectionFactory
{

    public function create(array $entities = [], $entityIdGetter = "getId")
    {
        return new Collection($entities, $entityIdGetter);
    }

}