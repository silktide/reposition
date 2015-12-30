<?php

namespace Silktide\Reposition\Storage\Logging;

use Silktide\Reposition\QueryInterpreter\CompiledQuery;

interface QueryLogProcessorInterface 
{

    /**
     * @param CompiledQuery $query
     */
    public function recordQueryStart(CompiledQuery $query);

    public function recordQueryEnd();

} 