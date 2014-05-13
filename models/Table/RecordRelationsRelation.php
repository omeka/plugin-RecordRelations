<?php

class Table_RecordRelationsRelation extends Omeka_Db_Table
{
    protected $_targetAlias;
    protected $_targetTable;

    public function findOne($params)
    {
        $select = $this->getSelectForFindBy($params);
        return $this->fetchObject($select);
    }

    public function applySearchFilters($select, $params)
    {
        $rrrAlias = $this->getTableAlias();
        $columns = $this->getColumns();
        foreach($columns as $column) {
            if(array_key_exists($column, $params)) {
                $select->where("$rrrAlias.$column = ? ", $params[$column]);
            }
        }
        return $select;
    }

    /**
     *
     * Modifies the query options
     * @param $select
     * @param array $queryOps
     */
    public function applyQueryOptions($select, $queryOps)
    {
        if(isset($queryOps['limit']) && isset($queryOps['offset'])) {
            $select->limit($queryOps['limit'], $queryOps['offset']);
        }
        if(isset($queryOps['limit']) && !isset($queryOps['offset'])) {
            $select->limit($queryOps['limit']);
        }
        return $select;
    }
    /**
     * Finds records that are the subject of the record relations
     *
     * @param array $relationParams Filters for the relations to find
     * @param array $queryOps Options to modify the query, like limits.
     * @param array $subjectParams Filters on the subject record type
     * @throws Exception
     */

    public function findSubjectRecordsByParams($relationParams, $queryOps= array(), $subjectParams = array())
    {
        if(!isset($relationParams['subject_record_type'])) {
            throw new Exception("subject_record_type must be passed in parameters");
        }
        
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();
        $this->_targetTable = $db->getTable($relationParams['subject_record_type']);

        $this->_setTargetTable($relationParams['subject_record_type']);
        $select = $this->_getSelectForTargetRecords($relationParams, $queryOps, $subjectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                      "$rrrAlias.subject_id = {$this->_targetAlias}.id", array()
                      );

        return $this->_findTargetRecords($select, $queryOps);
    }

    /**
     * Finds records that are the object of the record relations
     *
     * @param array $relationParams Filters for the relations to find
     * @param array $queryOps Options to modify the query, like limits.
     * @param array $subjectParams Filters on the object record type
     * @throws Exception
     */

    public function findObjectRecordsByParams($relationParams, $queryOps=array(), $objectParams=array())
    {

        if(!isset($relationParams['object_record_type'])) {
            throw new Exception("object_record_type must be passed in parameters");
        }
        
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();

        $this->_setTargetTable($relationParams['object_record_type']);
        $select = $this->_getSelectForTargetRecords($relationParams, $queryOps, $objectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                      "$rrrAlias.object_id = {$this->_targetAlias}.id", array()
                      );
        return $this->_findTargetRecords($select, $queryOps);
    }

    public function countObjectRecordsByParams($relationParams, $queryOps=array(), $objectParams=array()) 
    {
        if(!isset($relationParams['object_record_type'])) {
            throw new Exception("object_record_type must be passed in parameters");
        }
        
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();
        $this->_setTargetTable($relationParams['object_record_type']);        
        $select = $this->_getSelectForCountTargetRecords($relationParams, $queryOps, $objectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                        "$rrrAlias.object_id = {$this->_targetAlias}.id", array()
        );
        $result = $db->fetchOne($select);
        return $result;
    }
    
    public function countSubjectRecordsByParams($relationParams, $queryOps=array(), $objectParams=array())
    {
        if(!isset($relationParams['subject_record_type'])) {
            throw new Exception("subject_record_type must be passed in parameters");
        }
        
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();
        $this->_setTargetTable($relationParams['subject_record_type']);
        $select = $this->_getSelectForCountTargetRecords($relationParams, $queryOps, $objectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                        "$rrrAlias.subject_id = {$this->_targetAlias}.id", array()
        );
        $result = $db->fetchOne($select);        
        return $result;        
    }
    
    private function _getSelectForCountTargetRecords($relationParams, $queryOps, $targetParams)
    {
        $select = $this->_targetTable->getSelectForCount($targetParams);
        $this->applySearchFilters($select, $relationParams);
        
        return $select;
    }
    
    private function _findTargetRecords($select, $queryOps = array())
    {         
        $targets = $this->_targetTable->fetchObjects($select);
        //@TODO: might need to be moved to applyQueryOptions?
        if(isset($queryOps['indexById']) && $queryOps['indexById']) {
            $returnArray = array();
            foreach($targets as $target) {
                $returnArray[$target->id] = $target;
            }
            return $returnArray;
        }
        return $targets;
    }
    
    private function _setTargetTable($tableName)
    {
        $this->_targetTable = $this->getDb()->getTable($tableName);
        $this->_targetAlias = $this->_targetTable->getTableAlias();
    }

    /**
     *
     * Abstracts out the jobs of findObjectRecordsByParams and findSubjectRecordsByParams
     * @param array $relationParams Filters for the relations to find
     * @param array $queryOps Options to modify the query, like limits.
     * @param array $subjectParams Filters on the object record type
     * @param string $targetType The target table name (either subject or object record type)
     */

    private function _getSelectForTargetRecords($relationParams, $queryOps = array(), $targetParams = array() )
    {
        $db = $this->getDb();
        
        $select = $this->_targetTable->getSelectForFindBy($targetParams);
        
        
        if(array_key_exists('sort_dir', $relationParams)) {
            $sortDir = $relationParams['sort_dir'];            
        }
        
        if(array_key_exists('sort_field', $relationParams)) {
            $sortField = $relationParams['sort_field'];        
        }
                
        if (isset($sortDir) && isset($sortField)) {
            $this->applySorting($select, $sortField, $sortDir);
        }        
        
        $this->applyQueryOptions($select, $queryOps);
        $this->applySearchFilters($select, $relationParams);
        
        //$this->_targetTable->applySearchFilters($select, $targetParams);        
        return $select;
    }
}