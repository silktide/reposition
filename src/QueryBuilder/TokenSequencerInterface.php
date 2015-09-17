<?php

namespace Silktide\Reposition\QueryBuilder;

class TokenSequencerInterface 
{

    const TYPE_EXPRESSION = "expression";
    const TYPE_FIND = "find";
    const TYPE_SAVE = "save";
    const TYPE_UPDATE = "update";
    const TYPE_DELETE = "delete";

    const SORT_ASC = 1;
    const SORT_DESC = -1;

    public function getType();

    public function isQuery();

    public function getCollectionName();

    public function getSequence();

    public function aggregate($type);

    public function where();

    public function sort(array $by);

    public function limit($limit, $offset = null);

    public function group(array $by);

    public function closure($content = null);

    public function notL();

    public function andL();

    public function orL();

    public function ref($field, $alias = "", $type = "field");

    public function op($op);

    public function val($value);

    public function entity($entity);

    public function func($name, array $args = []);

    public function keyword($keyword);

} 