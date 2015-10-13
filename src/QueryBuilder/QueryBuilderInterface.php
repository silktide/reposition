<?php

namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\Metadata\EntityMetadata;

/**
 *
 */
interface QueryBuilderInterface
{

    const PRIMARY_KEY = "id";

    /**
     * @param EntityMetadata $entity
     * @return TokenSequencerInterface
     */
    public function find(EntityMetadata $entity);

    /**
     * @param EntityMetadata $entity
     * @return TokenSequencerInterface
     */
    public function update(EntityMetadata $entity);

    /**
     * @param EntityMetadata $entity
     * @return TokenSequencerInterface
     */
    public function save(EntityMetadata $entity);

    /**
     * @param EntityMetadata $entity
     * @return TokenSequencerInterface
     */
    public function delete(EntityMetadata $entity);

} 