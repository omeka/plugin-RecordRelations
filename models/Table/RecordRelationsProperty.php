<?php

class Table_RecordRelationsProperty extends Omeka_Db_Table
{

    public function findByVocabAndPropertyName($vocabUri, $predName) {
        $db = get_db();
        $select = $this->getSelect()
            ->join(array($db->RecordRelationsVocabulary => $db->RecordRelationsVocabulary),
                              "$db->RecordRelationsVocabulary.namespace_uri = '$vocabUri'",
                              array())
            ->where("vocabulary_id = $db->RecordRelationsVocabulary.id")
            ->where("local_part = ?", $predName);

        $prop = $this->fetchObject($select);
        return $prop;
    }

}
