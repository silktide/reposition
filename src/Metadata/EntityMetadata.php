<?php

namespace Silktide\Reposition\Metadata;

use Silktide\Reposition\Exception\MetadataException;

class EntityMetadata
{

    // metadata arraykeys
    const METADATA_FIELD_TYPE = "type";
    const METADATA_RELATIONSHIP_TYPE = "type";
    const METADATA_RELATIONSHIP_OUR_FIELD = "our field";
    const METADATA_RELATIONSHIP_THEIR_FIELD = "their field";
    const METADATA_RELATIONSHIP_JOIN_TABLE = "join table";

    // field types
    const FIELD_TYPE_STRING = "string";
    const FIELD_TYPE_INT = "int";
    const FIELD_TYPE_FLOAT = "float";
    const FIELD_TYPE_BOOL = "bool";
    const FIELD_TYPE_ARRAY = "array";
    const FIELD_TYPE_DATETIME = "datetime";

    // relationship types
    const RELATIONSHIP_TYPE_ONE_TO_ONE = "one to one";
    const RELATIONSHIP_TYPE_ONE_TO_MANY = "one to many";
    const RELATIONSHIP_TYPE_MANY_TO_MANY = "many to many";

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $relationships = [];

    /**
     * @param string $entity
     */
    public function construct($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    public function addFieldMetadata($name, array $metadata)
    {
        $finalMetadata = [];
        foreach ($metadata as $type => $value) {
            switch ($type) {
                case self::METADATA_FIELD_TYPE:
                    $this->validateFieldType($name, $value);
                    $finalMetadata[self::METADATA_FIELD_TYPE] = $value;
                    break;
            }
        }
        if (empty($finalMetadata)) {
            throw new MetadataException("No valid metadata fields were found");
        }
        $this->fields[$name] = $finalMetadata;
    }

    public function getFieldNames()
    {
        return array_keys($this->fields);
    }

    public function getFields()
    {
        return $this->fields;
    }

    protected function validateFieldType($name, $type)
    {
        switch ($type) {
            case self::FIELD_TYPE_BOOL:
            case self::FIELD_TYPE_INT:
            case self::FIELD_TYPE_FLOAT:
            case self::FIELD_TYPE_STRING:
            case self::FIELD_TYPE_DATETIME:
            case self::FIELD_TYPE_ARRAY:
                break;
            default:
                throw new MetadataException("The field type metadata for '$name' is invalid: '$type'");
        }
    }

    public function getFieldType($field)
    {
        if (!isset($this->fields[$field][self::METADATA_FIELD_TYPE])) {
            throw new MetadataException("Type metadata does not exist for '$field'");
        }
        return $this->fields[$field][self::METADATA_FIELD_TYPE];
    }

    public function addRelationshipMetadata($entity, $metadata)
    {
        $finalMetadata = [];
        if (!isset($metadata[self::METADATA_RELATIONSHIP_TYPE])) {
            throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a relationship type");
        }
        $type = $metadata[self::METADATA_RELATIONSHIP_TYPE];
        $finalMetadata[self::METADATA_RELATIONSHIP_TYPE] = $type;
        switch ($type) {
            case self::RELATIONSHIP_TYPE_MANY_TO_MANY:
                $finalMetadata[self::METADATA_RELATIONSHIP_JOIN_TABLE] = $this->getJoinTable($entity, $metadata);
                $finalMetadata[self::METADATA_RELATIONSHIP_THEIR_FIELD] = $this->getTheirField($entity, $metadata);
                $finalMetadata[self::METADATA_RELATIONSHIP_OUR_FIELD] = $this->getOurField($entity, $metadata);
                break;
            case self::RELATIONSHIP_TYPE_ONE_TO_MANY:
                $finalMetadata[self::METADATA_RELATIONSHIP_THEIR_FIELD] = $this->getTheirField($entity, $metadata);
                $finalMetadata[self::METADATA_RELATIONSHIP_OUR_FIELD] = $this->getOurField($entity, $metadata, false);
                break;
            case self::RELATIONSHIP_TYPE_ONE_TO_ONE:
                $finalMetadata[self::METADATA_RELATIONSHIP_THEIR_FIELD] = $this->getTheirField($entity, $metadata, false);
                $finalMetadata[self::METADATA_RELATIONSHIP_OUR_FIELD] = $this->getOurField($entity, $metadata, empty($finalMetadata[self::METADATA_RELATIONSHIP_THEIR_FIELD]));
                break;
        }
        $this->relationships[$entity] = $finalMetadata;
    }

    public function getRelatedEntities()
    {
        return array_keys($this->relationships);
    }

    public function getRelationships()
    {
        return $this->relationships;
    }

    protected function getJoinTable($entity, array $metadata) {
        if (!isset($metadata[self::METADATA_RELATIONSHIP_JOIN_TABLE])) {
            throw new MetadataException("Cannot add many-to-many relationship metadata for '$entity' without specifying a join table");
        }
        return $metadata[self::METADATA_RELATIONSHIP_JOIN_TABLE];
    }

    protected function getTheirField($entity, array $metadata, $required = true) {
        if (empty($metadata[self::METADATA_RELATIONSHIP_THEIR_FIELD])) {
            if ($required) {
                throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a the field representing the entity's foreign key");
            }
            return null;
        }
        return $metadata[self::METADATA_RELATIONSHIP_THEIR_FIELD];
    }

    protected function getOurField($entity, array $metadata, $required = true) {
        if (!isset($metadata[self::METADATA_RELATIONSHIP_OUR_FIELD])) {
            if ($required) {
                throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a foreign key field");
            }
            return null;
        }
        return $metadata[self::METADATA_RELATIONSHIP_OUR_FIELD];
    }

} 