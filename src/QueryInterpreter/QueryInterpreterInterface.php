<?php

namespace Silktide\Reposition\QueryInterpreter;

use Silktide\Reposition\Normaliser\NormaliserInterface;
use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;

/**
 *
 */
interface QueryInterpreterInterface 
{

    /**
     * @param TokenSequencerInterface $query
     * @return CompiledQuery
     */
    public function interpret(TokenSequencerInterface $query);

    /**
     * @param NormaliserInterface $normaliser
     */
    public function setNormaliser(NormaliserInterface $normaliser);

    /**
     * @param EntityMetadataProviderInterface $provider
     */
    public function setEntityMetadataProvider(EntityMetadataProviderInterface $provider);

} 