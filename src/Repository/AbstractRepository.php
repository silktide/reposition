<?php

namespace Silktide\Reposition\Repository;

use Silktide\Reposition\Collection\Collection;
use Silktide\Reposition\Exception\RepositoryException;
use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;
use Silktide\Reposition\Storage\StorageInterface;
use Silktide\Reposition\Metadata\EntityMetadata;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;

/**
 *
 */
abstract class AbstractRepository implements RepositoryInterface, MetadataRepositoryInterface
{

    const ANSI_DUPLICATE_KEY_ERROR_CODE = "23505";

    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @var string
     */
    protected $collectionName;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var QueryBuilderInterface
     */
    protected $queryBuilder;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var EntityMetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * Flag to set if relationships are included by default
     * Alternatively, an array selecting the relationships to include by default
     *
     * @var bool|array
     */
    protected $includeRelationshipsByDefault = false;

    /**
     * List of relationships to cascade
     * When saving, deleting or updating the parent, perform the same operation on the child entities
     *
     * @var array
     */
    protected $relationshipCascade = [];

    /**
     * @param EntityMetadata $entityMetadata
     * @param QueryBuilderInterface $queryBuilder
     * @param StorageInterface $storage
     * @param EntityMetadataProviderInterface $metadataProvider
     */
    public function __construct(EntityMetadata $entityMetadata, QueryBuilderInterface $queryBuilder, StorageInterface $storage, EntityMetadataProviderInterface $metadataProvider)
    {
        $this->entityMetadata = $entityMetadata;
        $this->queryBuilder = $queryBuilder;
        $this->storage = $storage;
        $this->metadataProvider = $metadataProvider;
        $this->configureMetadata();
    }

    /**
     * Configure the metadata for the entity this repository interacts with
     *
     * Override this method to set additional fields or define relationships with other entities
     *
     */
    protected function configureMetadata()
    {
        $this->entityMetadata->setCollection($this->collectionName);
        if (!empty($this->primaryKey)) {
            $this->entityMetadata->setPrimaryKey($this->primaryKey);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName()
    {
        return $this->entityMetadata->getEntity();
    }

    /**
     * {@inheritDoc}
     */
    public function getCollectionName()
    {
        return $this->entityMetadata->getCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    public function cascadeRelationship($alias, RepositoryInterface $repository)
    {
        if ($this->entityMetadata->hasRelationship($alias)) {
            $this->relationshipCascade[$alias] = $repository;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find($id, $includeRelationships = null)
    {
        $query = $this->getFindQuery($id, $includeRelationships);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(array $filters, array $sort = [], $limit = 0, array $options = [], $includeRelationships = null)
    {
        $query = $this->getFilterQuery($filters, $includeRelationships);

        if (!empty($sort)) {
            $query->sort($sort);
        }

        if (!empty($limit)) {
            $query->limit($limit);
        }

        return $this->doQuery($query);
    }

    protected function getFilterQuery(array $filters, $includeRelationships = null)
    {
        $query = $this->queryBuilder->find($this->entityMetadata);
        $this->addIncludes($query, $includeRelationships);
        $this->createWhereFromFilters($query, $filters, true, true);
        return $query;
    }

    protected function getFindQuery($id, $includeRelationships = null)
    {
        return $this->getFilterQuery([$this->entityMetadata->getPrimaryKey() => $id], $includeRelationships);
    }

    /**
     * {@inheritDoc}
     */
    public function save($entity, array $options = [])
    {
        $query = $this->queryBuilder->save($this->entityMetadata, $options)->entity($entity);
        try {
            $saveResult = $this->doQuery($query, false);
        } catch (\PDOException $e) {
            $pkMetadata = $this->entityMetadata->getPrimaryKeyMetadata();
            // if this entity has an auto incrementing PK, or the error is not about PK conflicts, re-throw the error
            if ($pkMetadata[EntityMetadata::METADATA_FIELD_AUTO_INCREMENTING] == true || $e->errorInfo[0] != self::ANSI_DUPLICATE_KEY_ERROR_CODE) {
                throw $e;
            }

            // this is a duplicate key on a collection with a PK that does not auto increment.
            // force the save to be an update
            $query->setOption("saveType", "update");
            $saveResult = $this->doQuery($query, false);
        }

        // get the primary key value, if there is one, and save it on the entity
        if (isset($saveResult[StorageInterface::NEW_INSERT_ID_RETURN_FIELD])) {
            $pkValue = $saveResult[StorageInterface::NEW_INSERT_ID_RETURN_FIELD];
            $this->entityMetadata->setEntityValue($entity, $this->entityMetadata->getPrimaryKey(), $pkValue);
        } else {
            $pkValue = null;
        }

        // save "* to many" relationships
        foreach ($this->entityMetadata->getRelationships() as $alias => $relationship) {
            $type = $relationship[EntityMetadata::METADATA_RELATIONSHIP_TYPE];
            $property = $relationship[EntityMetadata::METADATA_RELATIONSHIP_PROPERTY];

            $childValue = $this->entityMetadata->getEntityValue($entity, $property);

            if ($type == EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_ONE) {
                // This covers "many to one" as well
                if (!empty($this->relationshipCascade[$alias])) {
                    /** @var RepositoryInterface $childRepo */
                    $childRepo = $this->relationshipCascade[$alias];
                    $childRepo->save($childValue);
                }
                // nothing more to do for One to One / Many to One
                continue;
            }

            /* We need to know the following:
             * Which child entities have been added and removed
             * The field names used on both sides of the relationship
             * The corresponding values for both the parent and child entities
             */

            if (!$childValue instanceof Collection) {
                // if this isn't a collection object we can't process this relationship
                continue;
            }

            // get the field used on the child entity
            $childMetadata = $this->metadataProvider->getEntityMetadata($relationship[EntityMetadata::METADATA_ENTITY]);

            $theirField = !empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD])
                ? $relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD]
                : $childMetadata->getPrimaryKey();

            // get the field and value used on the parent entity
            $ourValue = null;
            $ourField = null;
            // if we haven't specified a field for the parent, use the primary key
            if (empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD])) {
                // using the PK
                if ($pkValue !== null) {
                    // This was a new record and we already have the value for the parent PK, so set it now
                    $ourValue = $pkValue;
                }
                $ourField = $this->entityMetadata->getPrimaryKey();
            } else {
                $ourField = $relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD];
            }

            // get the parent value if it isn't set already
            if ($ourValue === null) {
                $ourValue = $this->entityMetadata->getEntityValue($entity, $ourField);
            }

            // update the relationship
            if ($type == EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_MANY) {
                $this->saveOneToMany($alias, $childValue, $childMetadata, $ourValue, $theirField);
            } elseif ($type == EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY) {
                $this->saveManyToMany($relationship, $alias, $childValue, $childMetadata, $ourValue, $ourField, $theirField);
            }
        }

        return $saveResult;
    }

    protected function saveOneToMany($alias, Collection $collection, EntityMetadata $childMetadata, $ourValue, $theirField) {
        $childPk = $childMetadata->getPrimaryKey();

        if (!empty($this->relationshipCascade[$alias])) {
            // cascading relationships must deal with each entity individually
            /** @var RepositoryInterface $childRepo */
            $childRepo = $this->relationshipCascade[$alias];
            // save all entities currently in the collection
            foreach ($collection as $child) {
                $childRepo->save($child);
            }
            $removed = $collection->getRemovedEntities();
            if (!empty($removed)) {
                // delete any that have been removed
                $delete = $this->queryBuilder->delete($childMetadata);
                $this->createWhereFromFilters($delete, [$childPk => $this->condition("in", $removed)]);
                $this->doQuery($delete, false);
            }
        }

        // For non cascading operations, both adding and removing are updates; one sets the parent value on the child,
        // the other removes any existing values.
        // The two are so similar we can abstract the differences to the following array:
        $updates = [
            [
                "entities" => $collection->toArray(false),
                "value" => $ourValue
            ],
            [
                "entities" => $collection->getRemovedEntities(),
                "value" => null
            ]
        ];

        foreach ($updates as $update) {
            // obviously, we don't have to do anything if there are no entities to process
            if (empty($update["entities"])) {
                continue;
            }


            $query = $this->queryBuilder->update($childMetadata)
                ->ref($theirField)
                ->op("=")
                ->val($update["value"]);
            $this->createWhereFromFilters($query, [$childPk => $this->condition("in", $update["entities"])]);

            $this->doQuery($query, false);
        }
    }

    protected function saveManyToMany(
        array $relationship,
        $alias,
        Collection $collection,
        EntityMetadata $childMetadata,
        $ourValue,
        $ourField,
        $theirField
    ) {
        if (!empty($this->relationshipCascade[$alias])) {
            /** @var RepositoryInterface $childRepo */
            $childRepo = $this->relationshipCascade[$alias];

            // save all entities currently in the collection
            foreach ($collection->getAddedEntities() as $child) {
                $childRepo->save($child);
            }
        }

        // We will be doing operations on an intermediary join table. As Reposition works with EntityMetadata, we
        // need to create the metadata for the intermediary
        $intermediaryMetadata = $this->metadataProvider->getEntityMetadataForIntermediary(
            $relationship[EntityMetadata::METADATA_RELATIONSHIP_JOIN_TABLE]
        );

        // generate the relationship metadata used on this collection
        $intermediaryRelationship = [
            EntityMetadata::METADATA_RELATIONSHIP_TYPE => EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_ONE
        ];
        $manyToManyRelationships = [
            "ours" => $this->entityMetadata->getCollection() . "_" . $ourField,
            "theirs" => $childMetadata->getCollection() . "_" . $theirField
        ];

        foreach ($manyToManyRelationships as $relationshipAlias => $intermediaryOurField) {
            $intermediaryRelationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD] = $intermediaryOurField;
            $intermediaryRelationship[EntityMetadata::METADATA_RELATIONSHIP_ALIAS] = $relationshipAlias;
            $intermediaryMetadata->addRelationshipMetadata($this->entityMetadata->getEntity(), $intermediaryRelationship);
        }

        // process added and removed children
        $added = $collection->getAddedEntities();
        $removed = $collection->getRemovedEntities();

        if (!empty($added)) {
            $insert = $this->queryBuilder->save($intermediaryMetadata, ["saveType" => "insert"]);

            // create an array of values to use, for each entity we're adding
            foreach ($added as $child) {
                // we have to use the actual field names here as we're saving an array, rather than a class instance
                $entityArray = [
                    $manyToManyRelationships["ours"] => $ourValue,
                    $manyToManyRelationships["theirs"] => $childMetadata->getEntityValue($child, $theirField)
                ];
                $insert->entity($entityArray);
            }
            $this->doQuery($insert, false);
        }
        if (!empty($removed)) {
            $delete = $this->queryBuilder->delete($intermediaryMetadata);
            // delete rows where the parent value is X and the child value is in the list
            $filters = [
                "ours" => $ourValue,
                "theirs" => $this->condition("in", $removed)
            ];
            $this->createWhereFromFilters($delete, $filters);
            $this->doQuery($delete, false);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        // cascade delete operation for registered relationships
        foreach ($this->relationshipCascade as $alias => $childRepo) {
            /** @var RepositoryInterface $childRepo */
            $relationship = $this->entityMetadata->getRelationship($alias);
            // skip if this relationship is many to many
            if ($relationship[EntityMetadata::METADATA_RELATIONSHIP_TYPE] == EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY) {
                continue;
            }

            if (empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD])) {
                continue;
            }

            // build and execute a delete query for the relationship
            $childMetadata = $this->metadataProvider->getEntityMetadata($relationship[EntityMetadata::METADATA_ENTITY]);
            $childField = $relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD];

            $delete = $this->getDeleteQuery($id, $childMetadata, $childField);

            $this->doQuery($delete, false);
        }

        $query = $this->getDeleteQuery($id);
        return $this->doQuery($query, false);
    }

    protected function getDeleteQuery($value, EntityMetadata $metadata = null, $field = null)
    {
        if (empty($metadata)) {
            $metadata = $this->entityMetadata;
        }

        if (empty($field)) {
            $field = $metadata->getPrimaryKey();
        }

        $query = $this->queryBuilder->delete($metadata);
        $this->createWhereFromFilters($query, [$field => $value]);
        return $query;
    }

    public function deleteWithFilter(array $filters)
    {
        $query = $this->queryBuilder->delete($this->entityMetadata);
        $this->createWhereFromFilters($query, $filters);
        return $this->doQuery($query, false);
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $conditions = [], array $groupBy = [])
    {
        $query = $this->queryBuilder->find($this->entityMetadata)->aggregate("count", "*");

        $this->createWhereFromFilters($query, $conditions);

        if (!empty($groupBy)) {
            $query->group($groupBy);
        }

        return $this->doQuery($query, false);
    }

    public function condition($operator, $value, $inverted = false)
    {
        $operator = strtolower($operator);
        $inverted = (bool) $inverted;

        if ($operator == "in" && (!is_array($value) || empty($value))) {
            throw new \InvalidArgumentException("The value for an IN condition must be an array with at least one value");
        }
        if ($operator == "between" && (!is_array($value) || count($value) != 2)) {
            throw new \InvalidArgumentException("The value for a BETWEEN condition must be an array containing exactly two values");
        }

        return ["op" => $operator, "value" => $value, "inverted" => $inverted];
    }

    /**
     * @param TokenSequencerInterface $query
     * @param bool $createEntity
     *
     * @return object|array
     */
    protected function doQuery(TokenSequencerInterface $query, $createEntity = true)
    {
        $query->resetSequence();
        return $this->storage->query($query, $createEntity? $this->getEntityName(): "");
    }

    protected function createWhereFromFilters(TokenSequencerInterface $query, array $filters, $prefixFieldsWithCollection = false, $startWithWhere = true)
    {
        if (empty($filters)) {
            return;
        }

        if ($startWithWhere) {
            $query->where();
        }

        // normalise filters
        $normalisedFilters = [];
        foreach ($filters as $field => $conditions) {
            if (!is_array($conditions)) {
                $normalisedFilters[] = ["field" => $field, "op" => "=", "value" => $conditions];
            } else {
                if (isset($conditions["op"], $conditions["value"])) {
                    $conditions = [$conditions];
                }
                // condition should now be an array of operators and values
                foreach ($conditions as $singleCondition) {
                    if (!is_array($singleCondition) || !isset($singleCondition["op"], $singleCondition["value"])) {
                        throw new \InvalidArgumentException("The filter condition for the field '$field' is malformed");
                    }
                    $singleCondition["field"] = $field;
                    $normalisedFilters[] = $singleCondition;
                }
            }
        }

        // add filters
        $addLogicOperator = false;
        foreach ($normalisedFilters as $condition) {
            $this->addConditionToQuery($query, $condition, $prefixFieldsWithCollection, $addLogicOperator);
            $addLogicOperator = true;
        }

    }

    /**
     * @param TokenSequencerInterface $query
     * @param $condition
     * @param bool|false $prefixFieldWithCollection
     * @param bool|true $addLogicOperator
     * @throws RepositoryException
     */
    protected function addConditionToQuery(TokenSequencerInterface $query, $condition, $prefixFieldWithCollection = false, $addLogicOperator = true)
    {
        // normalise $condition
        $field = $condition["field"];
        $op = strtolower($condition["op"]);
        $value = $condition["value"];
        $inverted = !empty($condition["inverted"]);

        $related = $field;

        if ($query->getEntityMetadata()->hasRelationShip($field)) {
            $relationship = $query->getEntityMetadata()->getRelationship($field);
            if (empty($relationship) || $relationship[EntityMetadata::METADATA_RELATIONSHIP_TYPE] != EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_ONE) {
                $ourField = null;
            } else {
                $ourField = empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD])
                    ? null
                    : $relationship[EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD];
            }

            if (empty($ourField)) {
                throw new RepositoryException("No field could be found for the relationship '$field'");
            }

            $field = $ourField;
            $related = $relationship;
        }

        // if any value is an entity, get the value from the entity for the field we're looking for
        if (is_array($value)) {
            foreach ($value as $i => $entity) {
                $value[$i] = $this->getRelatedValueFromEntity($entity, $related);
            }
        } else {
            $value = $this->getRelatedValueFromEntity($value, $related);
        }

        if ($prefixFieldWithCollection && strpos($field, ".") === false) {
            $field = $this->collectionName . "." . $field;
        }

        // sprinkle with logic operators to taste
        if ($addLogicOperator) {
            // We do not need to allow for the OR operator. Complex queries that require OR are best written in custom methods
            $query->andL();
        }
        if ($inverted) {
            $query->notL();
        }

        // add condition to the query
        $query->ref($field)->op($op);
        switch($op) {
            case "in":
                $query->closure($value);
                break;
            case "between":
                $query->val($value[0])->andL()->val($value[1]);
                break;
            default;
                $query->val($value);
                break;
        }
    }

    private function getRelatedValueFromEntity($entity, $relationship)
    {
        // only process if this is an object
        if (is_object($entity)) {
            $childMetadata = $this->metadataProvider->getEntityMetadata($entity);
            $theirField = $relationship;
            if (is_array($relationship)) {
                $theirField = !empty($relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD])
                    ? $relationship[EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD]
                    : $childMetadata->getPrimaryKey();
            }
            $entity = $childMetadata->getEntityValue($entity, $theirField);
        }
        return $entity;
    }

    protected function addIncludes(TokenSequencerInterface $query, $includeRelationships = null)
    {
        $includeRelationships = is_null($includeRelationships)? $this->includeRelationshipsByDefault: $includeRelationships;
        if (!empty($includeRelationships)) {

            if (!is_array($includeRelationships) || isset($includeRelationships[0])) {
                $includeRelationships = ["this" => $includeRelationships];
            }

            $allRelationships = ["this" => $this->entityMetadata->getRelationships()];
            $relationships = [];
            $parents = [];

            // add all the relationship metadata we need into the allRelationships array
            foreach (array_keys($includeRelationships) as $alias) {
                if ($alias == "this") {
                    // already added the subject entity's relationships
                    continue;
                }
                foreach ($allRelationships as $subset) {
                    if (!empty($subset[$alias])) {
                        $relationshipEntity = $subset[$alias][EntityMetadata::METADATA_ENTITY];
                        $relationshipMetadata = $this->metadataProvider->getEntityMetadata($relationshipEntity);
                        $allRelationships[$alias] = $relationshipMetadata->getRelationships();
                        break;
                    }
                }
            }

            foreach ($includeRelationships as $alias => $includes) {
                if (empty($allRelationships[$alias])) {
                    continue;
                }
                $thisRelationships = $allRelationships[$alias];
                // if we have an array of includes, filter thisRelationships
                if (is_array($includes)) {
                    $thisRelationships = array_intersect_key($allRelationships[$alias], array_flip($includes));
                }
                // record the parent of each of the child aliases, so we can reference them when we include the entity
                foreach (array_keys($thisRelationships) as $childAlias) {
                    $parents[$childAlias] = $alias;
                }
                // add thisRelationships to the final list
                $relationships = array_replace($relationships, $thisRelationships);
            }

            // get the metadata for each entity and include it on the query
            foreach ($relationships as $alias => $relationship) {
                $metadata = $this->metadataProvider->getEntityMetadata($relationship[EntityMetadata::METADATA_ENTITY]);
                if ($alias == $metadata->getEntity()) {
                    $alias = "";
                }
                $parent = "";
                if (!empty($parents[$alias]) && $parents[$alias] != "this") {
                    $parent = $parents[$alias];
                }
                $query->includeEntity($metadata, $alias, $parent);
            }
        }
    }

} 