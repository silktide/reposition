<?php

namespace Silktide\Reposition\Repository;

use Silktide\Reposition\Metadata\EntityMetadata;

interface MetadataRepositoryInterface 
{

    /**
     * @return EntityMetadata
     */
    public function getEntityMetadata();

} 