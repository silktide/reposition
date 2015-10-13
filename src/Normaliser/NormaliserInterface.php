<?php

namespace Silktide\Reposition\Normaliser;

/**
 * Interface for translating data to and from a specific database format
 */
interface NormaliserInterface 
{

    /**
     * format data into the DB format
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function normalise(array $data, array $options = []);

    /**
     * format DB data into standard format
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    public function denormalise(array $data, array $options = []);

} 