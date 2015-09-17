<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\Query\FindQuery;
use Silktide\Reposition\Query\InsertQuery;
use Silktide\Reposition\Query\UpdateQuery;
use Silktide\Reposition\Query\DeleteQuery;
use Silktide\Reposition\Query\AggregationQuery;

/**
 *
 */
interface QueryBuilderInterface
{

    const PRIMARY_KEY = "id";

    /**
     * @param string $collection
     * @return TokenSequencerInterface
     */
    public function find($collection);

    /**
     * @param string $collection
     * @return TokenSequencerInterface
     */
    public function update($collection);

    /**
     * @param string $collection
     * @return TokenSequencerInterface
     */
    public function save($collection);

    /**
     * @param string $collection
     * @return TokenSequencerInterface
     */
    public function delete($collection);

} 