<?php

namespace Silktide\Reposition\Metadata;

use Silktide\Reposition\Exception\MetadataException;

class EntityMetadata
{

    // metadata array keys
    const METADATA_FIELD_TYPE = "type";
    const METADATA_FIELD_AUTO_INCREMENTING = "auto incrementing";
    const METADATA_FIELD_GETTER = "getter";
    const METADATA_FIELD_SETTER = "setter";
    const METADATA_FIELD_CAN_BE_NULL = "can be null";

    const METADATA_INDEX_TYPE = "index type";
    const METADATA_INDEX_NAME = "index name";
    const METADATA_INDEX_FIELDS = "index fields";

    const METADATA_RELATIONSHIP_TYPE = "type";
    const METADATA_RELATIONSHIP_ALIAS = "alias";
    const METADATA_RELATIONSHIP_PROPERTY = "property";
    const METADATA_RELATIONSHIP_OUR_FIELD = "our field";
    const METADATA_RELATIONSHIP_THEIR_FIELD = "their field";
    const METADATA_RELATIONSHIP_JOIN_TABLE = "join table";
    const METADATA_RELATIONSHIP_GETTER = self::METADATA_FIELD_GETTER;
    const METADATA_ENTITY = "entity";

    // field types
    const FIELD_TYPE_STRING = "string";
    const FIELD_TYPE_INT = "int";
    const FIELD_TYPE_FLOAT = "float";
    const FIELD_TYPE_BOOL = "bool";
    const FIELD_TYPE_ARRAY = "array";
    const FIELD_TYPE_DATETIME = "datetime";

    // key types
    const FIELD_INDEX_TYPE_NONE = "none";
    const FIELD_INDEX_TYPE_PRIMARY = "primary";
    const FIELD_INDEX_TYPE_UNIQUE = "unique";

    // relationship types
    const RELATIONSHIP_TYPE_ONE_TO_ONE = "one to one";
    const RELATIONSHIP_TYPE_MANY_TO_ONE = "one to one"; // "many to one" is the same a "one to one" from a child entity's point of view
    const RELATIONSHIP_TYPE_ONE_TO_MANY = "one to many";
    const RELATIONSHIP_TYPE_MANY_TO_MANY = "many to many";

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var string
     */
    protected $collection;

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
     * @param string $primaryKey
     */
    public function __construct($entity, $primaryKey = null)
    {
        $this->entity = $entity;
        $this->primaryKey = empty($primaryKey)? "id": $primaryKey;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getPrimaryKeyMetadata()
    {
        $fieldMetadata = !empty($this->fields[$this->primaryKey])
            ? $this->fields[$this->primaryKey]
            : [self::METADATA_FIELD_TYPE => self::FIELD_TYPE_INT]; // primary keys default to integer

        if (!isset($fieldMetadata[self::METADATA_FIELD_AUTO_INCREMENTING])) {
            // primary keys are auto incrementing by default
            $fieldMetadata[self::METADATA_FIELD_AUTO_INCREMENTING] = true;
        }

        return $fieldMetadata;
    }

    public function setEntityValue($entity, $property, $value)
    {
        return $this->callPropertyMethod($entity, $property, self::METADATA_FIELD_SETTER, [$value]);
    }

    public function getEntityValue($entity, $property)
    {
        return $this->callPropertyMethod($entity, $property, self::METADATA_FIELD_GETTER);
    }

    protected function callPropertyMethod($entity, $property, $methodType, $args = [])
    {
        $propertyMetadata = null;
        if (empty($this->fields[$property])) {
            // look in the relationships
            foreach ($this->relationships as $relationship) {
                if ($relationship[self::METADATA_RELATIONSHIP_PROPERTY] == $property) {
                    $propertyMetadata = $relationship;
                    break;
                }
            }
            if (empty($propertyMetadata)) {
                throw new MetadataException("The field '$property' for the entity '{$this->entity}' has no metadata");
            }
        } else {
            $propertyMetadata = $this->fields[$property];
        }

        if (empty($propertyMetadata[$methodType])) {
            throw new MetadataException("The field '$property' has no $methodType information set");
        }

        $method = $propertyMetadata[$methodType];
        return call_user_func_array([$entity, $method], $args);
    }

    /**
     * @param string $table
     */
    public function setCollection($table)
    {
        $this->collection = $table;
    }

    /**
     * @return string
     */
    public function getCollection()
    {
        return $this->collection;
    }

    public function addFieldMetadata($name, array $metadata)
    {
        $finalMetadata = [];
        foreach ($metadata as $type => $value) {
            switch ($type) {
                // field type (enumerated)
                case self::METADATA_FIELD_TYPE:
                    $this->validateFieldType($name, $value);
                    $finalMetadata[self::METADATA_FIELD_TYPE] = $value;
                    break;
                // boolean values
                case self::METADATA_FIELD_AUTO_INCREMENTING:
                case self::METADATA_FIELD_CAN_BE_NULL:
                    $finalMetadata[$type] = (bool) $value;
                    break;
                // string values
                case self::METADATA_FIELD_GETTER:
                case self::METADATA_FIELD_SETTER:
                    // TODO: get method validation working with discriminated methods
                    //$this->validateClassMethod($value, $type);
                    $finalMetadata[$type] = $value;
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

    public function getField($name)
    {
        return empty($this->fields[$name])? null: $this->fields[$name];
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

    protected function validateClassMethod($method, $type)
    {
        if (!method_exists($this->entity, $method)) {
            throw new MetadataException("The $type method '$method' does not exist for the entity '{$this->entity}'");
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
        if (!class_exists($entity)) {
            throw new MetadataException("The entity class '$entity' does not exist");
        }
        if (!isset($metadata[self::METADATA_RELATIONSHIP_TYPE])) {
            throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a relationship type");
        }

        $generatedGetter = false;
        if (!empty($this->entity)) {
            if (!isset($metadata[self::METADATA_RELATIONSHIP_PROPERTY])) {
                throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying the property of the parent entity that the relationship refers to");
            }
            if (!property_exists($this->entity, $metadata[self::METADATA_RELATIONSHIP_PROPERTY])) {
                throw new MetadataException("Cannot add relationship metadata for '$entity'. The property specified for the parent entity doesn't exist: '{$metadata[self::METADATA_RELATIONSHIP_PROPERTY]}'");
            }
            if (!isset($metadata[self::METADATA_RELATIONSHIP_GETTER])) {
                $metadata[self::METADATA_RELATIONSHIP_GETTER] = "get" . ucfirst($metadata[self::METADATA_RELATIONSHIP_PROPERTY]);
                $generatedGetter = true;
            }
            if (!method_exists($this->entity, $metadata[self::METADATA_RELATIONSHIP_GETTER])) {
                throw new MetadataException("Could not find the" . ($generatedGetter? " generated": "") . " getter method '{$metadata[self::METADATA_RELATIONSHIP_GETTER]}' on the entity '{$this->entity}'");
            }
        } else {
            $metadata[self::METADATA_RELATIONSHIP_PROPERTY] = "";
            $metadata[self::METADATA_RELATIONSHIP_GETTER] = "";
        }
        $type = $metadata[self::METADATA_RELATIONSHIP_TYPE];
        $finalMetadata[self::METADATA_ENTITY] = $entity;
        $finalMetadata[self::METADATA_RELATIONSHIP_TYPE] = $type;
        $finalMetadata[self::METADATA_RELATIONSHIP_PROPERTY] = $metadata[self::METADATA_RELATIONSHIP_PROPERTY];
        $finalMetadata[self::METADATA_RELATIONSHIP_GETTER] = $metadata[self::METADATA_RELATIONSHIP_GETTER];
        switch ($type) {
            case self::RELATIONSHIP_TYPE_MANY_TO_MANY:
                $finalMetadata[self::METADATA_RELATIONSHIP_JOIN_TABLE] = $this->getJoinTable($entity, $metadata);
                $finalMetadata[self::METADATA_RELATIONSHIP_THEIR_FIELD] = $this->getTheirField($entity, $metadata, false);
                $finalMetadata[self::METADATA_RELATIONSHIP_OUR_FIELD] = $this->getOurField($entity, $metadata, false);
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
        if (!empty($metadata[self::METADATA_RELATIONSHIP_ALIAS])) {
            $entity = $metadata[self::METADATA_RELATIONSHIP_ALIAS];
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

    public function getRelationship($entity)
    {
        return empty($this->relationships[$entity])? null: $this->relationships[$entity];
    }

    public function hasRelationship($entity)
    {
        return !empty($this->relationships[$entity]);
    }

    protected function getJoinTable($entity, array $metadata)
    {
        if (!isset($metadata[self::METADATA_RELATIONSHIP_JOIN_TABLE])) {
            throw new MetadataException("Cannot add many-to-many relationship metadata for '$entity' without specifying a join table");
        }
        return $metadata[self::METADATA_RELATIONSHIP_JOIN_TABLE];
    }

    protected function getTheirField($entity, array $metadata, $required = true)
    {
        if (empty($metadata[self::METADATA_RELATIONSHIP_THEIR_FIELD])) {
            if ($required) {
                throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a the field representing the entity's foreign key");
            }
            return null;
        }
        return $metadata[self::METADATA_RELATIONSHIP_THEIR_FIELD];
    }

    protected function getOurField($entity, array $metadata, $required = true)
    {
        if (!isset($metadata[self::METADATA_RELATIONSHIP_OUR_FIELD])) {
            if ($required) {
                throw new MetadataException("Cannot add relationship metadata for '$entity' without specifying a foreign key field");
            }
            return null;
        }
        return $metadata[self::METADATA_RELATIONSHIP_OUR_FIELD];
    }

} 