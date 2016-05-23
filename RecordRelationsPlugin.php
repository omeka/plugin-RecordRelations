<?php

require_once PLUGIN_DIR . '/RecordRelations/helpers/functions.php';


//define some prefixes/namespaces
define('SIOC', 'http://rdfs.org/sioc/ns#');
define('FOAF', 'http://xmlns.org/foaf/0.1/');
define('DCTERMS', 'http://purl.org/dc/terms/');
define('BIBO', 'http://purl.org/ontology/bibo/');
define('FRBR', 'http://purl.org/vocab/frbr/core#');
define('OMEKA', 'http://omeka.org/vocab/');

define('RECORD_RELATIONS_PLUGIN_DIR', dirname(__FILE__));



class RecordRelationsPlugin extends Omeka_Plugin_AbstractPlugin
{

    protected $_hooks = array('install', 'uninstall', 'upgrade');


    public function hookInstall()
    {

        $db = get_db();

        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->RecordRelationsVocabulary` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` text,
                `namespace_prefix` varchar(100) NOT NULL,
                `namespace_uri` varchar(200) NOT NULL,
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
            `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `user_id` INT( 10 ) UNSIGNED NOT NULL,
            `public` INT( 1 ) UNSIGNED DEFAULT 1
            ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
        ";

        $db->query($sql);

        $formalVocabularies = include RECORD_RELATIONS_PLUGIN_DIR . '/formal_vocabularies.php';
        record_relations_install_properties($formalVocabularies);

    }

    public function hookUpgrade($args)
    {
        $old = $args['old_version'];
        $new = $args['new_version'];

        if (version_compare($old, '2.0.1', '<')) {
            $db = get_db();
            $sql = "ALTER TABLE `$db->RecordRelationsRelation`  CHANGE `timestamp` `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
            $db->query($sql);
        }
    }

    public function hookUninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsRelation` ";
        $db->query($sql);

        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsProperty` ";
        $db->query($sql);

        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsVocabulary` ";
        $db->query($sql);


    }
}
