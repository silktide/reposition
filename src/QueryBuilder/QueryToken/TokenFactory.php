<?php

namespace Silktide\Reposition\QueryBuilder\QueryToken;

class TokenFactory 
{

    public function create($type, $value = null, $alias = null)
    {
        if (!is_null($alias)) {
            return new Reference($type, $value, $alias);
        } elseif (!is_null($value)) {
            if ($type == "entity") {
                return new Entity($value);
            }
            return new Value($type, $value);
        }
        return new Token($type);
    }

} 