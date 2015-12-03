<?php

namespace Silktide\Reposition\Collection;
use Silktide\Reposition\Exception\CollectionException;

/**
 * Collection
 */
class Collection implements \Iterator
{

    protected $entities = [];

    protected $added = [];

    protected $removed = [];

    protected $trackChanges = false;

    public function __construct(array $entities = [])
    {
        $this->entities = $entities;
    }

    public function add($entity)
    {
        // check this isn't in the array already
        $index = array_search($entity, $this->entities);
        if ($index !== false) {
            return;
        }

        $this->entities[] = $entity;
        $this->track("add", $entity);
    }

    public function remove($entity)
    {
        $index = array_search($entity, $this->entities);
        // if we didn't find an entity, no need to continue
        if ($index === false) {
            return;
        }
        unset($this->entities[$index]);
        $this->track("remove", $entity);
    }

    public function removeBy($identifier, $value)
    {
        $getter = null;
        foreach ($this->entities as $i => $entity) {
            if (empty($getter)) {
                // check if property is actually a getter
                if (method_exists($entity, $identifier)) {
                    $getter = $identifier;
                } else {
                    // otherwise, generate the getter and check it exists
                    $getter = "get" . ucfirst($identifier);
                    if (!method_exists($entity, $getter)) {
                        throw new CollectionException("No method exists for '$identifier' or '$getter' on this entity");
                    }
                }
            }
            if ($entity->{$getter}() == $value) {
                unset($this->entities[$i]);
                $this->track("remove", $entity);
            }
        }
    }

    public function clear()
    {
        if ($this->trackChanges) {
            $this->removed = array_merge($this->removed, $this->entities);
        }
        $this->entities = [];
    }

    public function setChangeTracking($track = true)
    {
        $this->trackChanges = (bool) $track;
    }

    public function getAddedEntities()
    {
        return $this->added;
    }

    public function getRemovedEntities()
    {
        return $this->removed;
    }

    public function hasBeenChanged()
    {
        return !empty($this->added) || !empty($this->removed);
    }

    public function toArray($toArrayEntities = true)
    {
        $return = [];
        $methodExists = null;
        foreach ($this->entities as $entity) {
            if ($methodExists == null) {
                $methodExists = method_exists($entity, "toArray");
            }
            $return[] = $toArrayEntities && $methodExists
                ? $entity->toArray()
                : $entity;
        }
        return $return;
    }

    public function count()
    {
        return count($this->entities);
    }

    // Iterator methods

    public function current()
    {
        return current($this->entities);
    }

    public function next()
    {
        next($this->entities);
    }

    public function key()
    {
        return key($this->entities);
    }

    public function valid()
    {
        return (key($this->entities) !== null);
    }

    public function rewind()
    {
        reset($this->entities);
    }

    protected function track($action, $entity)
    {
        if ($this->trackChanges) {
            switch ($action) {
                case "add":
                    $this->dedupeTrackingArrays("added", "removed", $entity);
                    break;
                case "remove":
                    $this->dedupeTrackingArrays("removed", "added", $entity);
                    break;
            }
        }
    }

    protected function dedupeTrackingArrays($one, $two, $entity)
    {
        $index = array_search($entity, $this->{$two});
        if ($index !== false) {
            unset($this->{$two}[$index]);
        } else {
            $this->{$one}[] = $entity;
        }
    }

}