<?php

namespace Silktide\Reposition\Tests\QueryBuilder;

use Silktide\Reposition\QueryBuilder\TokenSequencer;
use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;

class TokenSequencerTest extends \PHPUnit_Framework_TestCase {

    protected $tokenFactory;

    public function setUp()
    {
        $this->tokenFactory = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\QueryToken\\TokenFactory");
        $this->tokenFactory->shouldReceive("create")->andReturnUsing(function($type) {return $type;});
    }

    /**
     * @dataProvider sequenceProvider
     *
     * @param array $calls
     * @param int $expectedSequenceCount
     */
    public function testSequencing(array $calls, $expectedSequenceCount)
    {
        $qb = new TokenSequencer($this->tokenFactory);
        foreach ($calls as $method => $arguments) {
            $qb = call_user_func_array([$qb, $method], $arguments);
        }

        $this->assertCount($expectedSequenceCount, $qb->getSequence());
    }

    public function sequenceProvider()
    {
        $sequenceMock = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\TokenSequencerInterface");
        $sequenceMock->shouldReceive("getSequence")->andReturn([]);
        return [
            [
                [
                    "where" => [],
                    "ref" => ["field"],
                    "op" => ["="],
                    "val" => ["value"]
                ],
                4
            ],
            [
                [
                    "closure" => []
                ],
                2
            ],
            [
                [
                    "sort" => [["field" => 1, "field2" => -1]]
                ],
                5
            ],
            [
                [
                    "group" => [["field1", "field2", "field3"]]
                ],
                4
            ],
            [
                [
                    "limit" => [1]
                ],
                2
            ],
            [
                [
                    "limit" => [1, 2]
                ],
                4
            ],
            [
                [
                    "aggregate" => ["count", "field"]
                ],
                4
            ],
            [
                [
                    "func" => ["unix_timestamp"]
                ],
                3
            ],
            [
                [
                    "func" => ["ifnull", ["field", "value"]]
                ],
                5
            ],
            [
                [
                    "join" => ["table", $sequenceMock]
                ],
                6
            ],
            [
                [
                    "join" => ["table", $sequenceMock, "", TokenSequencerInterface::JOIN_FULL]
                ],
                7
            ]
        ];
    }


}
 