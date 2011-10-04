<?php

function record_relations_show_objects($record, $params = array())
{
    $params['subject_id'] = $record->id;
    $params['subject_record_type'] = get_class($record);
    $params['subject_id'] = 1;
    $params['subject_record_type'] = 'User';
    $db = get_db();
    //dig up all the possible object_record_types and predicates
    $rrTable = $db->getTable('RecordRelationsRelation');
    $select = $rrTable->getSelect()
      //  ->columns(array('object_record_type', 'property_id'))
        ->where("subject_id = ?", $params['subject_id'])
        ->where("subject_record_type = ?", $params['subject_record_type']);
            
  //  $relations = $select->query()->fetchAll();
    $sql = "
SELECT DISTINCT `omeka_record_relations_relations`.`object_record_type` , `omeka_record_relations_relations`.`property_id`
FROM `omeka_record_relations_relations`
WHERE `omeka_record_relations_relations`.`subject_id` = 1

";
$poPairs = $db->query($sql)->fetchAll();
print_r($poPairs);
$object_records = array();
foreach($poPairs as $poPair) {
    $params['property_id'] = $poPair['property_id'];
    $params['object_record_type'] = $poPair['object_record_type'];
    $object_records[$poPair['object_record_type']] = $rrTable->findObjectRecordsByParams($params);
}
$html = '';
foreach($object_records as $type=>$records) {
    $html .= "<h2>$type</h2>";
    foreach($records as $record) {
        $html .= "<p>" . $record->recordUri() . "</p>";
    }
    
}
echo $html;
//return $object_records;
    
}

/**
 * Look up a property id by namespace and local_part
 *
 */

function record_relations_property_id($namespace, $local_part)
{
    
    $prop = get_db()->getTable('RecordRelationsProperty')->findByVocabAndPropertyName($namespace, $local_part);
    return $prop->id;
}

/**
 *
 * Install vocabularies and properties
 * @param $data
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