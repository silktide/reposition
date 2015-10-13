<?php

namespace Silktide\Reposition\QueryBuilder\QueryToken;

use Silktide\Reposition\Exception\QueryException;

class Value extends Token
{

    const TYPE_STRING = "string";
    const TYPE_INT = "int";
    const TYPE_FLOAT = "float";
    const TYPE_BOOL = "bool";
    const TYPE_NULL = "null";

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
        $this->setType($type);
    }

    protected function setType($type)
    {
        switch ($type) {
            case self::TYPE_STRING:
            case self::TYPE_INT:
            case self::TYPE_FLOAT:
            case self::TYPE_BOOL:
            case self::TYPE_NULL:
                $this->type = $type;
                break;
            default:
                throw new QueryException("Invalid value type: '$type'");
        }
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}