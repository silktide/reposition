<?php

namespace Silktide\Reposition\Storage;

use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;

/**
 *
 */
interface StorageInterface
{

    const NEW_INSERT_ID_RETURN_FIELD = "pk";

    /**
     * @param TokenSequencerInterface $query
     * @param string $entityClass
     * @return object
     */
    public function query(TokenSequencerInterface $query, $entityClass);

    /**
     * @param EntityMetadataProviderInterface $provider
     */
    public function setEntityMetadataProvider(EntityMetadataProviderInterface $provider);

    /**
     * @return bool
     */
    public function hasEntityMetadataProvider();

} 