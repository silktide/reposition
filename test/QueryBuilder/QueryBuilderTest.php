<?php

namespace Silktide\Reposition\Tests\QueryBuilder;

use Silktide\Reposition\QueryBuilder\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

    protected $tokenFactory;

    public function setUp()
    {
        $this->tokenFactory = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\QueryToken\\TokenFactory");
        $this->tokenFactory->shouldReceive("create")->andReturnUsing(function($type) {return $type;});
    }

    /**
     * @dataProvider queryStartProvider
     *
     * @param string $method
     * @param string $expectedType
     */
    public function testQueryStarts($method, $expectedType)
    {
        $collection = "collection";

        $qb = new QueryBuilder($this->tokenFactory);
        $query = $qb->{$method}($collection);

        $this->assertEquals($expectedType, $query->getType());
        $this->assertEquals($collection, $query->getCollectionName());
    }

    public function queryStartProvider()
    {
        return [
            [
                "find",
                QueryBuilder::TYPE_FIND
            ],
            [
                "save",
                QueryBuilder::TYPE_SAVE
            ],
            [
                "update",
                QueryBuilder::TYPE_UPDATE
            ],
            [
                "delete",
                QueryBuilder::TYPE_DELETE
            ]
        ];
    }

    public function testSequencingNestedExpressions()
    {
        $qb = new QueryBuilder($this->tokenFactory);
        $qb = $qb->closure(
            $qb->ref("field")->op("!=")->val("value1")->andL()
               ->ref("field")->op("!=")->val("value2")
        )->orL()
        ->closure(
            $qb->ref("field2")->op("!=")->val("value1")->andL()
               ->ref("field2")->op("!=")->val("value2")
        );

        $this->assertCount(19, $qb->getSequence());
    }

}
 