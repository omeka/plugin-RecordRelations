<?php


class Table_RecordRelationsVocabulary extends Omeka_Db_Table
{
    public function findByVocabularyUri($vocabUri)
    {
        $select = $this->getSelect()
            ->where('namespace_uri = ?', $vocabUri);
        return $this->fetchObject($select);
    }
}

