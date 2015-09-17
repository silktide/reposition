<?php

namespace Silktide\Reposition\QueryBuilder\QueryToken;

class Value extends Token
{

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $type
     * @param mixed $value
     */
    public function __construct($type, $value)
    {
        $this->value = $value;
        parent::__construct($type);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}