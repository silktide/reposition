<?php

namespace Silktide\Reposition\Metadata;

class EntityMetadataFactory implements EntityMetadataFactoryInterface
{

    /**
     * {@inheritDoc}
     */
    public function createMetadata($reference)
    {
        if (is_object($reference)) {
            $reference = get_class($reference);
        }

        return new EntityMetadata($reference);
    }

    public function createEmptyMetadata()
    {
        return new EntityMetadata("");
    }

} 