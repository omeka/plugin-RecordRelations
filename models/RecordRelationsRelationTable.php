<?php

class RecordRelationsRelationTable extends Omeka_Db_Table
{
    protected $_alias = 'rr';
    protected $_targetAlias;

    public function findOne($params)
    {
        $select = $this->getSelectForFindBy($params);
        return $this->fetchObject($select);
    }

    public function applySearchFilters($select, $params)
    {
        $columns = $this->getColumns();
        foreach($columns as $column) {
            if(array_key_exists($column, $params)) {
                $select->where($this->_alias . ". $column = ? ", $params[$column]);
            }
        }
        return $select;
    }

    /**
     *
     * Applies filters on the subject or object record type
     * @param $select
     * @param array $params
     */
    public function applyTargetSearchFilters($select, $params)
    {
        foreach($params as $column=>$value) {
            $select->where($this->_targetAlias .".$column = ?", $value);
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
        $targetTable = $db->getTable($relationParams['subject_record_type']);
        if(!isset($relationParams['subject_record_type'])) {
            throw new Exception("subject_record_type must be passed in parameters");
        }
        $select = $this->getSelectForTargetRecords($relationParams, $targetTable, $queryOps, $subjectParams);
        $select->join(array('rr'=>$db->RecordRelationsRelation),
                      "rr.subject_id = {$this->_targetAlias}.id", array()
                      );

        return $this->findTargetRecords($select, $targetTable, $queryOps);
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
        if(!isset($relationParams['object_record_type'])) {
            throw new Exception("object_record_type must be passed in parameters");
        }
        $targetTable = $db->getTable($relationParams['object_record_type']);
        $select = $this->getSelectForTargetRecords($relationParams, $targetTable, $queryOps, $objectParams);
        $select->join(array('rr'=>$db->RecordRelationsRelation),
                      "rr.object_id = {$this->_targetAlias}.id", array()
                      );
        return $this->findTargetRecords($select, $targetTable, $queryOps);
    }

    private function findTargetRecords($select, $targetTable, $queryOps = array())
    {
         //if it's a count query, need to execute the query a little differently and return
        if(isset($queryOps['count']) && $queryOps['count']) {
            return $this->getDb()->fetchOne($select);
        }

        $targets = $targetTable->fetchObjects($select);

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

    private function getSelectForTargetRecords($relationParams, $targetTable, $queryOps = array(), $targetParams = array() )
    {
        $db = $this->getDb();
        $this->_targetAlias = $targetTable->getTableAlias();
        //need to get the select here based on whether it is for count
        if(isset($queryOps['count']) && $queryOps['count']) {
            $select = $targetTable->getSelectForCount();
        } else {
            $select = $targetTable->getSelect();
        }

        $select = $this->applySearchFilters($select, $relationParams);

        $select = $this->applyTargetSearchFilters($select, $targetParams);
        return $select;

    }
}