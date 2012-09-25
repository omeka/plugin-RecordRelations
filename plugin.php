<?php


require_once PLUGIN_DIR . '/RecordRelations/RecordRelationsPlugin.php';
require_once PLUGIN_DIR . '/RecordRelations/helpers/functions.php';


//define some prefixes/namespaces
define('SIOC', 'http://rdfs.org/sioc/ns#');
define('FOAF', 'http://xmlns.org/foaf/0.1/');
define('DCTERMS', 'http://purl.org/dc/terms/');
define('BIBO', 'http://purl.org/ontology/bibo/');
define('FRBR', 'http://purl.org/vocab/frbr/core#');
define('OMEKA', 'http://omeka.org/vocab/');

define('RECORD_RELATIONS_PLUGIN_DIR', dirname(__FILE__));


$recordRelationsPlugin = new RecordRelationsPlugin();
$recordRelationsPlugin->setUp();