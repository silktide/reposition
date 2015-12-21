<?php

namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\QueryBuilder\QueryToken\TokenFactory;
use Silktide\Reposition\QueryBuilder\QueryToken\Token;
use Silktide\Reposition\Exception\TokenParseException;
use Silktide\Reposition\Metadata\EntityMetadata;

class TokenSequencer implements TokenSequencerInterface
{

    protected $tokenFactory;

    protected $type;

    protected $entityMetadata;

    protected $options;

    protected $includes = [];

    protected $querySequence = [];

    protected $joinedTables = [];

    public function __construct(TokenFactory $tokenFactory, $type = self::TYPE_EXPRESSION, EntityMetadata $entityMetadata = null, array $options = [])
    {
        $this->tokenFactory = $tokenFactory;
        $this->setType($type);
        $this->entityMetadata = $entityMetadata;
        $this->options = $options;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getOptions()
    {
        return $this->options;
    }

    protected function setType($type)
    {
        switch ($type) {
            case self::TYPE_EXPRESSION:
            case self::TYPE_FIND:
            case self::TYPE_SAVE:
            case self::TYPE_UPDATE:
            case self::TYPE_DELETE:
                $this->type = $type;
                break;
            default:
                throw new \InvalidArgumentException("Cannot create a QueryBuilder with the type '$type'");
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function isQuery()
    {
        return ($this->type != self::TYPE_EXPRESSION && !empty($this->entityMetadata));
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityMetadata->getEntity();
    }

    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    public function getIncludes()
    {
        return $this->includes;
    }

    public function getSequence()
    {
        return $this->querySequence;
    }

    public function resetSequence()
    {
        reset($this->querySequence);
    }

    public function getNextToken()
    {
        // get the current token
        $token = current($this->querySequence);
        // advance the array pointer ready for the next call
        next($this->querySequence);
        // return the current token
        return $token;
    }

    protected function addToSequence(Token $token)
    {
        $this->querySequence[] = $token;
    }

    protected function addNewToSequence($type, $value = null, $alias = null)
    {
        $this->querySequence[] = $this->tokenFactory->create($type, $value, $alias);
    }

    protected function mergeSequence(array $sequence)
    {
        $this->querySequence = array_merge($this->querySequence, $sequence);
    }

    protected function addMixedContentToSequence($content)
    {
        if ($content instanceof TokenSequencer) {
            $this->closure($content);
        } elseif ($content instanceof Token) {
            $this->addToSequence($content);
        } else {
            $this->val($content);
        }
    }

    ////////// QUERY SECTION METHODS //////////

    public function aggregate($type)
    {
        $values = func_get_args();
        // already have type so remove that from the array
        array_shift($values);

        $type = strtolower($type);

        switch ($type) {
            case "count":
            case "total":
            case "maximum":
            case "mininum":
            case "average":
                $this->func($type, $values);
                break;
            default:
                throw new \InvalidArgumentException("The aggregate function '$type' is invalid");
        }
        return $this;
    }

    public function includeEntity(EntityMetadata $childMetadata, $collectionAlias = "", $parent = "", TokenSequencerInterface $additionalFilters = null)
    {
        // check we're dealing with a query
        if (!$this->isQuery()) {
            throw new TokenParseException("Cannot include an entity on an expression sequence.");
        }

        // get data on the child entity
        $childEntity = $childMetadata->getEntity();
        $childCollection = $childMetadata->getCollection();
        $childAlias = empty($collectionAlias)? $childCollection: $collectionAlias;

        // determine which entity to join on (the parent)
        $parentMetadata = $this->entityMetadata;
        if (!empty($parent)) {
            if (empty($this->includes[$parent])) {
                throw new TokenParseException("Cannot include the entity '$childEntity'. The parent entity '$parent' has not yet been included");
            }
            /** @var EntityMetadata $parentMetadata */
            $parentMetadata = $this->includes[$parent];
            if ($parent == $parentMetadata->getEntity()) {
                $parent = $parentMetadata->getCollection();
            }
        }

        // make sure we have a parent that we can use as a table name or alias
        if (empty($parent) || $parent == $parentMetadata->getEntity()) {
            $parent = $parentMetadata->getCollection();
        }

        // get the relationship between the child and parent
        $relationshipAlias = empty($collectionAlias)? $childEntity: $collectionAlias;
        $relationship = $parentMetadata->getRelationship($relationshipAlias);
        if (empty($relationship)) {
            throw new TokenParseException("The parent entity '{$parentMetadata->getEntity()}' has no relationship defined for '$relationshipAlias'");
        }

        // find the fields we will need to use in the join condition
        // we need the primary key and/or the parent field defined in the relationship
        $parentKey = $parentMetadata->getPrimaryKey();
        $parentField = $parent . "." . (
                empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD])
                ? $parentKey
                : $relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD]
            );

        // we need the primary key and/or the child field defined in the relationship
        $childKey = $childMetadata->getPrimaryKey();
        $childField = $childAlias . "." . (
            empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD])
                ? $childKey
                : $relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD]
            );

        // many to many relationships require an extra join, so treat them differently
        if ($relationship[EntityMetadata::METADATA_RELATIONSHIP_TYPE] != EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY) {
            // create the join condition
            $onClause = new TokenSequencer($this->tokenFactory);
            $onClause->ref($parentField)->op("=")->ref($childField);
            // if we have additional join filters, add them now
            if (!empty($additionalFilters)) {
                $onClause->andL()->mergeSequence($additionalFilters->getSequence());
            }
            // create the join
            $this->join($childCollection, $onClause, $collectionAlias);

        } else {
            // many to many. Requires intermediary join table
            $joinTable = $relationship[EntityMetadata::METADATA_RELATIONSHIP_JOIN_TABLE];
            // create the join condition from the parent to the intermediate
            $parentToMany = new TokenSequencer($this->tokenFactory);
            $parentToMany->ref($parentField)->op("=")->ref("{$joinTable}.{$parent}_id");

            // create the join condition from the intermediate to the child, including any additional join filters
            $manyToChild = new TokenSequencer($this->tokenFactory);
            $manyToChild->ref("{$joinTable}.{$childAlias}_id")->op("=")->ref($childField);
            if (!empty($additionalFilters)) {
                $manyToChild->andL()->mergeSequence($additionalFilters->getSequence());
            }

            // create the joins
            $this
                ->join($joinTable, $parentToMany)
                ->join($childCollection, $manyToChild, $collectionAlias);
        }

        // add the include
        $this->includes[$childAlias] = $childMetadata;
        return $this;
    }

    public function join($collection, TokenSequencerInterface $on, $collectionAlias = "", $type = self::JOIN_LEFT)
    {
        // check for collisions
        $alias = empty($collectionAlias)? $collection: $collectionAlias;
        if (!empty($this->joinedTables[$alias])) {
            throw new TokenParseException("The collection '$alias' has already been joined");
        }

        // validate join type
        switch ($type) {
            case "":
                $type = self::JOIN_INNER;
                // no break
            case self::JOIN_INNER:
            case self::JOIN_LEFT:
            case self::JOIN_RIGHT:
            case self::JOIN_FULL:
                // direction is fine
                break;
            default:
                throw new TokenParseException(
                    "Unsupported join type: '$type'. Join type must be '" .
                    self::JOIN_INNER . "' (default), '" .
                    self::JOIN_LEFT . "', '" .
                    self::JOIN_RIGHT . "' or '" .
                    self::JOIN_FULL . "'"
                );
        }


        $this->addNewToSequence($type);
        if ($type == self::JOIN_FULL) {
            $this->addNewToSequence("outer");
        }
        $this->addNewToSequence("join");
        $this->addNewToSequence("collection", $collection, $collectionAlias);
        $this->addNewToSequence("on");
        $this->closure($on);

        $this->joinedTables[$alias] = true;

        return $this;
    }

    public function where()
    {
        $this->addNewToSequence("where");
        return $this;
    }

    public function sort(array $by)
    {
        $this->addNewToSequence("sort");
        foreach ($by as $ref => $direction) {
            $this->ref($ref);
            $this->addNewToSequence("sort-direction", ($direction == self::SORT_DESC)? $direction: self::SORT_ASC );
        }
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            throw new \InvalidArgumentException("Cannot have a limit less than 1");
        }
        $this->addNewToSequence("limit");
        $this->addNewToSequence("integer", $limit);
        if (!is_null($offset)) {
            $offset = (int) $offset;
            if ($limit < 0) {
                throw new \InvalidArgumentException("Cannot have an offset less than 0");
            }
            $this->addNewToSequence("offset");
            $this->addNewToSequence("integer", $offset);
        }
        return $this;
    }

    public function group(array $by)
    {
        $this->addNewToSequence("group");
        foreach ($by as $ref) {
            $this->ref($ref);
        }
        return $this;
    }

    ////////// LOGIC METHODS //////////
    // suffixed with 'L' because we can't use keywords as method names in PHP 5.5

    public function notL()
    {
        $this->addNewToSequence("not");
        return $this;
    }

    public function andL()
    {
        $this->addNewToSequence("and");
        return $this;
    }

    public function orL()
    {
        $this->addNewToSequence("or");
        return $this;
    }

    /**
     * wraps the following expression in parentheses to isolate it
     * @param TokenSequencer|Token|mixed $content
     * @return $this
     */
    public function closure($content = null)
    {
        $this->addNewToSequence("open");

        if (!empty($content)) {
            if ($content instanceof TokenSequencer) {
                $this->mergeSequence($content->getSequence());
            } elseif ($content instanceOf Token) {
                $this->addToSequence($content);
            } elseif (is_array($content)) {
                foreach ($content as $subContent) {
                    $this->addMixedContentToSequence($subContent);
                }
            } else {
                $this->val($content);
            }
        }

        $this->addNewToSequence("close");

        return $this;
    }

    ////////// EXPRESSION METHODS //////////

    public function ref($name, $alias = "", $type = "field")
    {
        $this->addNewToSequence($type, $name, $alias);
        return $this;
    }

    public function op($value)
    {
        $this->addNewToSequence("operator", strtolower($value));
        return $this;
    }

    public function val($value)
    {
        $type = strtolower(gettype($value));

        $this->addNewToSequence($type, $value);
        return $this;
    }

    public function entity($entity)
    {
        $this->addNewToSequence("entity", $entity);
        return $this;
    }

    public function func($name, array $args = [])
    {
        $this->addNewToSequence("function", $name);
        $this->closure($args);
        return $this;
    }

    public function keyword($keyword)
    {
        $this->addNewToSequence($keyword);
        return $this;
    }
} 