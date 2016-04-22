<?php

namespace Silktide\Reposition\Hydrator;

use Silktide\Reposition\Normaliser\NormaliserInterface;

/**
 *
 */
interface HydratorInterface 
{

    /**
     * @param array $data
     * @param array $options
     * @return object
     */
    public function hydrate(array $data, array $options = []);

    /**
     * @param array $data
     * @param array $options
     * @return array
     */
    public function hydrateAll(array $data, array $options = []);

    /**
     * @param NormaliserInterface $normaliser
     */
    public function setNormaliser(NormaliserInterface $normaliser);

} 