<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryInterpreter;
use Silktide\Reposition\Query\Query;

/**
 *
 */
interface QueryInterpreterInterface 
{

    public function interpret(Query $query);

} 