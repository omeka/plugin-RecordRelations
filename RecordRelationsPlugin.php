<?php

//define some prefixes/namespaces
define('SIOC', 'http://rdfs.org/sioc/ns#');
define('FOAF', 'http://xmlns.org/foaf/0.1/');
define('DCTERMS', 'http://purl.org/dc/terms/');
define('BIBO', 'http://purl.org/ontology/bibo/');
define('FRBR', 'http://purl.org/vocab/frbr/core#');
define('OMEKA', 'http://omeka.org/vocab/');

define('RECORD_RELATIONS_PLUGIN_DIR', dirname(__FILE__));

if(class_exists('Omeka_Plugin_Abstract')) {

class RecordRelationsPlugin extends Omeka_Plugin_Abstract
{
    
    protected $_hooks = array('install', 'uninstall');
    
    
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
            `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
            `user_id` INT( 10 ) UNSIGNED NOT NULL,
            `public` INT( 1 ) UNSIGNED DEFAULT 1
            ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;
        ";
        
        $db->query($sql);
    
        $formalVocabularies = include RECORD_RELATIONS_PLUGIN_DIR . '/formal_vocabularies.php';
        record_relations_install_properties($formalVocabularies);
        
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

} else {
    

class RecordRelationsPlugin
{
    
    protected $_hooks = array('install', 'uninstall');
    protected $_filters = array();
    protected $_options = array();
    
    public function install()
    {
        
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
    
        $formalVocabularies = include RECORD_RELATIONS_PLUGIN_DIR . '/formal_vocabularies.php';
        record_relations_install_properties($formalVocabularies);
        
    }
    
    public function uninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsRelation` ";
        $db->query($sql);
        
        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsProperty` ";
        $db->query($sql);
        
        $sql = "DROP TABLE IF EXISTS `$db->RecordRelationsVocabulary` ";
        $db->query($sql);
            
            
    }
    
    public function __construct()
    {
        $this->_db = Omeka_Context::getInstance()->getDb();
        $this->_addHooks();
        $this->_addFilters();
    }
    
    /**
     * Set options with default values.
     *
     * Plugin authors may want to use this convenience method in their install
     * hook callback.
     */
    protected function _installOptions()
    {
        $options = $this->_options;
        if (!is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            // Don't set options without default values.
            if (!is_string($name)) {
                continue;
            }
            set_option($name, $value);
        }
    }
    
    /**
     * Delete all options.
     *
     * Plugin authors may want to use this convenience method in their uninstall
     * hook callback.
     */
    protected function _uninstallOptions()
    {
        $options = self::$_options;
        if (!is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            delete_option($name);
        }
    }
    
    /**
     * Validate and add hooks.
     */
    private function _addHooks()
    {
        $hookNames = $this->_hooks;
        if (!is_array($hookNames)) {
            return;
        }
        foreach ($hookNames as $hookName) {
            $functionName = Inflector::variablize($hookName);
            if (!is_callable(array($this, $functionName))) {
                throw new Omeka_Plugin_Exception('Hook callback "' . $functionName . '" does not exist.');
            }
            add_plugin_hook($hookName, array($this, $functionName));
        }
    }
    
    /**
     * Validate and add filters.
     */
    private function _addFilters()
    {
        $filterNames = $this->_filters;
        if (!is_array($filterNames)) {
            return;
        }
        foreach ($filterNames as $filterName) {
            $functionName = Inflector::variablize($filterName);
            if (!is_callable(array($this, $functionName))) {
                throw new Omeka_Plugin_Exception('Filter callback "' . $functionName . '" does not exist.');
            }
            add_filter($filterName, array($this, $functionName));
        }
    }
    
}
    
    
}
