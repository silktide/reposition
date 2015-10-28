<?php

namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\Exception\TokenDefinitionException;
use Silktide\Reposition\Exception\TokenParseException;
use Silktide\Reposition\QueryBuilder\QueryToken\Value;

class TokenParser 
{

    protected $definitions = [];

    public function __construct(array $definitions = [])
    {
        $this->addDefinitions($definitions);
    }

    public function addDefinitions(array $definitions)
    {
        foreach ($definitions as $type => $definition) {
            if (!is_string($type)) {
                throw new TokenDefinitionException("Found a definition key that was not a string");
            }
            if (!is_array($definition)) {
                throw new TokenDefinitionException("The definition for '$type' must be in array format");
            }
            $this->addDefinition($type, $definition);
        }
    }

    public function addDefinition($type, array $definition)
    {
        if (empty($definition)) {
            throw new TokenDefinitionException("Error defining '$type'. The definition was empty");
        }
        foreach ($definition as $i => $tokenDefinition) {
            if (!is_array($tokenDefinition)) {
                throw new TokenDefinitionException("Error defining '$type'. Token definitions must be in array format");
            }
            $tokenConstraints = 0;
            foreach ($tokenDefinition as $constraint => $value) {
                switch ($constraint) {
                    // string constraints
                    case "name":
                    case "type":
                        if (!is_string($value)) {
                            throw new TokenDefinitionException("Error defining '$type'. '$constraint' must be a string.");
                        }
                        ++$tokenConstraints;
                        break;
                    // boolean constraints
                    case "optional":
                        if (!is_bool($value) && !is_string($value)) {
                            throw new TokenDefinitionException("Error defining '$type'. '$constraint' must be a string or a boolean.");
                        }
                        break;
                    case "flag":
                        if (!is_string($value)) {
                            throw new TokenDefinitionException("Error defining '$type'. '$constraint' must be a string.");
                        }
                        break;
                    case "multiple":
                        if (!is_bool($value)) {
                            throw new TokenDefinitionException("Error defining '$type'. '$constraint' must be a boolean.");
                        }
                        break;
                    // mixed value constraints
                    case "value":
                        ++$tokenConstraints;
                        break;
                    // complex constraints
                    case "any":
                        if (!is_array($value) || (empty($value['type']) && empty($value['name']))) {
                            throw new TokenDefinitionException("Error defining '$type'. '$constraint' must be an array containing the keys 'type' and/or 'name'.");
                        }
                        $total = 0;
                        if (!empty($value["type"])) {
                            if (!is_array($value["type"])) {
                                throw new TokenDefinitionException("Error defining token #$i of '$type'. The 'type' key for '$constraint' must be an array.");
                            } else {
                                $total += count($value["type"]);
                            }
                        }
                        if (!empty($value["name"])) {
                            if (!is_array($value["name"])) {
                                throw new TokenDefinitionException("Error defining token #$i of '$type'. The 'name' key for '$constraint' must be an array.");
                            } else {
                                $total += count($value["name"]);
                            }
                        }
                        if ($total < 2) {
                            throw new TokenDefinitionException("Error defining token #$i of '$type'. The '$constraint' key requires at least two values. $total found.");
                        }
                        ++$tokenConstraints;
                        break;
                    default:
                        throw new TokenDefinitionException("Error defining token #$i of '$type'. Invalid constraint '$constraint'");
                }
            }
            if (empty($tokenConstraints)) {
                throw new TokenDefinitionException("Error defining token #$i of '$type'. Definition does not contain any token constraints");
            }
        }
        // done validating, add the definintion
        $this->definitions[$type] = $definition;
    }

    public function parseTokenSequence(TokenSequencerInterface $tokens)
    {
        $sequence = $tokens->getSequence();
        $type = $tokens->getType();

        $position = $this->parseTokens($type, $sequence);
        if (!empty($sequence[$position])) {
            throw new TokenParseException("Unexpected tokens at the end of the sequence. Reached position $position of " . (count($sequence)));
        }
    }

    protected function parseTokens($type, array $sequence, $position = 0)
    {
        $definition = $this->getDefinition($type);

        $optionalFlags = [];

        foreach ($definition as $tokenDefinition) {
            $multiple = empty($tokenDefinition["multiple"])? false: $tokenDefinition["multiple"];
            $optional = empty($tokenDefinition["optional"])? false: $tokenDefinition["optional"];
            if (!is_bool($optional)) {
                // only process this if the flag has not been set. Otherwise, treat as required
                if (empty($optionalFlags[$optional])) {
                    continue;
                }
                $optional = false;
            }
            $count = 0;
            $e = null;
            $definitionType = "";
            $definitionValue = "";

            // determine what type of constraint we should apply
            if (isset($tokenDefinition["value"])) {
                $definitionType = "value";
                $definitionValue = $tokenDefinition["value"];
            } elseif (isset($tokenDefinition["name"])) {
                $definitionType = "name";
                $definitionValue = $tokenDefinition["name"];
            } elseif (isset($tokenDefinition["type"])) {
                $definitionType = "type";
                $definitionValue = $tokenDefinition["type"];
            } elseif (isset($tokenDefinition["any"])) {
                $any = $tokenDefinition["any"];
                // try each value for any and see if we can match anything. use the first match we come to
                if (!empty($any["name"])) {
                    foreach ($any["name"] as $name) {
                        try {
                            $this->parseTokenConstraint("name", $name, $sequence, $position);
                            $definitionType = "name";
                            $definitionValue = $name;
                            break;
                        } catch (TokenParseException $f) {

                        }
                    }
                }
                if (empty($definitionType) && !empty($any["type"])) {
                    foreach ($any["type"] as $type) {
                        try {
                            $this->parseTokens($type, $sequence, $position);
                            $definitionType = "type";
                            $definitionValue = $type;
                            break;
                        } catch (TokenParseException $f) {

                        }
                    }
                }
            }
            // handle cases where the definition has no constraints  and where the
            if (empty($definitionType)) {
                if (empty($f)) {
                    throw new TokenDefinitionException("Cannot parse sequence, definition for '$type' does not define any constraints");
                } elseif (!$optional) {
                    throw new TokenParseException("Did not match any of the constraints defined in '$type'");
                } else {
                    return $position;
                }
            }

            $atLeastOnce = false;
            do {
                try {
                    $position = $this->parseTokenConstraint($definitionType, $definitionValue, $sequence, $position);
                    $atLeastOnce = true;
                } catch (TokenParseException $e) {

                }
            } while (empty($e) && $multiple);

            if (!$optional && !$atLeastOnce) {
                throw new TokenParseException("Did not find a sequence for '$type'." . (!empty($e)? " - " . $e->getMessage(): ""));
            } elseif (!empty($definition["flag"])) {
                $optionalFlags[$definition["flag"]] = true;
            }
        }
        return $position;
    }

    protected function parseTokenConstraint($constraint, $value, $sequence, $position)
    {
        if (empty($sequence[$position])) {
            throw new TokenParseException("Unexpected end of token sequence");
        }

        /** @var Token $token */
        $token = $sequence[$position];

        switch ($constraint) {
            case "name":
                if ($token->getType() != $value) {
                    throw new TokenParseException("Expecting a token of type '$value', found '{$token->getType()}'");
                }
                ++$position;
                break;
            case "value":
                if (!$token instanceof Value || $token->getValue() != $value) {
                    throw new TokenParseException("Expecting a token of value '$value'." . ($token instanceof Value? " Found '{$token->getValue()}'.": " No token value found."));
                }
                ++$position;
                break;
            case "type":
                try {
                    $position = $this->parseTokens($value, $sequence, $position);
                } catch (TokenParseException $e) {
                    throw new TokenParseException("Did not find the expected token sequence '$value'");
                }
                break;
        }
        return $position;
    }

    protected function getDefinition($type)
    {
        if (empty($this->definitions[$type])) {
            throw new TokenParseException("The definition '$type' does not exist.");
        }
        return $this->definitions[$type];
    }

} 