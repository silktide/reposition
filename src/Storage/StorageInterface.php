<?php

namespace Silktide\Reposition\Storage;

use Silktide\Reposition\QueryBuilder\TokenSequencerInterface;
use Silktide\Reposition\Metadata\EntityMetadataProviderInterface;
use Silktide\Reposition\Storage\Logging\QueryLogProcessorInterface;
use Silktide\Reposition\Storage\Logging\ErrorLogProcessorInterface;

/**
 *
 */
interface StorageInterface
{

    const NEW_INSERT_ID_RETURN_FIELD = "pk";

    /**
     * @param TokenSequencerInterface $query
     * @param array $options
     * @return object
     */
    public function query(TokenSequencerInterface $query, array $options = ["output" => "normalise"]);

    /**
     * @param EntityMetadataProviderInterface $provider
     */
    public function setEntityMetadataProvider(EntityMetadataProviderInterface $provider);

    /**
     * @return bool
     */
    public function hasEntityMetadataProvider();

    /**
     * @param QueryLogProcessorInterface $processor
     */
    public function setQueryLogProcessor(QueryLogProcessorInterface $processor);

    /**
     * @param ErrorLogProcessorInterface $processor
     */
    public function setErrorLogProcessor(ErrorLogProcessorInterface $processor);

} 