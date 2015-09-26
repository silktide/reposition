<?php

namespace Silktide\Reposition\Storage;

use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;

/**
 *
 */
interface StorageInterface
{

    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder();

    /**
     * @param TokenSequencerInterface $query
     * @param string $entityClass
     * @return object
     */
    public function query(TokenSequencerInterface $query, $entityClass);

} 