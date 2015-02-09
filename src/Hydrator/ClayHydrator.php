<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Hydrator;
use Silktide\Reposition\Exception\HydrationException;

/**
 *
 */
class ClayHydrator implements HydratorInterface
{

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, $entityClass, array $options = [])
    {
        $this->checkClass($entityClass);
        return new $entityClass($data);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateAll(array $data, $entityClass, array $options = [])
    {
        $this->checkClass($entityClass);
        $collection = [];
        foreach ($data as $i => $subData) {
            $collection[$i] = new $entityClass($subData);
        }
        return $collection;
    }

    /**
     * @param string $class
     * @throws HydrationException
     */
    protected function checkClass($class)
    {
        if (!is_string($class)) {
            throw new HydrationException("Invalid class name (not a string)");
        }
        if (!class_exists($class)) {
            throw new HydrationException("Could not hydrate. the class '$class' does not exist");
        }
        // TODO: check this class actually uses Clay
    }

} 