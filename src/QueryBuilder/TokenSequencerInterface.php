<?php

namespace Silktide\Reposition\QueryBuilder;

class TokenSequencerInterface 
{

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