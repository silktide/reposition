<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Query;

/**
 *
 */
abstract class Query
{

    const ACTION_FIND = "find";
    const ACTION_INSERT = "insert";
    const ACTION_UPDATE = "update";
    const ACTION_DELETE = "delete";
    const ACTION_AGGREGATE = "aggregate";

    const SORT_ASCENDING = "asc";
    const SORT_DESCENDING = "desc";

    protected $action;

    protected $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getAction()
    {
        return $this->action;
    }

} 