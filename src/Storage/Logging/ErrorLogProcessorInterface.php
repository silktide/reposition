<?php

namespace Silktide\Reposition\Storage\Logging;

interface ErrorLogProcessorInterface 
{

    /**
     * @param $query
     * @param array $errorInfo
     */
    public function recordError($query, array $errorInfo);

} 