<?php

namespace Silktide\Reposition\Metadata;

interface EntityMetadataFactoryInterface
{

    /**
     * @param string|object $reference - class name or object instanced
     *
     * @return EntityMetadata
     */
    public function createMetadata($reference);

} 