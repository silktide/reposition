<?php

namespace Silktide\Reposition\Metadata;

interface MetadataProviderInterface
{

    /**
     * @param string|object $reference - class name or object instanced
     *
     * @return EntityMetadata
     */
    public function getMetadata($reference);

} 