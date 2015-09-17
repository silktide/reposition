<?php

namespace Silktide\Reposition\QueryBuilder\QueryToken;

class Reference extends Value
{

    /**
     * @var string
     */
    protected $alias;

    /**
     * @param string $type
     * @param mixed $value
     * @param string $alias
     */
    public function __construct($type, $value, $alias)
    {
        $this->alias = $alias;
        parent::__construct($type, $value);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

}