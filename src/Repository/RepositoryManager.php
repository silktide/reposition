<?php

namespace Silktide\Reposition\Repository;

use Silktide\Reposition\Exception\RepositoryException;
use Silktide\Reposition\Exception\MetadataException;
use Silktide\Reposition\Hydrator\EntityFactoryInterface;
use Silktide\Reposition\Storage\StorageInterface;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;
use Silktide\Reposition\Metadata\EntityMetadataFactoryInterface;
use Silktide\Reposition\QueryBuilder\QueryBuilderInterface;

/**
 *
 */
class RepositoryManager implements EntityMetadataProviderInterface
{

    protected $repositoryNamespaces = [];

    protected $repositoryCache = [];

    protected $defaultStorage;

    protected $defaultQueryBuilder;

    protected $metadataFactory;

    protected $entityFactory;

    public function __construct(
        StorageInterface $storage,
        QueryBuilderInterface $queryBuilder,
        EntityMetadataFactoryInterface $metadataFactory,
        EntityFactoryInterface $entityFactory,
        array $repositoryNamespaces,
        array $repositories = []
    ) {
        $this->defaultStorage = $storage;
        $this->defaultQueryBuilder = $queryBuilder;
        $this->metadataFactory = $metadataFactory;
        $this->entityFactory = $entityFactory;
        $this->repositoryNamespaces = $repositoryNamespaces;
        $this->repositoryNamespaces[] = ""; // for classes with no namespace

        foreach ($repositories as $repository) {
            $this->addRepository($repository);
        }

        if (!$this->defaultStorage->hasEntityMetadataProvider()) {
            $this->defaultStorage->setEntityMetadataProvider($this);
        }
    }

    public function addRepository(RepositoryInterface $repository)
    {
        $this->repositoryCache[$repository->getEntityName()] = $repository;
    }

    public function getRepositoryFor($entity)
    {
        if (!is_string($entity)) {
            if (!is_object($entity)) {
                throw new RepositoryException("The supplied entity was not a class name or an object instance");
            }
            $entity = get_class($entity);
        } elseif (!class_exists($entity)) {
            throw new RepositoryException("The supplied entity class '$entity' does not exist");
        }
        if (empty($this->repositoryCache[$entity])) {
            // try to autoload the repository based on entity class name
            $repoFqcn = "";

            // first check in the same namespace as the entity
            if (class_exists($entity . "Repository")) {
                $repoFqcn = $entity . "Repository";
            } else {
                // Create the repo class name. Strip the entity namespace and add "Repository"
                $entityClass = (strpos($entity, "\\") !== false)
                    ? substr($entity, strrpos($entity, "\\") + 1)
                    : $entity;
                $repoClass = $entityClass . "Repository";

                // check each registered repository namespace for the repository class
                foreach ($this->repositoryNamespaces as $namespace) {
                    $namespace = rtrim($namespace, "\\") . "\\";
                    if (class_exists($namespace . $repoClass)) {
                        $repoFqcn = $namespace . $repoClass;
                        break;
                    }
                }
            }

            if (empty($repoFqcn)) {
                throw new RepositoryException("Could not find a repository for the entity '$entity'");
            }

            $this->repositoryCache[$entity] = new $repoFqcn(
                $this->metadataFactory->createMetadata($entity),
                $this->defaultQueryBuilder,
                $this->defaultStorage,
                $this,
                $this->entityFactory
            );
        }
        return $this->repositoryCache[$entity];
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadata($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        try {
            $repository = $this->getRepositoryFor($entity);
        } catch (RepositoryException $e) {
            // check for base classes
            $parent = $entity;
            while($parent = get_parent_class($parent)) {
                try {
                    $repository = $this->getRepositoryFor($parent);
                    break;
                } catch (RepositoryException $e) {

                }
            }
            if (empty($repository)) {
                throw new MetadataException("Cannot get metadata, no repository class exists for '$entity'");
            }
        }

        if (!$repository instanceof MetadataRepositoryInterface) {
            throw new MetadataException("Cannot get metadata for '$entity', the repository class for the entity does not supply metadata information.");
        }

        return $repository->getEntityMetadata();
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadataForIntermediary($collection)
    {
        $metadata = $this->metadataFactory->createEmptyMetadata();
        $metadata->setCollection($collection);
        return $metadata;
    }

} 