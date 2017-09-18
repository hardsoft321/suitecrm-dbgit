<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

require_once __DIR__.'/DbGit.php';
require_once __DIR__.'/DbGitTableDefs.php';
require_once __DIR__.'/DbGitTable.php';

class DbGitFile extends DbGitTableDefs
{
    protected static $DBDIR = 'dbdata';

    public function getTableDir()
    {
        return self::$DBDIR.'/'.$this->getTableName();
    }

    public function getTableData()
    {
        $tableDefs = $this->getTableDefs();
        $dbTable = DbGit::getTable($tableDefs['table']);
        $tableDir = $this->getTableDir();
        try {
            $files =
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tableDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY);
        }
        catch( UnexpectedValueException $ex) {
            fwrite(STDERR, "Directory {$tableDir} not exists".PHP_EOL);
            return array();
        }

        $table_data = array();
        $nonUniqueKeys = array();
        foreach ($files as $file) {
            $record = require $file;
            if(empty($record['key'])) {
                $record['key'] = $dbTable->getRowKey($record['data']);
            }
            if(empty($record['hash'])) {
                $record['hash'] = DbGitTable::hashArray($record['key']);
            }
            if(isset($table_data[$record['hash']])) {
                $nonUniqueKeys[] = $record['data'];
            }
            $table_data[$record['hash']] = $record;
        }
        if(!empty($nonUniqueKeys)) {
            throw new Exception("Some keys are not unique: ".print_r($nonUniqueKeys, true)." <- Non unique keys");
        }
        return $table_data;
    }

    public function save($plan)
    {
        foreach($plan as $diff) {
            list($cmd, $from, $to) = $diff;
            if($cmd === DbGit::$ADD_CMD || $cmd === DbGit::$MODIFY_CMD) {
                $filename = $this->hashToFileName($to['hash']);
                $dirname = dirname($filename);
                if(!file_exists($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                file_put_contents($filename, self::exportToPhp($to));
            }
            elseif($cmd === DbGit::$DELETE_CMD) {
                $filename = $this->hashToFileName($from['hash']);
                $dirname = dirname($filename);
                unlink($filename);
                $files = array_diff(scandir($dirname), array('.','..'));
                if(empty($files)) {
                    rmdir($dirname);
                }
            }
        }
    }

    public static function exportToPhp($var)
    {
        return "<?php
return ".var_export($var, true).";
";
    }

    public function hashToFileName($hash)
    {
        return $this->getTableDir().'/'.substr($hash, 0, 2).'/'.substr($hash, 2);
    }
}
