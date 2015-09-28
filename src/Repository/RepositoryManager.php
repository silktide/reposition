<?php

namespace Silktide\Reposition\Repository;

use Silktide\Reposition\Exception\RepositoryException;
use Silktide\Reposition\Exception\MetadataException;
use Silktide\Reposition\Storage\StorageInterface;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;

/**
 *
 */
class RepositoryManager implements EntityMetadataProviderInterface
{

    protected $repositoryNamespaces = [];

    protected $repositoryCache = [];

    protected $defaultStorage;

    protected $metadataFactory;

    public function __construct(StorageInterface $storage, EntityMetadataFactoryInterface $metadataFactory, array $repositoryNamespaces, array $repositories = [])
    {
        $this->defaultStorage = $storage;
        $this->metadataFactory = $metadataFactory;
        $this->repositoryNamespaces = $repositoryNamespaces;
        $this->repositoryNamespaces[] = ""; // for classes with no namespace
        foreach ($repositories as $repository) {
            $this->addRepository($repository);
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

            // strip the namespace and add "Repository"
            $entityClass = (strpos($entity, "\\") !== false)
                ? substr($entity, strrpos($entity, "\\") + 1)
                : $entity;
            $repoClass = $entityClass . "Repository";

            // check each registered repository namespace for the repository class
            foreach ($this->repositoryNamespaces as $namespace) {
                $repoFqcn = rtrim($namespace, "\\") . "\\" . $repoClass;
                if (class_exists($repoFqcn)) {
                    $this->repositoryCache[$entity] = new $repoFqcn(
                        $this->metadataFactory->create($entity),
                        $this->defaultStorage->getQueryBuilder(),
                        $this->defaultStorage
                    );
                    break;
                }
            }

            // error on no match
            if (empty($this->repositoryCache[$entity])) {
                throw new RepositoryException("Could not find a repository for the entity '$entity'");
            }
        }
        return $this->repositoryCache[$entity];
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityMetadata($entity)
    {
        $repository = $this->getRepositoryFor($entity);

        if (!$repository instanceof MetadataRepositoryInterface) {
            throw new MetadataException("Cannot get metadata for '$entity', the repository class for the entity does not supply metadata information.");
        }

        return $repository->getEntityMetadata();
    }

} 