<?php

class RecordRelationsPropertyTable extends Omeka_Db_Table
{

    public function findByVocabAndPropertyName($vocabUri, $predName) {
        $db = get_db();
        $select = $this->getSelect()
            ->columns('record_relations_properties.id')
            ->join(array('record_relations_vocabularies' => $db->RecordRelationsVocabulary),
                              "record_relations_vocabularies.namespace_uri = '$vocabUri'",
                              array())
            ->where('record_relations_properties.vocabulary_id = record_relations_vocabularies.id')
            ->where('local_part = ?', $predName);
        $prop = $this->fetchObject($select);
        return $prop;
    }

}
