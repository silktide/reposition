<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryInterpreter;
use Silktide\Reposition\Normaliser\NormaliserInterface;
use Silktide\Reposition\Query\Query;

/**
 *
 */
interface QueryInterpreterInterface 
{

    /**
     * @param Query $query
     * @return CompiledQuery
     */
    public function interpret(Query $query);

    /**
     * @param NormaliserInterface $normaliser
     */
    public function setNormaliser(NormaliserInterface $normaliser);

} 