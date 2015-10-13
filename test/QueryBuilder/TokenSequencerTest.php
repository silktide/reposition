<?php

namespace Silktide\Reposition\Tests\QueryBuilder;

use Silktide\Reposition\Metadata\EntityMetadata;
use Silktide\Reposition\QueryBuilder\TokenSequencer;
use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\Exception\MetadataException;

class TokenSequencerTest extends \PHPUnit_Framework_TestCase {

    protected $tokenFactory;

    public function setUp()
    {
        $this->tokenFactory = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\QueryToken\\TokenFactory");
        $this->tokenFactory->shouldReceive("create")->andReturnUsing(function($type, $value = "") {return empty($value)? $type: $value;});
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
                7
            ],
            [
                [
                    "join" => ["table", $sequenceMock, "", TokenSequencerInterface::JOIN_FULL]
                ],
                8
            ]
        ];
    }

    /**
     * @dataProvider includeProvider
     *
     * @param array $toInclude
     * @param array $parentRelationships
     * @param array $expectedSequence
     */
    public function testIncludes(array $toInclude, array $parentRelationships, array $expectedSequence)
    {
        $metadataMock = $this->createMetadataMock([
            "entity" => "one",
            "relationships" => $parentRelationships
        ]);

        $sequencer = new TokenSequencer($this->tokenFactory, "find", $metadataMock);

        foreach ($toInclude as $include) {
            $includeMetadata = $this->createMetadataMock($include["metadata"]);
            $alias = isset($include["alias"])? $include["alias"]: "";
            $parent = isset($include["parent"])? $include["parent"]: "";
            $filters = null;
            if (!empty($include["filters"])) {
                $filters = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\TokenSequencerInterface");
                $filters->shouldReceive("getSequence")->andReturn($include["filters"]);
            }
            $sequencer->includeEntity($includeMetadata, $alias, $parent, $filters);
        }

        $this->assertEquals($expectedSequence, $sequencer->getSequence());

    }

    protected function createMetadataMock($config) {
        $metadata = \Mockery::mock("Silktide\\Reposition\\Metadata\\EntityMetadata");
        $metadata->shouldReceive("getEntity")->andReturn($config["entity"]);
        $metadata->shouldReceive("getCollection")->andReturn($config["entity"]);
        $metadata->shouldReceive("getPrimaryKey")->andReturn("id");
        $metadata->shouldReceive("getRelationship")->andReturnUsing(function ($entity) use ($config) {
            if (empty($config["relationships"][$entity])){
                return null;
            }
            return $config["relationships"][$entity];
        });
        return $metadata;
    }

    public function includeProvider()
    {
        $ourField = EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD;
        $theirField = EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD;
        $type = EntityMetadata::METADATA_RELATIONSHIP_TYPE;
        $m2m = EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY;
        $joinTable = EntityMetadata::METADATA_RELATIONSHIP_JOIN_TABLE;

        return [
            [ #0 single include
                [
                    [
                        "metadata" => ["entity" => "two"],
                    ]
                ],
                [
                    "two" => [
                        $theirField => "one_id",
                        $type => ""
                    ]
                ],
                [
                    "left", "join", "two", "on", "open", "one.id", "=", "two.one_id", "close"
                ]
            ],
            [ #1 single include, many to many
                [
                    [
                        "metadata" => ["entity" => "two"],
                    ]
                ],
                [
                    "two" => [
                        $type => $m2m,
                        $joinTable => "one_two"
                    ]
                ],
                [
                    "left", "join", "one_two", "on", "open", "one.id", "=", "one_two.one_id", "close",
                    "left", "join", "two", "on", "open", "one_two.two_id", "=", "two.id", "close"
                ]
            ],
            [ #2 single include with extra filters
                [
                    [
                        "metadata" => ["entity" => "two"],
                        "filters" => ["one.date", ">", "two.date"]
                    ]
                ],
                [
                    "two" => [
                        $theirField => "one_id",
                        $type => ""
                    ]
                ],
                [
                    "left", "join", "two", "on", "open", "one.id", "=", "two.one_id", "and", "one.date", ">", "two.date", "close"
                ]
            ],
            [ #3 multiple includes, including aliased include
                [
                    [
                        "metadata" => ["entity" => "two"],
                        "alias" => "owt"
                    ],
                    [
                        "metadata" => ["entity" => "three"],
                    ],
                ],
                [
                    "owt" => [
                        $theirField => "one_id",
                        $type => ""
                    ],
                    "three" => [
                        $theirField => "parent_id",
                        $type => ""
                    ]
                ],
                [
                    "left", "join", "two", "on", "open", "one.id", "=", "owt.one_id", "close",
                    "left", "join", "three", "on", "open", "one.id", "=", "three.parent_id", "close"
                ]
            ],
            [ #4 multiple includes, using included parent
                [
                    [
                        "metadata" => [
                            "entity" => "two",
                            "relationships" => [
                                "three" => [
                                    $theirField => "parent_id",
                                    $type => ""
                                ]
                            ]
                        ],
                    ],
                    [
                        "metadata" => ["entity" => "three"],
                        "parent" => "two"
                    ],
                ],
                [
                    "two" => [
                        $theirField => "one_id",
                        $type => ""
                    ]
                ],
                [
                    "left", "join", "two", "on", "open", "one.id", "=", "two.one_id", "close",
                    "left", "join", "three", "on", "open", "two.id", "=", "three.parent_id", "close"
                ]
            ]
        ];
    }

}
 