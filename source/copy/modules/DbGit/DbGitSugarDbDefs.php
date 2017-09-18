<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

class DbGitSugarDbDefs
{
    public static function getDbDefs()
    {
        $db_defs = array();
        $files = glob('custom/Extension/modules/DbGit/Ext/DbDefs/*.php');
        usort ($files, 'strnatcmp');
        foreach($files as $file) {
            require $file;
        }
        return $db_defs;
    }
}
