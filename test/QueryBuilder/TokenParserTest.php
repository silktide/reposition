<?php

namespace Silktide\Reposition\Tests\QueryBuilder;

use Silktide\Reposition\QueryBuilder\TokenParser;
use Silktide\Reposition\Exception\TokenDefinitionException;
use Silktide\Reposition\Exception\TokenParseException;

class TokenParserTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider invalidDefinitionProvider
     *
     * @param array $definition
     * @param string $errorPartial
     */
    public function testInvalidDefinitions(array $definition, $errorPartial = "")
    {
        $parser = new TokenParser();
        try {
            $parser->addDefinition("test", $definition);
            $this->fail("Should not be able to add an invalid definition");
        } catch (TokenDefinitionException $e) {
            if (!empty($errorPartial)) {
                $this->assertContains($errorPartial, $e->getMessage());
            }
        }
    }

    public function invalidDefinitionProvider()
    {
        return [
            [ // empty list
                []
            ],
            [ // not a list of defintions
                ["one", "two", "three"]
            ],
            [ // empty definition
                [[]]
            ],
            [ // unknown constraint
                [["blah" => "blah"]]
            ],
            [ // invalid optional
                [["optional" => 123]],
                "optional"
            ],
            [ // invalid multiple
                [["multiple" => "blah"]],
                "multiple"
            ],
            [ // invalid type
                [["type" => 1]],
                "type"
            ],
            [ // invalid name
                [["name" => 1]],
                "name"
            ],
            [ // invalid any
                [["any" => 1]],
                "any"
            ],
            [ // empty any
                [["any" => []]],
                "any"
            ],
            [ // invalid keys in any
                [["any" => ["blah" => "blah"]]],
                "any"
            ],
            [ // invalid "type" in any
                [["any" => ["type" => "blah"]]],
                "type"
            ],
            [ // invalid "name" in any
                [["any" => ["name" => "blah"]]],
                "name"
            ],
            [ // not enough types
                [["any" => ["type" => ["blah"]]]],
                "any"
            ],
            [ // not enough names
                [["any" => ["name" => ["blah"]]]],
                "any"
            ]
        ];
    }

    /**
     * @dataProvider validDefinitionProvider
     *
     * @param array $definition
     */
    public function testValidDefnitions(array $definition)
    {
        $parser = new TokenParser(["test" => $definition]);
    }

    public function validDefinitionProvider()
    {
        return [
            [ // name
                [["name" => "name"]]
            ],
            [ // type
                [["type" => "type"]]
            ],
            [ // optional
                [["name" => "name", "optional" => true]]
            ],
            [ // multiple
                [["name" => "name", "multiple" => false]]
            ],
            [ // any with type
                [["any" => ["type" => ["one", "two"]]]]
            ],
            [ // any with name
                [["any" => ["name" => ["one", "two"]]]]
            ],
            [ // any with both
                [["any" => ["type" => ["one"], "name" => ["two"]]]]
            ]
        ];
    }

    /**
     * @dataProvider invalidSequenceProvider
     *
     * @param array $definitions
     * @param $sequenceType
     * @param array $sequence
     * @param string $expectedExceptionType
     */
    public function testInvalidSequences(array $definitions, $sequenceType, array $sequence, $errorPartial = "")
    {

        $sequencer = \Mockery::mock("\\Silktide\\Reposition\\QueryBuilder\\TokenSequencerInterface");
        $sequencer->shouldReceive("getType")->andReturn($sequenceType);
        $sequencer->shouldReceive("getSequence")->andReturn($sequence);

        $parser = new TokenParser($definitions);
        try {
            $parser->parseTokenSequence($sequencer);
            $this->fail("Should not be able to parse an invalid sequence");
        } catch (TokenParseException $e) {
            if (!empty($errorPartial)) {
                $this->assertContains($errorPartial, $e->getMessage());
            }
        }
    }

    public function invalidSequenceProvider()
    {
        return [
            [ // no sequence type in definitions
                ["test" => [["type" => "token"]]],
                "find",
                [$this->createTokenMock("token")],
                "find"
            ],
            [ // no match on any name
                ["test" => [["any" => ["name" => ["one", "two"]]]]],
                "test",
                [$this->createTokenMock("token")],
                "constraints"
            ],
            [ // no match on any type
                [
                    "test" => [["any" => ["type" => ["one", "two"]]]],
                    "one" => [["name" => "one"]],
                    "two" => [["name" => "two"]]
                ],
                "test",
                [$this->createTokenMock("token")],
                "constraints"
            ],
            [ // no match on name
                ["test" => [["name" => "name"]]],
                "test",
                [$this->createTokenMock("token")],
                "of type"
            ],
            [ // no match on type
                [
                    "test" => [["type" => "one"]],
                    "one" => [["name" => "one"]]
                ],
                "test",
                [$this->createTokenMock("token")],
                "sequence"
            ],
            [ // no match on subsequent token
                ["test" => [["name" => "name"],["name" => "notName"]]],
                "test",
                [$this->createTokenMock("name"),$this->createTokenMock("name")],
                "notName"
            ],
            [ // no match on value
                ["test" => [["value" => "orange"]]],
                "test",
                [$this->createTokenMock("name", "value")],
                "orange"
            ],
            [ // no match on value
                ["test" => [["value" => "orange"]]],
                "test",
                [$this->createTokenMock("name", "value")],
                "orange"
            ],
            [ // not multiple at end of sequence
                ["test" => [["name" => "name"]]],
                "test",
                [$this->createTokenMock("name"),$this->createTokenMock("name")],
                "end of the sequence"
            ]
        ];
    }

    /**
     * @dataProvider validSequenceProvider
     *
     * @param array $definitions
     * @param array $sequence
     */
    public function testValidSequences(array $definitions, array $sequence)
    {
        $sequencer = \Mockery::mock("\\Silktide\\Reposition\\QueryBuilder\\TokenSequencerInterface");
        $sequencer->shouldReceive("getType")->andReturn("test");
        $sequencer->shouldReceive("getSequence")->andReturn($sequence);

        $parser = new TokenParser($definitions);
        $parser->parseTokenSequence($sequencer);
    }

    public function validSequenceProvider()
    {
        return [
            [ // simple name match
                ["test" => [["name" => "name"]]],
                [$this->createTokenMock("name")]
            ],
            [ // simple value match
                ["test" => [["value" => "one"]]],
                [$this->createTokenMock("name", "one")]
            ],
            [ // simple sequence match
                ["test" => [["name" => "one"], ["name" => "two"]]],
                [$this->createTokenMock("one"), $this->createTokenMock("two")]
            ],
            [ // optional match (missing)
                ["test" => [["name" => "one"], ["name" => "two", "optional" => true], ["name" => "three"]]],
                [$this->createTokenMock("one"), $this->createTokenMock("three")]
            ],
            [ // optional match (included)
                ["test" => [["name" => "one"], ["name" => "two", "optional" => true], ["name" => "three"]]],
                [$this->createTokenMock("one"), $this->createTokenMock("two"), $this->createTokenMock("three")]
            ],
            [ // multiple match
                ["test" => [["name" => "name", "multiple" => true]]],
                [$this->createTokenMock("name"), $this->createTokenMock("name"), $this->createTokenMock("name")]
            ],
            [ // match after multiple
                ["test" => [["name" => "name", "multiple" => true],["name" => "end"]]],
                [$this->createTokenMock("name"), $this->createTokenMock("name"), $this->createTokenMock("name"), $this->createTokenMock("end")]
            ],
            [ // any name match #0
                ["test" => [["any" => ["name" => ["one","two"]]]]],
                [$this->createTokenMock("one")]
            ],
            [ // any name match #1
                ["test" => [["any" => ["name" => ["one","two"]]]]],
                [$this->createTokenMock("two")]
            ],
            [ // type match
                [
                    "test" => [["type" => "oneTwo"]],
                    "oneTwo" => [["name" => "one"], ["name" => "two"]]
                ],
                [$this->createTokenMock("one"), $this->createTokenMock("two")]
            ],
            [ // any type match #0
                [
                    "test" => [["any" => ["type" => ["oneTwo","twoOne"]]]],
                    "oneTwo" => [["name" => "one"], ["name" => "two"]],
                    "twoOne" => [["name" => "two"], ["name" => "one"]]
                ],
                [$this->createTokenMock("one"), $this->createTokenMock("two")]
            ],
            [ // any type match #1
                [
                    "test" => [["any" => ["type" => ["oneTwo","twoOne"]]]],
                    "oneTwo" => [["name" => "one"], ["name" => "two"]],
                    "twoOne" => [["name" => "two"], ["name" => "one"]]
                ],
                [$this->createTokenMock("two"), $this->createTokenMock("one")]
            ]

        ];
    }

    protected function createTokenMock($type, $value = "", $alias = "")
    {
        $tokenClass = "Token";
        if (!empty($alias)) {
            $tokenClass = "Reference";
        } else if (!empty($value)) {
            if ($type == "entity") {
                $tokenClass = "Entity";
            } else {
                $tokenClass = "Value";
            }
        }

        $token = \Mockery::mock("Silktide\\Reposition\\QueryBuilder\\QueryToken\\$tokenClass");
        $token->shouldReceive("getType")->andReturn($type);
        if (!empty($value)) {
            if ($tokenClass == "Entity") {
                $token->shouldReceive("getEntity")->andReturn($value);
            } else {
                $token->shouldReceive("getValue")->andReturn($value);
            }
        }
        if (!empty($alias)) {
            $token->shouldReceive("getAlias")->andReturn($alias);
        }
        return $token;
    }

}
 