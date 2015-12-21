<?php

namespace Silktide\Reposition\Test\Collection;

/**
 * MockEntity
 */
class MockEntity
{

    protected $id;

    public $property;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProperty()
    {
        return $this->property;
    }

}