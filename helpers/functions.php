<?php

/**
 * Look up a property id by namespace and local_part
 *
 * @param string $namespace the namespace uri
 * @param string $local_part the local name
 */

function get_record_relations_property_id($namespace, $local_part)
{

    $prop = get_db()->getTable('RecordRelationsProperty')->findByVocabAndPropertyName($namespace, $local_part);
    if(!empty($prop)) {
        return $prop->id;
    }    
}

/**
 *
 * Install vocabularies and properties
 * @param array $data
 *
 * $data looks like:
 * <code>
 * array(
 *     array(
            'name' => 'Omeka',
            'description' => 'Omeka-based relations',
            'namespace_prefix' => 'omeka',
            'namespace_uri' => OMEKA,
            'properties' => array(
                array(
                    'local_part' => 'memberOf',
                    'label' => 'Member of',
                    'description' => 'A sioc:Users foaf:Person is associated with an omeka:Institution '
                )
            )
        );
 *
 * )
 *
 * </code>
 * See formal_vocabularies.php for a longer example
 */

function record_relations_install_properties($data) {
    $db = get_db();
    $vocabTable = $db->getTable('RecordRelationsVocabulary');
    $propertyTable = $db->getTable('RecordRelationsProperty');
    foreach($data as $propertyData) {
        //first, check the vocabulary exists by uri
        $vocabulary = $vocabTable->findByVocabularyUri($propertyData['namespace_uri']);
        if(empty($vocabulary)) {
            //Gratuitously steal from Jim Safley's work with Item Relations plugin
            //Install the formal vocabularies and their properties.
            $vocabulary = new RecordRelationsVocabulary;
            $vocabulary->name = $propertyData['name'];
            $vocabulary->description = $propertyData['description'];
            $vocabulary->namespace_prefix = $propertyData['namespace_prefix'];
            $vocabulary->namespace_uri = $propertyData['namespace_uri'];
            $vocabulary->custom = 0; //@TODO: is this needed/relevant?
            $vocabulary->save();
        }
        foreach($propertyData['properties'] as $property) {
            $propertyRecord = $propertyTable->findByVocabAndPropertyName($propertyData['namespace_uri'], $property['local_part']);
            if(empty($propertyRecord)) {

                $propertyRecord = new RecordRelationsProperty;
                $propertyRecord->vocabulary_id = $vocabulary->id;
                $propertyRecord->local_part = $property['local_part'];
                $propertyRecord->label = $property['label'];
                $propertyRecord->description = $property['description'];
                $propertyRecord->save();
            }
        }
    }
}

/**
 * Delete relations from the relations table (NOT the target subject or object tables)
 * @param array $params
 */

function record_relations_delete_relations($params)
{
    $relTable = get_db()->getTable('RecordRelationsRelation');
    $rels = $relTable->findBy($params);
    foreach($rels as $rel) {
        $rel->delete();
    }
}
