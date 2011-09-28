<?php

class RecordRelationsRelationTest extends Omeka_Test_AppTestCase  {
    
    public function setUp() {
       parent::setUp();
        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp('RecordRelations');
        $this->_createRelation();
    }
    
    
    private function _createRelation() {
        $relation = new RecordRelationsRelation();
        $relation->subject_id = 1;
        $relation->object_id = 1;
        $relation->property_id = 1;
        $relation->subject_record_type = "Item";
        $relation->object_record_type = "Item";
        $relation->user_id = 1;
        $relation->save();
        $this->_relation = $relation;
    }
        
}