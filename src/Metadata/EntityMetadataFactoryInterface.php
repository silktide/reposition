<?php

namespace Silktide\Reposition\Metadata;

/**
 * Interface for creating new, empty or partially decorated metadata for a given entity
 *
 * @package Silktide\Reposition
 */
interface EntityMetadataFactoryInterface
{

    /**
     * @param string|object $reference - class name or object instanced
     *
     * @return EntityMetadata
     */
    public function createMetadata($reference);

} 