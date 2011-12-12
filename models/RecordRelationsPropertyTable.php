<?php

class RecordRelationsPropertyTable extends Omeka_Db_Table
{

    protected $_alias = 'rp';
    
    public function findByVocabAndPropertyName($vocabUri, $predName) {
        $db = get_db();
        $select = $this->getSelect()
            ->columns('rp.id')
            ->join(array('rv' => $db->RecordRelationsVocabulary),
                              "rv.namespace_uri = '$vocabUri'",
                              array())
            ->where('rp.vocabulary_id = rv.id')
            ->where('local_part = ?', $predName);
        $prop = $this->fetchObject($select);
        return $prop;
    }

}
