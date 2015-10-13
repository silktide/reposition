<?php

namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\QueryBuilder\QueryToken\Token;
use Silktide\Reposition\Metadata\EntityMetadata;

interface TokenSequencerInterface
{

    const TYPE_EXPRESSION = "expression";
    const TYPE_FIND = "find";
    const TYPE_SAVE = "save";
    const TYPE_UPDATE = "update";
    const TYPE_DELETE = "delete";

    const SORT_ASC = 1;
    const SORT_DESC = -1;

    const JOIN_INNER = "inner";
    const JOIN_LEFT = "left";
    const JOIN_RIGHT = "right";
    const JOIN_FULL = "full";

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function isQuery();

    /**
     * @return string
     */
    public function getEntityName();

    /**
     * @return array
     */
    public function getIncludes();

    /**
     * @return array
     */
    public function getSequence();

    /**
     * Returns the next token in the sequence or false if the sequence has ended
     *
     * @return Token|bool
     */
    public function getNextToken();

    /**
     * @param string $type
     *
     * @return TokenSequencerInterface
     */
    public function aggregate($type);

    /**
     * @param EntityMetadata $childMetadata
     * @param string $collectionAlias
     * @param string $parent
     * @param TokenSequencerInterface $additionalFilters
     *
     * @return TokenSequencerInterface
     */
    public function includeEntity(EntityMetadata $childMetadata, $collectionAlias = "", $parent = "", TokenSequencerInterface $additionalFilters = null);

    /**
     * @param string $collection - collection to join with
     * @param TokenSequencerInterface $on - conditions to join on
     * @param string $collectionAlias
     * @param string $type - "left", "right", "full" or "inner" (empty = "inner")
     *
     * @return TokenSequencerInterface
     */
    public function join($collection, TokenSequencerInterface $on, $collectionAlias = "", $type = self::JOIN_LEFT);

    /**
     * @return TokenSequencerInterface
     */
    public function where();

    /**
     * @param array $by
     * @return TokenSequencerInterface
     */
    public function sort(array $by);

    /**
     * @param int $limit
     * @param int $offset
     * @return TokenSequencerInterface
     */
    public function limit($limit, $offset = null);

    /**
     * @param array $by
     * @return TokenSequencerInterface
     */
    public function group(array $by);

    /**
     * @param mixed $content
     */
    public function closure($content = null);

    /**
     * @return TokenSequencerInterface
     */
    public function notL();

    /**
     * @return TokenSequencerInterface
     */
    public function andL();

    /**
     * @return TokenSequencerInterface
     */
    public function orL();

    /**
     * @param string $field
     * @param string $alias
     * @param string $type - 'field' or 'table'
     * @return TokenSequencerInterface
     */
    public function ref($field, $alias = "", $type = "field");

    /**
     * @param string $op
     * @return TokenSequencerInterface
     */
    public function op($op);

    /**
     * @param mixed $value
     * @return TokenSequencerInterface
     */
    public function val($value);

    /**
     * @param object|array $entity
     * @return TokenSequencerInterface
     */
    public function entity($entity);

    /**
     * @param string $name
     * @param array $args
     * @return TokenSequencerInterface
     */
    public function func($name, array $args = []);

    /**
     * @param $keyword
     * @return TokenSequencerInterface
     */
    public function keyword($keyword);

} 