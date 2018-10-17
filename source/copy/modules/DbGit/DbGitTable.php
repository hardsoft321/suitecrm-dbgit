<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

require_once __DIR__.'/DbGit.php';
require_once __DIR__.'/DbGitTableDefs.php';
require_once __DIR__.'/records/DbGitTableRecord.php';
require_once __DIR__.'/records/SugarBeanDbGitTableRecord.php';
require_once __DIR__.'/records/SugarRelDbGitTableRecord.php';

class DbGitTable extends DbGitTableDefs
{
    protected $diffsToSave = array();
    protected $diffsToDelete = array();
    protected $notFoundDbKeys = array();
    protected $diffsToSave2 = array();

    public function clearPlan()
    {
        $this->diffsToSave = array();
        $this->diffsToDelete = array();
        $this->notFoundDbKeys = array();
        $this->diffsToSave2 = array();
    }

    public function getTableData($ignoreDuplicates = false)
    {
        global $db;
        $tableName = $this->getTableName();
        $tableDefs = $this->getTableDefs();
        $table_data = array();
        $sql = "SELECT * FROM {$tableName} WHERE 1=1 "
            .(isset($tableDefs['condition'])
            ? (!empty($tableDefs['condition']) ? " AND {$tableDefs['condition']}" : "")
            : " AND deleted = 0");
        if(!empty($tableDefs['orderby'])) {
            $sql .= " ORDER BY ".$tableDefs['orderby'];
        }
        $dbRes = $db->query($sql);
        $nonUniqueKeys = array();
        while($row = $db->fetchByAssoc($dbRes ,false)) {
            $hashRow = self::hashArray($row);
            $normalRow = $this->normalizeRow($row, array($hashRow => $row));
            if(empty($normalRow)) {
                continue;
            }
            $normalKey = $this->getRowKey($normalRow);
            if(empty($normalKey)) {
                throw new Exception("Empty keys : ".print_r($normalRow, true));
            }
            $hash = self::hashArray($normalKey);
            if(isset($table_data[$hash])) {
                $nonUniqueKeys[] = $normalRow;
            }
            $table_data[$hash] = array(
                'hash' => $hash,
                'key' => $normalKey,
                'data' => $normalRow,
            );
        }
        if(!empty($nonUniqueKeys) && !$ignoreDuplicates) {
            throw new Exception("Some keys are not unique: ".print_r($nonUniqueKeys, true)." <- Non unique keys");
        }
        return $table_data;
    }

    public static function hashArray($arr)
    {
        return sha1(serialize($arr));
    }

    protected function normalizeRow($row, $searchPath = array())
    {
        $normalRow = array();
        $tableDefs = $this->getTableDefs();
        foreach($tableDefs['fields'] as $field) {
            $name = $field['name'];
            $type = !empty($field['type']) ? $field['type'] : 'varchar';
            if($type === 'varchar') {
                $normalRow[$name] = $row[$name];
            }
            elseif($type === 'id') {
                $normalRow[$name] = '';
                if(!empty($row[$name])) {
                    $relatedTableName = '';
                    if(!empty($field['relationship_role_column'])) {
                        $relatedModule = $row[$field['relationship_role_column']];
                        if(!empty($relatedModule)) {
                            $relatedTableName = DbGit::getTableNameForModule($relatedModule);
                        }
                    }
                    else {
                        $relatedTableName = $field['table'];
                    }
                    if(!empty($relatedTableName)) {
                        $relatedTable = DbGit::getTable($relatedTableName);
                        $relatedRow = $relatedTable->fetchById($row[$name]); //TODO: use condition      
                        if($relatedRow) {
                            self::checkSearchPath($searchPath, $relatedRow);
                            $relatedHash = self::hashArray($relatedRow);
                            $searchPath1 = $searchPath;
                            $searchPath1[$relatedHash] = $relatedRow;
                            $normalRelatedRow = $relatedTable->normalizeRow($relatedRow, $searchPath1);
                            if($normalRelatedRow) {
                                $relatedKey = $relatedTable->getRowKey($normalRelatedRow);
                                $normalRow[$name] = $relatedKey;
                            }
                        }
                    }
                }
                if(empty($normalRow[$name]) && !empty($field['required'])) {
                    return false;
                }
            }
            else {
                throw new Exception("Unknown field type {$type} for field {$name}");
            }
        }
        return $normalRow;
    }

    protected function specifyRow($normalRow, $searchPath, $cmd = '')
    {
        $row = array();
        $tableDefs = $this->getTableDefs();
        $notFoundDbKeys = array();
        foreach($normalRow as $name => $normalValue) {
            $field = $tableDefs['fields'][$name];
            $type = !empty($field['type']) ? $field['type'] : 'varchar';
            if($type === 'varchar') {
                $row[$name] = $normalRow[$name];
            }
            elseif($type === 'id') {
                $row[$name] = '';
                if(!empty($normalRow[$name])) {
                    $relatedTableName = '';
                    if(!empty($field['relationship_role_column'])) {
                        $relatedModule = $normalRow[$field['relationship_role_column']];
                        if(!empty($relatedModule)) {
                            $relatedTableName = DbGit::getTableNameForModule($relatedModule);
                        }
                    }
                    else {
                        $relatedTableName = $field['table'];
                    }
                    $relatedTable = DbGit::getTable($relatedTableName);
                    try {
                        self::checkSearchPath($searchPath, $normalRow[$name]);
                        $relatedHash = self::hashArray($normalRow[$name]);
                        $searchPath1 = $searchPath;
                        $searchPath1[$relatedHash] = $normalRow[$name];
                        $relatedRow = $relatedTable->specifyRow($normalRow[$name], $searchPath1);
                    }
                    catch(RelatedNotFoundException $ex) {
                        $notFoundDbKeys = array_merge($notFoundDbKeys, $ex->notFoundDbKeys);
                    }
                    $relatedRecord = $relatedTable->newRecord();
                    $relatedRecord->retrieveByFields($relatedRow); //TODO: use condition      
                    if($relatedRecord->isLoaded()) {
                        $row[$name] = $relatedRecord->getId();
                    }
                    if(isset(DbGit::$deleteDbPlan[$relatedHash.$relatedTableName]) && $cmd !== DbGit::$DELETE_CMD) {
                        $row[$name] = '';
                    }
                    if(empty($row[$name])) {
                        $hash = self::hashArray($normalRow[$name]);
                        $notFoundDbKeys[$hash] = array(
                            'table' => $relatedTableName,
                            'hash' => self::hashArray($normalRow[$name]),
                            'key' => $normalRow[$name],
                        );
                    }
                }
            }
            else {
                throw new Exception("Unknown field type {$type} for field {$name}");
            }
        }
        if(!empty($notFoundDbKeys)) {
            throw new RelatedNotFoundException($notFoundDbKeys);
        }
        return $row;
    }

    protected static function checkSearchPath($searchPath, $newKey)
    {
        $newHash = self::hashArray($newKey); //TODO: use table name in key
        if(isset($searchPath[$newHash])) {
            throw new Exception("Recursive loop found: ".var_export($searchPath, true).", ".var_export($newKey, true));
        }
        if(count($searchPath) > 16) {
            throw new Exception("Too much recursion: ".var_export($searchPath, true));
        }
    }

    protected function fetchById($id)
    {
        global $db;
        $dbRes = $db->query("SELECT * FROM ".$this->getTableName()." WHERE id = '".$db->quote($id)."'");
        return $db->fetchByAssoc($dbRes, false);
    }

    public function getRowKey($row)
    {
        $key = array();
        $tableDefs = $this->getTableDefs();
        $indices = reset($tableDefs['indices']);
        foreach($indices['fields'] as $name) {
            $key[$name] = $row[$name];
        };
        return $key;
    }

    public function scanPlan($plan)
    {
        foreach($plan as $diff) {
            $iCMD = 0; $iFROM = 1; $iTO = 2;

            if(!empty($diff[$iFROM])) { //перевычисляем $from
                if(empty($diff[$iFROM]['data']) && !empty($diff[$iFROM]['key'])) {
                    $diff[$iFROM]['data'] = $diff[$iFROM]['key'];
                }
                $diff[$iFROM]['key'] = $this->getRowKey($diff[$iFROM]['data']);
                $diff[$iFROM]['hash'] = self::hashArray($diff[$iFROM]['key']);
            }

            if(!empty($diff[$iTO])) { //перевычисляем $to
                if(!empty($diff[$iFROM])) {
                    foreach($diff[$iFROM]['key'] as $key => $value) {
                        if(!array_key_exists($key, $diff[$iTO]['data'])) {
                            $diff[$iTO]['data'][$key] = $value;
                        }
                    }
                    foreach($diff[$iFROM]['data'] as $key => $value) {
                        if(!array_key_exists($key, $diff[$iTO]['data'])) {
                            $diff[$iTO]['data'][$key] = $value;
                        }
                    }
                }
                $diff[$iTO]['key'] = $this->getRowKey($diff[$iTO]['data']);
                $diff[$iTO]['hash'] = self::hashArray($diff[$iTO]['key']);
            }

            if(!empty($diff[$iTO])) {
                $this->diffsToSave[$diff[$iTO]['hash']] = $diff;
            }
            else if(!empty($diff[$iFROM])) {
                $this->diffsToDelete[$diff[$iFROM]['hash']] = $diff;
            }
        }
    }

    public function specifyDeletePlan()
    {
        $notFoundDbKeys = array();
        $diffsToDelete2 = array();
        foreach($this->diffsToDelete as $diff) {
            list($cmd, $from, $to) = $diff;
            $tableDefs = $this->getTableDefs();
            $record = $this->newRecord();

            $normalKey = $from['key'];
            try {
                $hash = self::hashArray($normalKey);
                $key = $this->specifyRow($normalKey, array($hash => $normalKey), DbGit::$DELETE_CMD);
            }
            catch(RelatedNotFoundException $ex) {
                $notFoundDbKeys = array_merge($notFoundDbKeys, $ex->notFoundDbKeys);
                continue;
            }
            $record->retrieveByFields($key);

            DbGit::$deleteDbPlan[$from['hash'].$tableDefs['table']] = array(
                'table' => $tableDefs['table'],
                'diff' => $diff,
                'record' => $record,
            );
        }

        if(!empty($notFoundDbKeys)) {
            throw new Exception("Data not found: ".var_export($notFoundDbKeys, true));
        }
        $count1 = count($this->diffsToDelete);
        $count2 = count($diffsToDelete2);
        $this->diffsToDelete = $diffsToDelete2;
        return $count1 - $count2;
    }

    public function specifySavePlan()
    {
        $this->notFoundDbKeys = array();
        foreach($this->diffsToSave as $diff) {
            list($cmd, $from, $to) = $diff;
            $tableDefs = $this->getTableDefs();
            $record = $this->newRecord();

            try {
                if(!empty($from)) {
                    $normalKey = $from['key'];
                    $hash = self::hashArray($normalKey);
                    try {
                        $key = $this->specifyRow($normalKey, array($hash => $normalKey));
                    }
                    catch(RelatedNotFoundException $ex) {
                        $this->searchNotFoundDbKeysInPlan($ex->notFoundDbKeys, $diff, $record);
                    }
                }
                $rowTo = $this->specifyRow($to['data'], array($to['hash'] => $to['key']));
                DbGit::$saveDbPlan[$to['hash'].$tableDefs['table']] = array(
                    'table' => $tableDefs['table'],
                    'diff' => $diff,
                    'record' => $record,
                    'rowTo' => $rowTo,
                );
            }
            catch(RelatedNotFoundException $ex) {
                $this->searchNotFoundDbKeysInPlan($ex->notFoundDbKeys, $diff, $record);
            }
        }

        if(!empty($this->notFoundDbKeys)) {
            throw new Exception("Data not found: ".var_export($this->notFoundDbKeys, true));
        }
        $count1 = count($this->diffsToSave);
        $count2 = count($this->diffsToSave2);
        $this->diffsToSave = $this->diffsToSave2;
        $this->diffsToSave2 = array();
        return $count1 - $count2;
    }

    protected function searchNotFoundDbKeysInPlan($notFoundDbKeys, $diff, $record)
    {
        $allInPlan = true;
        $anyNotFound = false;
        foreach($notFoundDbKeys as $dbKey) {
            if(!isset(DbGit::$saveDbPlan[$dbKey['hash'].$dbKey['table']]) || isset(DbGit::$deleteDbPlan[$dbKey['hash'].$dbKey['table']])) {
                $allInPlan = false;
                $relDbTable = DbGit::getTable($dbKey['table']);
                if(!isset($relDbTable->diffsToSave[$dbKey['hash']]) || isset($relDbTable->diffsToDelete[$dbKey['hash']])) {
                    $this->notFoundDbKeys[$dbKey['hash']] = $dbKey;
                    $anyNotFound = true;
                }
            }
        }
        if(!$anyNotFound) {
            list($cmd, $from, $to) = $diff;
            if($allInPlan) {
                $tableDefs = $this->getTableDefs();
                DbGit::$saveDbPlan[$to['hash'].$tableDefs['table']] = array(
                    'table' => $tableDefs['table'],
                    'diff' => $diff,
                    'record' => $record,
                );
            }
            else {
                $this->diffsToSave2[$to['hash']] = $diff;
            }
        }
    }

    public function checkAllSpecifiedToDelete()
    {
        if(!empty($this->diffsToDelete)) {
            throw new Exception("Cannot specify ".var_export($this->diffsToDelete, true));
        }
    }

    public function checkAllSpecifiedToSave()
    {
        if(!empty($this->diffsToSave)) {
            throw new Exception("Cannot specify ".var_export($this->diffsToSave, true));
        }
    }

    public function execCmd($diff, $record, $rowTo = null)
    {
        list($cmd, $from, $to) = $diff;
        $tableDefs = $this->getTableDefs();
        if($cmd === DbGit::$DELETE_CMD) {
            if(!$record->isLoaded()) {
                fwrite(STDERR, "Warning: record already deleted, key ".var_export($from['key'], true).PHP_EOL);
            }
            else {
                $record->delete();
            }
        }
        else {
            if(!empty($from)) {
                $normalKey = $from['key'];
                $hash = self::hashArray($normalKey);
                $key = $this->specifyRow($normalKey, array($hash => $normalKey));
                $record->retrieveByFields($key);
            }
            if($rowTo === null) {
                $rowTo = $this->specifyRow($to['data'], array($to['hash'] => $to['key']));
            }
            if(empty($from)) {
                $keyTo = $this->getRowKey($rowTo);
                $record->retrieveByFields($keyTo);
            }
            if($record->isLoaded() && $cmd === DbGit::$ADD_CMD) {
                fwrite(STDERR, "Warning: record already exists, key ".var_export($to['key'], true).PHP_EOL);
                return;
            }
            if(!$record->isLoaded() && $cmd === DbGit::$MODIFY_CMD) {
                fwrite(STDERR, "Warning: Trying to update non-existing record, key ".var_export($from['key'], true).PHP_EOL);
            }
            foreach($tableDefs['fields'] as $field) {
                $name = $field['name'];
                if(array_key_exists($name, $rowTo)) {
                    $record->setField($name, $rowTo[$name]);
                }
            }
            $record->save();
        }
    }

    public function newRecord()
    {
        $tableDefs = $this->getTableDefs();
        if(!empty($tableDefs['module'])) {
            if($tableDefs['module'] == 'relationship') {
                return new SugarRelDbGitTableRecord($tableDefs);
            }
            return new SugarBeanDbGitTableRecord($tableDefs);
        }
        return new DbGitTableRecord($tableDefs);
    }
}

class RelatedNotFoundException extends Exception
{
    public $notFoundDbKeys;

    public function __construct($notFoundDbKeys)
    {
        parent::__construct("Related Not Found: ".var_export($notFoundDbKeys, true));
        $this->notFoundDbKeys = $notFoundDbKeys;
    }
}
