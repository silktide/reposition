<?php

namespace Silktide\Reposition\Metadata;

/**
 * Interface for providers of complete, decorated metadata for a given entity

 * @package Silktide\Reposition
 */
interface EntityMetadataProviderInterface 
{

    /**
     * @param $entity
     *
     * @return EntityMetadata
     */
    public function getEntityMetadata($entity);

} 