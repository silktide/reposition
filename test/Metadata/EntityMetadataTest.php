<?php

namespace Silktide\Reposition\Tests\QueryBuilder;

use Silktide\Reposition\Metadata\EntityMetadata;
use Silktide\Reposition\Exception\MetadataException;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase {


    /**
     * @dataProvider invalidFieldMetadataProvider
     *
     * @param array $metadata
     * @param string $errorPartial
     */
    public function testInvalidFieldMetadata(array $metadata, $errorPartial = "")
    {
        $field = "test";
        $entityMetadata = new EntityMetadata("entity");
        try {
            $entityMetadata->addFieldMetadata($field, $metadata);
            $this->fail("Should have thrown an exception when trying to add invalid metadata");
        } catch (MetadataException $e) {
            if (!empty($errorPartial)) {
                $this->assertContains($errorPartial, $e->getMessage());
            }
        }
    }

    public function invalidFieldMetadataProvider()
    {
        return [
            [ // no metadata
                [],
                "metadata"
            ],
            [ // unrecognised metatdata keys
                ["blah" => "blah"],
                "metadata"
            ],
            [ // invalid type value
                [EntityMetadata::METADATA_FIELD_TYPE => "blah"],
                "invalid"
            ]
        ];
    }

    /**
     * @dataProvider validFieldMetadataProvider
     *
     * @param array $metadata
     */
    public function testValidFieldMetadata(array $metadata)
    {
        $field = "test";
        $entityMetadata = new EntityMetadata("entity");
        $entityMetadata->addFieldMetadata($field, $metadata);

        $fields = $entityMetadata->getFieldNames();
        $this->assertCount(1, $fields);
        $this->assertEquals($field, $fields[0]);
    }

    public function validFieldMetadataProvider()
    {
        $field = EntityMetadata::METADATA_FIELD_TYPE;
        $types = [
            EntityMetadata::FIELD_TYPE_BOOL,
            EntityMetadata::FIELD_TYPE_INT,
            EntityMetadata::FIELD_TYPE_FLOAT,
            EntityMetadata::FIELD_TYPE_STRING,
            EntityMetadata::FIELD_TYPE_ARRAY,
            EntityMetadata::FIELD_TYPE_DATETIME
        ];

        foreach ($types as $type) {
            yield [
                [$field => $type]
            ];
        }
    }

    /**
     * @dataProvider invalidRelationshipMetadataProvider
     *
     * @param array $metadata
     * @param string $errorPartial
     */
    public function testInvalidRelationshipMetadata(array $metadata, $errorPartial = "")
    {
        $entity = "test";
        $entityMetadata = new EntityMetadata("\\Silktide\\Reposition\\Metadata\\EntityMetadata");
        try {
            $entityMetadata->addRelationshipMetadata($entity, $metadata);
            $this->fail("Should have thrown an exception when trying to add invalid metadata");
        } catch (MetadataException $e) {
            if (!empty($errorPartial)) {
                $this->assertContains($errorPartial, $e->getMessage());
            }
        }
    }

    public function invalidRelationshipMetadataProvider()
    {
        $typeField = EntityMetadata::METADATA_RELATIONSHIP_TYPE;
        $propField = EntityMetadata::METADATA_RELATIONSHIP_PROPERTY;
        $ourField = EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD;
        $theirField = EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD;
        $joinField = EntityMetadata::METADATA_RELATIONSHIP_JOIN_TABLE;

        $o2o = EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_ONE;
        $o2m = EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_MANY;
        $m2m = EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY;

        return [
            [ // no property field
                [
                    $typeField => $o2o,
                    $theirField => "field"
                ]
            ],
            [ // no our/their field for One to One
                [
                    $typeField => $o2o,
                    $propField => "collection"
                ]
            ],
            [ // no their field for One to Many
                [
                    $typeField => $o2m,
                    $propField => "collection"
                ]
            ],
            [ // no join table field for Many to Many
                [
                    $typeField => $m2m,
                    $propField => "collection"
                ]
            ],
        ];
    }

    /**
     * @dataProvider validRelationshipMetadataProvider
     *
     * @param array $metadata
     */
    public function testValidRelationshipMetadata(array $metadata)
    {
        $metadata[EntityMetadata::METADATA_RELATIONSHIP_PROPERTY] = "collection";

        $entity = "Silktide\\Reposition\\Metadata\\EntityMetadata";
        $entityMetadata = new EntityMetadata("Silktide\\Reposition\\Metadata\\EntityMetadata");
        $entityMetadata->addRelationshipMetadata($entity, $metadata);

        $entities = $entityMetadata->getRelationships();
        $this->assertCount(1, $entities);
        $this->assertArrayHasKey($entity, $entities);
    }

    public function validRelationshipMetadataProvider()
    {
        $typeField = EntityMetadata::METADATA_RELATIONSHIP_TYPE;
        $ourField = EntityMetadata::METADATA_RELATIONSHIP_OUR_FIELD;
        $theirField = EntityMetadata::METADATA_RELATIONSHIP_THEIR_FIELD;
        $joinField = EntityMetadata::METADATA_RELATIONSHIP_JOIN_TABLE;

        $o2o = EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_ONE;
        $o2m = EntityMetadata::RELATIONSHIP_TYPE_ONE_TO_MANY;
        $m2m = EntityMetadata::RELATIONSHIP_TYPE_MANY_TO_MANY;

        return [
            [ // One to One, our field
                [
                    $typeField => $o2o,
                    $ourField => "field"
                ]
            ],
            [ // One to One, their field
                [
                    $typeField => $o2o,
                    $theirField => "field"
                ]
            ],
            [ // One to One, both fields
                [
                    $typeField => $o2o,
                    $ourField => "field",
                    $theirField => "field"
                ]
            ],
            [ // One to Many
                [
                    $typeField => $o2m,
                    $theirField => "field"
                ]
            ],
            [ // Many to Many
                [
                    $typeField => $m2m,
                    $ourField => "field",
                    $theirField => "field",
                    $joinField => "table"
                ]
            ]
        ];
    }

    /**
     * @dataProvider pkMetadataProvider
     *
     * @param $fieldMetadata
     * @param $expectedMetadata
     */
    public function testPublicKeyMetadata($fieldMetadata, $expectedMetadata)
    {
        $pk = "id";
        $metadata = new EntityMetadata("blah");
        $metadata->setPrimaryKey($pk);
        if (!is_null($fieldMetadata)) {
            $metadata->addFieldMetadata($pk, $fieldMetadata);
        }

        $pkMetadata = $metadata->getPrimaryKeyMetadata();
        foreach ($expectedMetadata as $field => $value) {
            $this->assertEquals($value, $pkMetadata[$field]);
        }
    }

    public function pkMetadataProvider()
    {
        $type = EntityMetadata::METADATA_FIELD_TYPE;
        $autoInc = EntityMetadata::METADATA_FIELD_AUTO_INCREMENTING;

        return [
            [ #0 no field metadata exists for the PK
                null,
                [
                    $type => EntityMetadata::FIELD_TYPE_INT,
                    $autoInc => true
                ]
            ],
            [ #1 no auto increment value specified
                [
                    $type => EntityMetadata::FIELD_TYPE_FLOAT
                ],
                [
                    $type => EntityMetadata::FIELD_TYPE_FLOAT,
                    $autoInc => true
                ]
            ],
            [ #2 auto increment value set
                [
                    $type => EntityMetadata::FIELD_TYPE_STRING,
                    $autoInc => false
                ],
                [
                    $type => EntityMetadata::FIELD_TYPE_STRING,
                    $autoInc => false
                ]
            ],
        ];
    }

}
 