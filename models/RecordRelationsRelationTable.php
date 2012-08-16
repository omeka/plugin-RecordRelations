<?php

class RecordRelationsRelationTable extends Omeka_Db_Table
{
    protected $_targetAlias;
    protected $targetTable;

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
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();
        $this->targetTable = $db->getTable($relationParams['subject_record_type']);
        if(!isset($relationParams['subject_record_type'])) {
            throw new Exception("subject_record_type must be passed in parameters");
        }
        $select = $this->getSelectForTargetRecords($relationParams, $queryOps, $subjectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                      "$rrrAlias.subject_id = {$this->_targetAlias}.id", array()
                      );

        return $this->findTargetRecords($select, $queryOps);
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
        $db = $this->getDb();
        $rrrAlias = $this->getTableAlias();
        if(!isset($relationParams['object_record_type'])) {
            throw new Exception("object_record_type must be passed in parameters");
        }
        $this->targetTable = $db->getTable($relationParams['object_record_type']);
        $select = $this->getSelectForTargetRecords($relationParams, $queryOps, $objectParams);
        $select->join(array($rrrAlias=>$db->RecordRelationsRelation),
                      "$rrrAlias.object_id = {$this->_targetAlias}.id", array()
                      );
        return $this->findTargetRecords($select, $queryOps);
    }

    private function findTargetRecords($select, $queryOps = array())
    {

         //if it's a count query, need to execute the query a little differently and return
        if(isset($queryOps['count']) && $queryOps['count']) {
            return $this->getDb()->fetchOne($select);
        }
        $targets = $this->targetTable->fetchObjects($select);
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

    /**
     *
     * Abstracts out the jobs of findObjectRecordsByParams and findSubjectRecordsByParams
     * @param array $relationParams Filters for the relations to find
     * @param array $queryOps Options to modify the query, like limits.
     * @param array $subjectParams Filters on the object record type
     * @param string $targetType The target table name (either subject or object record type)
     */

    private function getSelectForTargetRecords($relationParams, $queryOps = array(), $targetParams = array() )
    {
        $db = $this->getDb();
        $this->_targetAlias = $this->targetTable->getTableAlias();
        //need to get the select here based on whether it is for count
        if(isset($queryOps['count']) && $queryOps['count']) {
            $select = $this->targetTable->getSelectForCount();
        } else {
            $select = $this->targetTable->getSelectForFindBy($targetParams);
        }

        
        if(array_key_exists('sort_dir', $relationParams)) {
            $sortDir = $relationParams['sort_dir'];            
        }
        
        if(array_key_exists('sort_field', $relationParams)) {
            $sortField = $relationParams['sort_field'];        
        }
                
        if ($sortDir && $sortField) {
            $this->applySorting($select, $sortField, $sortDir);
        }        

        $this->applySearchFilters($select, $relationParams);
        
        $this->targetTable->applySearchFilters($select, $targetParams);        
        return $select;
    }
}