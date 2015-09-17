<?php

namespace Silktide\Reposition\QueryBuilder\QueryToken;

class Entity extends Token
{
    protected $type = "entity";

    protected $entity;

    public function __construct($entity)
    {
        $this->setEntity($entity);
    }

    protected function setEntity($entity)
    {
        if (!is_object($entity) && !is_array($entity)) {
            throw new \InvalidArgumentException("Entity must be an object or an array representing an object. Found " . gettype($entity));
        }

        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
} 