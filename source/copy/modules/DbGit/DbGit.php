<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

require_once __DIR__.'/DbGitTable.php';
require_once __DIR__.'/DbGitFile.php';

class DbGit
{
    public static $ADD_CMD = 'add';
    public static $DELETE_CMD = 'delete';
    public static $MODIFY_CMD = 'modify';

    private static $dbDefs;
    private static $dbTables;
    public static $saveDbPlan = array();
    public static $deleteDbPlan = array();
    public static $ignoreDuplicates = false;

    public static function getDbToFilePlan($tables = array())
    {
        return self::getPlan('db2file', $tables);
    }

    public static function getFileToDbPlan($tables = array())
    {
        return self::getPlan('file2db', $tables);
    }

    protected static function getPlan($cmd, $tables)
    {
        $dbDefs = self::getDbDefs();
        $dbPlan = array();
        $isDb2File = $cmd === 'db2file';
        foreach($dbDefs as $tableDefs) {
            $table = $tableDefs['table'];
            $condition = isset($tableDefs['condition']) ? $tableDefs['condition'] : false;
            if(!empty($tables) && !self::matchTables($tables, $table)) {
                $dbPlan[] = array(
                    'table' => $table,
                    'diff' => array(),
                    'error' => '-',
                    'condition' => $condition,
                );
                continue;
            }
            $tableDiff = array();
            $dbTable = self::getTable($tableDefs['table']);
            $dbFile = new DbGitFile($tableDefs);
            try {
                $tableData = $dbTable->getTableData(self::$ignoreDuplicates);
                $fileData  = $dbFile->getTableData();

                $addDiff = $isDb2File ? array_diff_key($tableData, $fileData) : array_diff_key($fileData, $tableData);
                foreach($addDiff as $value) {
                    $tableDiff[] = array(
                        self::$ADD_CMD,
                        '',
                        $value,
                    );
                }

                $deleteDiff = $isDb2File ? array_diff_key($fileData, $tableData) : array_diff_key($tableData, $fileData);
                foreach($deleteDiff as $value) {
                    $tableDiff[] = array(
                        self::$DELETE_CMD,
                        $value,
                        '',
                    );
                }

                $modifyDiff = $isDb2File
                    ? self::array_diff_assoc_recursive(array_intersect_key($tableData, $fileData), array_intersect_key($fileData, $tableData))
                    : self::array_diff_assoc_recursive(array_intersect_key($fileData, $tableData), array_intersect_key($tableData, $fileData));
                foreach($modifyDiff as $key => $value) {
                    $tableDiff[] = array(
                        self::$MODIFY_CMD,
                        $isDb2File ? $fileData[$key] : $tableData[$key],
                        $isDb2File ? $tableData[$key] : $fileData[$key],
                    );
                }

                $dbPlan[] = array(
                    'table' => $dbTable->getTableName(),
                    'diff' => $tableDiff,
                    'condition' => $condition,
                );
            }
            catch(Exception $ex) {
                fwrite(STDERR, "ERROR: DbGit - ".$ex->getMessage().PHP_EOL.PHP_EOL);
                $dbPlan[] = array(
                    'table' => $table,
                    'diff' => array(),
                    'error' => 'ERROR',
                    'condition' => $condition,
                );
            }
        }
        return $dbPlan;
    }

    protected static function matchTables($tables, $table)
    {
        foreach($tables as $mask) {
            if(fnmatch($mask, $table)) {
                return true;
            }
        }
        return false;
    }

    public static function executeDbToFilePlan($dbPlan)
    {
        foreach ($dbPlan as $tablePlan) {
            $dbFile = new DbGitFile(self::getTableDefs($tablePlan['table']));
            $dbFile->save($tablePlan['diff']);
        }
    }

    public static function executeFileToDbPlan($dbPlan)
    {
        if(!empty(self::$saveDbPlan) || !empty(self::$deleteDbPlan)) {
            throw new Exception("Execution already in progress");
        }
        $tables = array();
        foreach ($dbPlan as $tablePlan) {
            $dbTable = self::getTable($tablePlan['table']);
            $tables[] = $dbTable;
            $dbTable->scanPlan($tablePlan['diff']);
        }

        self::specifyFileToDbPlan_Delete($tables);

        foreach($tables as $dbTable) {
            $dbTable->checkAllSpecifiedToDelete();
        }

        self::specifyFileToDbPlan_Save($tables);

        foreach($tables as $dbTable) {
            $dbTable->checkAllSpecifiedToSave();
        }

        echo "Start saving", PHP_EOL;
        foreach(self::$deleteDbPlan as $dbPlanRecord) {
            $dbTable = self::getTable($dbPlanRecord['table']);
            $dbTable->execCmd($dbPlanRecord['diff'], $dbPlanRecord['record']);
        }
        foreach(self::$saveDbPlan as $dbPlanRecord) {
            $dbTable = self::getTable($dbPlanRecord['table']);
            $dbTable->execCmd($dbPlanRecord['diff'], $dbPlanRecord['record'], !empty($dbPlanRecord['rowTo']) ? $dbPlanRecord['rowTo'] : null);
        }

        self::clearPlan();
    }

    public static function clearPlan()
    {
        self::$deleteDbPlan = array();
        self::$saveDbPlan = array();
        $dbDefs = self::getDbDefs();
        foreach($dbDefs as $tableDefs) {
            $dbTable = self::getTable($tableDefs['table']);
            $dbTable->clearPlan();
        }
    }

    protected static function specifyFileToDbPlan_Delete($tables)
    {
        $count = 0;
        foreach($tables as $dbTable) {
            $count += $dbTable->specifyDeletePlan();
        }
        if($count !== 0) {
            self::specifyFileToDbPlan_Delete($tables);
        }
    }

    protected static function specifyFileToDbPlan_Save($tables)
    {
        $count = 0;
        foreach($tables as $dbTable) {
            $count += $dbTable->specifySavePlan();
        }
        if($count !== 0) {
            self::specifyFileToDbPlan_Save($tables);
        }
    }

    public static function planIsEmpty($dbPlan)
    {
        foreach($dbPlan as $tablePlan) {
            if(!empty($tablePlan['diff'])) {
                return false;
            }
        }
        return true;
    }

    protected static function getDbDefs()
    {
        if(!isset(self::$dbDefs)) {
            require_once __DIR__.'/DbGitSugarDbDefs.php';
            self::$dbDefs = DbGitSugarDbDefs::getDbDefs();
        }
        return self::$dbDefs;
    }

    public static function getTable($table)
    {
        if(!isset(self::$dbTables[$table])) {
            $tableDefs = self::getTableDefs($table);
            self::$dbTables[$table] = new DbGitTable($tableDefs);
        }
        return self::$dbTables[$table];
    }

    protected static function getTableDefs($table)
    {
        foreach(self::getDbDefs() as $tableDefs) {
            if($tableDefs['table'] == $table) {
                return $tableDefs;
            }
        }
        throw new Exception("TableDefs not found for table '{$table}'");
    }

    public static function getTableNameForModule($module)
    {
        $module = trim($module);
        foreach(self::getDbDefs() as $tableDefs) {
            if(!empty($tableDefs['module']) && ($tableDefs['module'] == $module)) {
                return $tableDefs['table'];
            }
        }
        throw new Exception("Table name not found for module '{$module}'");
    }

    /**
     * http://php.net/manual/ru/function.array-diff-assoc.php#111675
     */
    protected static function array_diff_assoc_recursive($array1, $array2) {
        $difference=array();
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::array_diff_assoc_recursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }
}
