<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Normaliser;
use Silktide\Reposition\QueryBuilder\MongoQueryBuilder;
use Silktide\Reposition\QueryBuilder\QueryBuilder;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;

/**
 *
 */
class MongoNormaliser implements NormaliserInterface
{

    protected $primary_key = "_id";

    public function normalise(array $data, array $options = [])
    {
        return $this->doNormalisation($data, $options);
    }

    public function denormalise(array $data, array $options = [])
    {
        return $this->doNormalisation($data, $options);
    }

    protected function doNormalisation(array $data, array $options)
    {
        foreach ($data as $field => $value) {
            if (empty($options["filter"]) || $options["filter"] == "keys") {
                $normalisedField = $this->normaliseKey($field);
                if ($normalisedField !== $field) {
                    $data[$normalisedField] = $value;
                    unset($data[$field]);
                    $field = $normalisedField;
                }
            }
            if (empty($options["filter"]) || $options["filter"] == "values") {
                $data[$field] = $this->normaliseValue($field, $value);
            }
            if (is_array($value)) {
                // recurse
                $data[$field] = $this->normalise($value);
            }
        }
        return $data;
    }

    protected function normaliseKey($key) {
        if ($key == QueryBuilderInterface::PRIMARY_KEY) {
            return $this->primary_key;
        } elseif ($key == $this->primary_key) {
            return QueryBuilderInterface::PRIMARY_KEY;
        }
        return $key;
    }

    protected function normaliseValue($key, $value)
    {
        // primary key
        if ($key == $this->primary_key) {
            return new \MongoId($value);
        }
        if ($key == QueryBuilderInterface::PRIMARY_KEY && $value instanceof \MongoId) {
            return "" . $value;
        }

        // dates
        if ($value instanceof \DateTime) {
            // create mongo date
            return new \MongoDate($value->getTimestamp());
        }
        if ($value instanceof \MongoDate) {
            //convert mongo date to DateTime
            return new \DateTime($value->sec);
        }

        // normal values
        return $value;
    }

} 