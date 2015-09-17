<?php

namespace Silktide\Reposition\QueryBuilder;

/**
 *
 */
class QueryBuilder extends TokenSequencer implements QueryBuilderInterface
{



    ////////// QUERY START METHODS //////////

    /**
     * SELECT, etc...
     *
     * @param $collection
     *
     * @return TokenSequencer
     */
    public function find($collection)
    {
        return new TokenSequencer($this->tokenFactory, self::TYPE_FIND, $collection);
    }

    /**
     * INSERT, straightforward UPDATE
     *
     * @param $collection
     *
     * @return TokenSequencer
     */
    public function save($collection)
    {
        return new TokenSequencer($this->tokenFactory, self::TYPE_SAVE, $collection);
    }

    /**
     * Mass update e.g. UPDATE field = field + 1 WHERE ...
     *
     * @param $collection
     *
     * @return TokenSequencer
     */
    public function update($collection)
    {
        return new TokenSequencer($this->tokenFactory, self::TYPE_UPDATE, $collection);
    }

    /**
     * @param $collection
     *
     * @return TokenSequencer
     */
    public function delete($collection)
    {
        return new TokenSequencer($this->tokenFactory, self::TYPE_DELETE, $collection);
    }

    ////////// OVERRIDE TokenSequencer METHODS TO PREVENT INVALID USAGE //////////

    public function getType()
    {
        throw new \LogicException("No type has been set. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function isQuery()
    {
        throw new \LogicException("Cannot check if this is a query. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function getCollectionName()
    {
        throw new \LogicException("No collection name has been set. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function getSequence()
    {
        throw new \LogicException("Sequence has not ben initialised.");
    }



    public function aggregate($type)
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return call_user_func_array([$sequencer, "aggregate"], func_get_args());
    }

    public function where()
    {
        throw new \LogicException("Cannot use the 'where' method just yet. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function group(array $by)
    {
        throw new \LogicException("Cannot use the 'group' method just yet. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function sort(array $by)
    {
        throw new \LogicException("Cannot use the 'order' method just yet. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function limit($limit, $offset = null)
    {
        throw new \LogicException("Cannot use the 'limit' method just yet. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }



    public function notL()
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->not();
    }

    public function andL()
    {
        throw new \LogicException("Cannot use the 'andL' method just yet.");
    }

    public function orL()
    {
        throw new \LogicException("Cannot use the 'orL' method just yet.");
    }



    public function closure($content = null)
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->closure($content);
    }

    public function ref($name, $alias = "", $type = "field")
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->ref($name, $alias, $type);
    }

    public function op($value)
    {
        throw new \LogicException("Cannot use the 'op' method just yet.");
    }

    public function val($value)
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->val($value);
    }

    public function entity($entity)
    {
        throw new \LogicException("Cannot use the 'entity' method just yet. Use one of the 'find', 'save', 'update' or 'delete' methods first");
    }

    public function func($name, array $args = [])
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->func($name, $args);
    }

    public function keyword($keyword)
    {
        $sequencer = new TokenSequencer($this->tokenFactory);
        return $sequencer->keyword($keyword);
    }

}