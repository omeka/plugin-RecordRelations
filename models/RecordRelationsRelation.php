<?php
class RecordRelationsRelation extends Omeka_Record_AbstractRecord
{
    public $id;
    public $subject_id;
    public $property_id;
    public $object_id;
    public $subject_record_type;
    public $object_record_type;
    public $timestamp;
    public $user_id;
    public $public;
    protected $namespace;
    protected $local_part;

    public function setProps($props)
    {
        foreach($props as $prop=>$value) {
            $this->$prop = $value;
        }
        if(isset($this->namespace) && isset($this->local_part)) {
            $this->_setPropertyIdFromParts($this->namespace , $this->local_part);
        }
    }

    public function save($throwIfInvalid = true)
    {
        //check to see if the triple already exists, from the same user
        if(empty($this->user_id)) {
            $currentUser = current_user();
            $this->user_id = $currentUser->id;
        }
        $count = $this->getTable()->count(array(
                                            'subject_id' => $this->subject_id,
                                            'object_id' => $this->object_id,
                                            'user_id' => $this->user_id,
                                            'subject_record_type' => $this->subject_record_type,
                                            'object_record_type' => $this->object_record_type,
                                            'property_id' => $this->property_id
                                        ));

        if($count == 0) {
            parent::save();
        }

    }

    public function deleteWithObject()
    {
        $this->_db->getTable($this->object_record_type)->find($this->object_id)->delete();
        $this->delete();
    }

    public function deleteWithSubject()
    {
        $this->_db->getTable($this->subject_record_type)->find($this->subject_id)->delete();
        $this->delete();
    }

    private function _setPropertyIdFromParts($vocab, $local_part)
    {
        $this->property_id = get_db()->getTable('RecordRelationsProperty')->findByVocabAndPropertyName($vocab, $local_part)->id;
    }
}

