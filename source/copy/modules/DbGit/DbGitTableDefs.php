<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

class DbGitTableDefs
{
    private $tableDefs;

    public function __construct($tableDefs)
    {
        $this->tableDefs = $tableDefs;
        clean_string($this->tableDefs['table']);
    }

    public function getTableName()
    {
        return $this->tableDefs['table'];
    }

    public function getTableDefs()
    {
        return $this->tableDefs;
    }
}
