<?php
require_once(PLUGIN_DIR . '/RecordRelations/models/RecordRelationsRelation.php');

abstract class RelatableRecord extends Omeka_Record_AbstractRecord {

    private $_relation;
    protected $property_id;
    protected $namespace;
    protected $local_part;
    protected $subject_record_type;
    protected $object_record_type;
    protected $_isSubject; // false if this will be the object of a relation
    protected $public = true;

    protected function construct() {
        $this->_relation = new RecordRelationsRelation();
        $this->property_id = $this->findVocabPropId($this->namespace, $this->local_part);
        $relData = $this->getRelationData();
        $this->setRelationData($relData);
    }

    public function setRelationData($data) {
        foreach($data as $prop=>$value) {
            $this->_relation->$prop = $value;
        }
    }

    /**
     *
     * Get the full array of default relations, or just a specific one
     * @param string $relation Optional. The default relation data to return
     * @return mixed Array if $relation is null, String if a valid relation
     */

    public function getRelationData($relation = null)
    {
        if( ! is_null($relation)) {
            return $this->$relation;
        }
        $relationsArray = array();
        $relationsArray['subject_record_type'] = $this->subject_record_type;
        $relationsArray['object_record_type'] = $this->object_record_type;
        $relationsArray['property_id'] = $this->property_id;
        return $relationsArray;
    }

    public function isSubject() {
        return $this->_isSubject;
    }

    public function deleteWithRelation()
    {
        $params =  $this->getRelationData();
        if($this->_isSubject) {
            $params['subject_id'] = $this->id;

        } else {
            $params['object_id'] = $this->id;
        }
        $rel = $this->getTable('RecordRelationsRelation')->findBy($params);
        $rel[0]->delete();
        $this->delete();

    }

    public function getPropertyByVocabAndPropertyName($vocabUri, $propLocalPart)
    {
        return get_db()->getTable('RecordRelationsProperty')->findByVocabAndPropertyName($vocabUri, $propLocalPart);
    }

    public function getRelation()
    {
        return $this->_relation;
    }

    public function findVocabPropId($vocab = null, $prop = null)
    {
        if(is_null($vocab)) {
            $vocab = $this->namespace;
        }

        if(is_null($prop)) {
            $prop = $this->local_part;
        }
        $propertyRecord = $this->getTable('RecordRelationsProperty')->findByVocabAndPropertyName($vocab, $prop);
        if(!$propertyRecord) {
            throw new Exception("$vocab$prop does not exist");
        }
        return $propertyRecord->id;
    }

    protected function beforeSave($args)
    {
        //There's not a good way to avoid creating the relation in construct, so
        //here unset it if the record, and hence the relation, already exist
        if( $this->exists() ) {
            $this->_relation = null;
        }
    }

    protected function afterSave($args)
    {
        if(!empty($this->_relation)) {
            if($this->_isSubject) {
               $this->_relation->subject_id = $this->id;
            } else {
                $this->_relation->object_id = $this->id;
            }
            $this->_relation->save();
        }
        parent::afterSave($args);
    }

    /**
     * returns an array of the most common parameters for queries
     */

	static function defaultParams()
	{
	    $params = array();
	    $params['subject_record_type'] = $this->subject_record_type;
	    $params['object_record_type'] = $this->object_record_type;
	    $params['property_id'] = get_record_relations_property_id($this->namespace, $this->local_part);
	    if($this->_isSubject) {
	        $params['subject_id'] = $this->id;
	    } else {
	        $params['object_id'] = $this->id;
	    }
	    $params['public'] = true;
	    return $params;
	}
}