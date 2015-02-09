<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Query;

/**
 *
 */
class AggregationQuery extends Query
{

    protected $action = self::ACTION_AGGREGATE;

    protected $operations = [];

    protected $filters = [];

    protected $modifiers = [];

    /**
     * @param array $operations
     */
    public function setOperations(array $operations)
    {
        $this->operastions = $operations;
    }

    /**
     * @return array
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers)
    {
        $this->modifiers = $modifiers;
    }

    /**
     * @return array
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }



} 