<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryInterpreter;

/**
 *
 */
class CompiledQuery 
{

    /**
     * @var string
     */
    protected $table;

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

    public function __construct($table, $method, $arguments, $calls = [])
    {
        $this->setTable($table);
        $this->setMethod($method);
        $this->setArguments($arguments);
        $this->setCalls($calls);
    }

    /**
     * @param array $arguments
     */
    protected function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
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
     */
    protected function setCalls(array $calls)
    {
        $this->calls = [];
        foreach ($calls as $call) {
            $this->addCall($call);
        }
    }

    /**
     * @param array $call
     * @throws \InvalidArgumentException
     */
    protected function addCall(array $call)
    {
        if (count($call) != 2 || empty($call[0]) || empty($call[1])) {
            throw new \InvalidArgumentException("Cannot set query call. Invalid format");
        }

        $this->calls[] = $call;
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
     */
    protected function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $table
     */
    protected function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }



} 