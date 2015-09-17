<?php

namespace Silktide\Reposition\QueryInterpreter;

use Silktide\Reposition\Normaliser\NormaliserInterface;
use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;

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

} 