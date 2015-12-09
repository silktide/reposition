<?php

namespace Silktide\Reposition\QueryInterpreter;

/**
 *
 */
class CompiledQuery 
{

    /**
     * @var string
     */
    protected $collection;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $calls = [];

    /**
     * @var string
     */
    protected $query = "";

    /**
     * @var string
     */
    protected $primaryKeySequence = "";

    /**
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $calls
     * @return $this
     */
    public function setCalls(array $calls)
    {
        $this->calls = [];
        foreach ($calls as $call) {
            $this->addCall($call);
        }
        return $this;
    }

    /**
     * @param array $call
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addCall(array $call)
    {
        if (count($call) != 2 || empty($call[0]) || empty($call[1])) {
            throw new \InvalidArgumentException("Cannot set query call. Invalid format");
        }

        $this->calls[] = $call;
        return $this;
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getPrimaryKeySequence()
    {
        return $this->primaryKeySequence;
    }

    /**
     * @param string $primaryKeySequence
     * @return $this
     */
    public function setPrimaryKeySequence($primaryKeySequence)
    {
        $this->primaryKeySequence = $primaryKeySequence;
        return $this;
    }

}