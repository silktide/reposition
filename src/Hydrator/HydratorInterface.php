<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Reposition\Hydrator;
use Silktide\Reposition\Normaliser\NormaliserInterface;

/**
 *
 */
interface HydratorInterface 
{

    /**
     * @param array $data
     * @param string $entityClass
     * @param array $options
     * @return object
     */
    public function hydrate(array $data, $entityClass, array $options = []);

    /**
     * @param array $data
     * @param string $entityClass
     * @param array $options
     * @return array
     */
    public function hydrateAll(array $data, $entityClass, array $options = []);

    /**
     * @param NormaliserInterface $normaliser
     */
    public function setNormaliser(NormaliserInterface $normaliser);

} 