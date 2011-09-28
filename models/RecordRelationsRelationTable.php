<?php

class RecordRelationsRelationTable extends Omeka_Db_Table
{
    
    protected $_alias = 'rr';
    
    public function applySearchFilters($select, $params)
    {
        $paramNames = array('id',
                        'subject_id',
                        'property_id',
                        'object_id',
                        'subject_record_type',
                        'object_record_type',
                        'user_id',
                        );
                            
        foreach($paramNames as $paramName) {
            if (isset($params[$paramName])) {
                $select->where('rr.' . $paramName . ' = ?', array($params[$paramName]));
            }
        }
        return $select;
    }

    public function findSubjectRecordsByParams($params)
    {
        if(!isset($params['subject_record_type'])) {
            throw new Exception("subject_record_type must be passed in parameters");
        }
        $db = $this->getDb();
        $objectTable = $db->getTable($params['object_record_type']);
        $select = $objectTable->getSelect();

        foreach($params as $column=>$value) {
            $select->where("$column = ? ", $value);
        }
        $alias = $objectTable->getTableAlias();
        $select->join(array('rr'=>$db->RecordRelationsRelation),
                      "rr.subject_id = $alias.id", array()
                      );
        return $objectTable->fetchObjects($select);
    }
    
    public function findObjectRecordsByParams($params)
    {
        if(!isset($params['object_record_type'])) {
            throw new Exception("object_record_type must be passed in parameters");
        }
        $db = $this->getDb();
        $objectTable = $db->getTable($params['object_record_type']);
        $select = $objectTable->getSelect();

        foreach($params as $column=>$value) {
            $select->where("$column = ? ", $value);
        }
        $alias = $objectTable->getTableAlias();
        $select->join(array('rr'=>$db->RecordRelationsRelation),
                      "rr.object_id = $alias.id", array()
                      );
        return $objectTable->fetchObjects($select);
    }

}