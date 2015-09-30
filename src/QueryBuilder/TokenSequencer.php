<?php

namespace Silktide\Reposition\QueryBuilder;

use Silktide\Reposition\QueryBuilder\QueryToken\TokenFactory;
use Silktide\Reposition\QueryBuilder\QueryToken\Token;
use Silktide\Reposition\Exception\TokenParseExcaptoin;

class TokenSequencer implements TokenSequencerInterface
{

    protected $tokenFactory;

    protected $type;

    protected $collectionName;

    protected $includes = [];

    protected $querySequence = [];

    protected $filtering = false;

    protected $currentSection = "initial";

    protected $requiresReset = true;

    public function __construct(TokenFactory $tokenFactory, $type = self::TYPE_EXPRESSION, $collectionName = "")
    {
        $this->tokenFactory = $tokenFactory;
        $this->setType($type);
        $this->collectionName = $collectionName;
    }

    protected function setType($type)
    {
        switch ($type) {
            case self::TYPE_EXPRESSION:
            case self::TYPE_FIND:
            case self::TYPE_SAVE:
            case self::TYPE_UPDATE:
            case self::TYPE_DELETE:
                $this->type = $type;
                break;
            default:
                throw new \InvalidArgumentException("Cannot create a QueryBuilder with the type '$type'");
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function isQuery()
    {
        return ($this->type != self::TYPE_EXPRESSION);
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    public function getIncludes()
    {
        return $this->includes;
    }

    public function getSequence()
    {
        return $this->querySequence;
    }

    public function getNextToken()
    {
        // reset if required, then mark as having been reset
        if ($this->requiresReset) {
            reset($this->querySequence);
            $this->requiresReset = false;
        }
        // get the current token
        $token = current($this->querySequence);
        // advance the array pointer ready for the next call
        next($this->querySequence);
        // return the current token
        return $token;
    }

    protected function addToSequence(Token $token)
    {
        $this->querySequence[] = $token;
    }

    protected function addNewToSequence($type, $value = null, $alias = null)
    {
        $this->querySequence[] = $this->tokenFactory->create($type, $value, $alias);
    }

    protected function mergeSequence(array $sequence)
    {
        $this->querySequence = array_merge($this->querySequence, $sequence);
    }

    protected function addMixedContentToSequence($content, $defaultType)
    {
        if ($content instanceof TokenSequencer) {
            $this->closure($content);
        } elseif ($content instanceof Token) {
            $this->addToSequence($content);
        } else {
            $this->addNewToSequence($defaultType, $content);
        }
    }

    ////////// QUERY SECTION METHODS //////////

    public function aggregate($type)
    {
        $values = func_get_args();
        // already have type so remove that from the array
        array_shift($values);

        $type = strtolower($type);

        switch ($type) {
            case "count":
            case "sum":
            case "maximum":
            case "mininum":
            case "average":
                $this->func($type, $values);
                break;
            default:
                throw new \InvalidArgumentException("The aggregate function '$type' is invalid");
        }
        return $this;
    }

    public function includeEntity($entity, $collection, TokenSequencerInterface $on, $collectionAlias = "", $type = self::JOIN_LEFT)
    {
        // check we're dealing with a query
        if (!$this->isQuery()) {
            throw new TokenParseException("Cannot include an entity on an expression sequence.");
        }

        // check for alias colisions
        if (!empty($this->includes[$collectionAlias])) {
            throw new TokenParseException("Cannot include entity '$entity'. The specified alias '$collectionAlias' is already in use");
        }

        // add the include and create the join
        $this->includes[$collectionAlias] = $entity;
        return $this->join($collection, $on, $collectionAlias, $type, $type);
    }

    public function join($collection, TokenSequencerInterface $on, $collectionAlias = "", $type = self::JOIN_LEFT)
    {
        // validate join type
        switch ($type) {
            case "":
                $type = self::JOIN_INNER;
                // no break
            case self::JOIN_INNER:
            case self::JOIN_LEFT:
            case self::JOIN_RIGHT:
            case self::JOIN_FULL:
                // direction is fine
                break;
            default:
                throw new TokenParseException(
                    "Unsupported join type: '$type'. Join type must be '" .
                    self::JOIN_INNER . "' (default), '" .
                    self::JOIN_LEFT . "', '" .
                    self::JOIN_RIGHT . "' or '" .
                    self::JOIN_FULL . "'"
                );
        }


        $this->addNewToSequence($type);
        if ($type == self::JOIN_FULL) {
            $this->addNewToSequence("outer");
        }
        $this->addNewToSequence("join");
        $this->addNewToSequence("collection", $collection, $collectionAlias);
        $this->closure($on);
        return $this;
    }

    public function where()
    {
        $this->addNewToSequence("where");
        return $this;
    }

    public function sort(array $by)
    {
        $this->addNewToSequence("sort");
        foreach ($by as $ref => $direction) {
            $this->addMixedContentToSequence($ref, "field");
            $this->addNewToSequence("sort direction", ($direction == self::SORT_DESC)? $direction: self::SORT_ASC );
        }
        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            throw new \InvalidArgumentException("Cannot have a limit less than 1");
        }
        $this->addNewToSequence("limit");
        $this->addNewToSequence("integer", $limit);
        if (!is_null($offset)) {
            $offset = (int) $offset;
            if ($limit < 0) {
                throw new \InvalidArgumentException("Cannot have an offset less than 0");
            }
            $this->addNewToSequence("offset");
            $this->addNewToSequence("integer", $offset);
        }
        return $this;
    }

    public function group(array $by)
    {
        $this->addNewToSequence("group");
        foreach ($by as $ref) {
            $this->addMixedContentToSequence($ref, "field");
        }
        return $this;
    }

    ////////// LOGIC METHODS //////////
    // suffixed with 'L' because we can't use keywords as method names in PHP 5.5

    public function notL()
    {
        $this->addNewToSequence("not");
        return $this;
    }

    public function andL()
    {
        $this->addNewToSequence("and");
        return $this;
    }

    public function orL()
    {
        $this->addNewToSequence("or");
        return $this;
    }

    /**
     * wraps the following expression in parentheses to isolate it
     */
    public function closure($content = null)
    {
        $this->addNewToSequence("closure start");

        if (!empty($content)) {
            if ($content instanceof TokenSequencer) {
                $this->mergeSequence($content->getSequence());
            } elseif ($content instanceOf Token) {
                $this->addToSequence($content);
            } elseif (is_array($content)) {
                foreach ($content as $subcontent) {
                    $this->addMixedContentToSequence($subcontent, "value");
                }
            } else {
                $this->addNewToSequence("value", $content);
            }
        }

        $this->addNewToSequence("closure end");

        return $this;
    }

    ////////// EXPRESSION METHODS //////////

    public function ref($name, $alias = "", $type = "field")
    {
        $this->addNewToSequence($type, $name, $alias);
        return $this;
    }

    public function op($value)
    {
        $this->addNewToSequence("operator", $value);
        return $this;
    }

    public function val($value)
    {
        $this->addNewToSequence("value", $value);
        return $this;
    }

    public function entity($entity)
    {
        $this->addNewToSequence("entity", $entity);
        return $this;
    }

    public function func($name, array $args = [])
    {
        $this->addNewToSequence("function", $name);
        $this->closure($args);
        return $this;
    }

    public function keyword($keyword)
    {
        $this->addNewToSequence($keyword);
        return $this;
    }
} 