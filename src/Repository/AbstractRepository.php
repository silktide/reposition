<?php

namespace Silktide\Reposition\Repository;

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

    /**
     * {@inheritDoc}
     */
    public function find($id, $includeRelationships = null)
    {
        $query = $this->queryBuilder->find($this->entityMetadata);
        $this->addIncludes($query, $includeRelationships);
        $query->where()
            ->ref($this->getCollectionName() . "." . $this->entityMetadata->getPrimaryKey())
            ->op("=")
            ->val($id);
        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(array $filters, array $sort = [], $limit = 0, array $options = [], $includeRelationships = null)
    {
        $query = $this->queryBuilder->find($this->entityMetadata);
        $this->addIncludes($query, $includeRelationships);

        $this->createWhereFromFilters($query, $filters);

        if (!empty($sort)) {
            $query->sort($sort);
        }

        if (!empty($limit)) {
            $query->limit($limit);
        }

        return $this->doQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function save($entity, array $options = [])
    {
        $query = $this->queryBuilder->save($this->entityMetadata, $options)->entity($entity);
        try {
            return $this->doQuery($query, false);
        } catch (\PDOException $e) {
            $pkMetadata = $this->entityMetadata->getPrimaryKeyMetadata();
            // if this entity has an auto incrementing PK, or the error is not about PK conflicts, re-throw the error
            if ($pkMetadata[EntityMetadata::METADATA_FIELD_AUTO_INCREMENTING] == true || $e->errorInfo[0] != self::ANSI_DUPLICATE_KEY_ERROR_CODE) {
                throw $e;
            }

            // this is a duplicate key on a collection with a PK that does not auto increment.
            // force the save to be an update
            $query->setOption("saveType", "update");
            return $this->doQuery($query, false);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $query = $this->queryBuilder->delete($this->entityMetadata)
            ->where()
            ->ref($this->entityMetadata->getPrimaryKey())
            ->op("=")
            ->val($id);
        return $this->doQuery($query, false);
    }

    public function deleteWithFilter(array $filters)
    {
        $query = $this->queryBuilder->delete($this->entityMetadata);
        $this->createWhereFromFilters($query, $filters);
        return $this->doQuery($query);
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

    protected function createWhereFromFilters(TokenSequencerInterface $query, array $filters, $startWithWhere = true)
    {
        if (empty($filters)) {
            return;
        }

        if ($startWithWhere) {
            $query->where();
        }

        // we need to prepend "andL" to all but the first field, so
        // get the values for the last field and remove it from the array
        reset($filters);
        $firstField = key($filters);
        $firstValue = array_pop($filters);

        // filter first field
        $this->addComparisonToQuery($query, $firstField, $firstValue);

        // create filters
        foreach ($filters as $field => $value) {
            $query->andL();
            $this->addComparisonToQuery($query, $field, $value);
        }

    }

    protected function addComparisonToQuery(TokenSequencerInterface $query, $field, $value, $prefixFieldWithCollection = false)
    {
        if ($this->entityMetadata->hasRelationShip($field)) {
            $relationship = $this->entityMetadata->getRelationship($field);
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
        }

        if ($prefixFieldWithCollection) {
            $field = $this->collectionName . "." . $field;
        }

        $query->ref($field)->op("=")->val($value);
    }

    protected function addIncludes(TokenSequencerInterface $query, $includeRelationships)
    {
        $includeRelationships = is_null($includeRelationships)? $this->includeRelationshipsByDefault: $includeRelationships;
        if (!empty($includeRelationships)) {
            $relationships = $this->entityMetadata->getRelationships();

            // if includeRelationships is an array, filter out relationships not in the array
            if (is_array($includeRelationships)) {
                $relationships = array_intersect_key($relationships, array_flip($includeRelationships));
            }

            foreach ($relationships as $alias => $relationship) {
                $metadata = $this->metadataProvider->getEntityMetadata($relationship[EntityMetadata::METADATA_ENTITY]);
                if ($alias == $metadata->getEntity()) {
                    $alias = "";
                }
                $query->includeEntity($metadata, $alias);
            }
        }
    }

} 