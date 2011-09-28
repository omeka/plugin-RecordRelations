<?php

//define some prefixes
define('SIOC', 'http://rdfs.org/sioc/ns#');
define('FOAF', 'http://xmlns.org/foaf/0.1/');
define('DCTERMS', 'http://purl.org/dc/terms/');
define('BIBO', 'http://purl.org/ontology/bibo/');
define('FRBR', 'http://purl.org/vocab/frbr/core#');
define('OMEKA', 'http://omeka.org/vocab/');

function record_relations_install() {
    
    $db = get_db();

    $sql = "
        CREATE TABLE IF NOT EXISTS `$db->RecordRelationsVocabulary` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `namespace_prefix` varchar(100) NOT NULL,
            `namespace_uri` varchar(200) DEFAULT NULL,
            `custom` BOOLEAN NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $db->query($sql);
        
    $sql = "
        CREATE TABLE IF NOT EXISTS `$db->RecordRelationsProperty` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `vocabulary_id` int(10) unsigned NOT NULL,
            `local_part` varchar(100) NOT NULL,
            `label` varchar(100) DEFAULT NULL,
            `description` text,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $db->query($sql);
    
    $sql = "
        CREATE TABLE IF NOT EXISTS `$db->RecordRelationsRelation` (
        `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `subject_id` INT( 10 ) UNSIGNED NOT NULL ,
        `property_id` INT( 10 ) UNSIGNED NOT NULL ,
        `object_id` INT( 10 ) UNSIGNED NOT NULL ,
        `subject_record_type` TINYTEXT NOT NULL ,
        `object_record_type` TINYTEXT NOT NULL ,
        `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
        `user_id` INT( 10 ) UNSIGNED NOT NULL,
        `public` INT( 1 ) UNSIGNED DEFAULT 1
        ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
    ";
    
    $db->query($sql);

    $formalVocabularies = include 'formal_vocabularies.php';
    record_relations_install_properties($formalVocabularies);
}


function record_relations_uninstall() {
    $db = get_db();
    $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsRelation` ";
    $db->query($sql);
    
    $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsProperty` ";
    $db->query($sql);
    
    $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsVocabulary` ";
    $db->query($sql);
}


/**
 *
 * Enter description here ...
 * @param $data
 *
 * $data looks like:
 * <code>
   array(
       array(
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
        )
   );
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
            //check if the property already exists
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