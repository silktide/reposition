<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\QueryInterpreter;

use Silktide\Reposition\Exception\QueryException;
use Silktide\Reposition\Query\Query;
use Silktide\Reposition\Query\FindQuery;

/**
 *
 */
class MongoQueryInterpreter implements QueryInterpreterInterface
{

    public function interpret(Query $query)
    {
        switch ($query->getAction()) {
            case Query::ACTION_FIND:
                /** @var FindQuery $query */
                return $this->compileFindQuery($query);
                break;
            default:
                throw new QueryException("Invalid query action: {$query->getAction()}");
        }
    }

    protected function compileFindQuery(FindQuery $query)
    {
        $compiled = [
            "table" => $query->getTable(),
            "method" => "find",
            "arguments" => [$query->getFilters()]
        ];

        $calls = [];
        $limit = $query->getLimit();
        if (!empty($limit)) {
            $calls[] = ["limit", [$limit]];
        }
        $sort = $query->getSort();
        if (!empty($sort)) {
            foreach ($sort as $field => $direction) {
                $sort[$field] = ($direction == Query::SORT_ASCENDING)? 1: -1;
            }
            $calls[] = ["sort", [$sort]];
        }
        $compiled["calls"] = $calls;
        return $compiled;
    }

} 