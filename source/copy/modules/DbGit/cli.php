<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */
require_once __DIR__.'/libcli.php';
require_once __DIR__.'/DbGit.php';
require_once __DIR__.'/DbGitFile.php';

if(PHP_SAPI !== 'cli') {
    echo "CLI only\n";
    sugar_die('');
}

$SUBJECT_COUNT = 1;
$ALLOWED_OPTIONS = array(
    'y',
    'n',
    'v',
    'tables:',
    'login:',
    'export',
    'ignore-duplicates',
);

try {
    list($keywords, $options) = getArgvParams($SUBJECT_COUNT, $ALLOWED_OPTIONS);
}
catch(Exception $ex) {
    fwrite(STDERR, $ex->getMessage().PHP_EOL);
    sugar_die('');
}

$command = reset($keywords);
if($command == 'db2file' || $command == 'file2db') {

    $silent = false;
    if(!empty($options['export'])) {
        $silent = true;
    }
    DbGit::$ignoreDuplicates = !empty($options['ignore-duplicates']);

    if($command == 'file2db') {
        global $current_user;
        $current_user = new User();
        if(!empty($options['login'])) {
            $current_user->retrieve_by_string_fields(array(
                'user_name' => $options['login'],
            ));
            if(empty($current_user->id)) {
                fwrite(STDERR, "User '{$options['login']}' not found.".PHP_EOL);
                sugar_die('');
            }
        }
        else {
            $current_user->getSystemUser();
        }
    }

    if(!$silent) {
        echo "Plan {$command}...", PHP_EOL;
        echo PHP_EOL;
    }

    $tables = !empty($options['tables']) ? explode(',', $options['tables']) : array();
    $dbPlan = $command == 'db2file' ? DbGit::getDbToFilePlan($tables) : DbGit::getFileToDbPlan($tables);

    if(!$silent) {
        printDbGitPlan($dbPlan, !empty($options['v']));
    }

    if(!empty($options['export'])) {
        $dbPlan = array_values(array_filter($dbPlan, function($tablePlan) {
            return !empty($tablePlan['diff']);
        }));
        //очищаем дублируемую информацию
        foreach($dbPlan as &$tablePlan) {
            foreach($tablePlan['diff'] as &$diff) {
                $iCMD = 0; $iFROM = 1; $iTO = 2;

                if(!empty($diff[$iFROM]) && !empty($diff[$iTO])) {
                    foreach($diff[$iFROM]['data'] as $key => $value) {
                        if($diff[$iFROM]['data'][$key] === $diff[$iTO]['data'][$key]) {
                            unset($diff[$iTO]['data'][$key]);
                        }
                    }
                }
                if(!empty($diff[$iFROM])) {
                    if(!empty($diff[$iFROM]['data'])) {
                        unset($diff[$iFROM]['data']);
                    }
                    unset($diff[$iFROM]['hash']);
                }
                if(!empty($diff[$iTO])) {
                    unset($diff[$iTO]['hash']);
                    unset($diff[$iTO]['key']);
                }
            }
            unset($tablePlan['condition']);
            unset($diff);
        }
        unset($tablePlan);
        echo DbGitFile::exportToPhp($dbPlan);
    }

    if(!DbGit::planIsEmpty($dbPlan) && empty($options['export'])) {
        try {
            $question = $command == 'db2file' ? "Write changes to disk?" : "Update database?";
            $executionConfirmed = cliConfirm($question, $options);
        }
        catch(Exception $ex) {
            sugar_cleanup();
            exit;
        }
        if($executionConfirmed) {
            if(!$silent) {
                echo "Execute {$command}...", PHP_EOL;
            }
            if($command == 'db2file') {
                DbGit::executeDbToFilePlan($dbPlan);
            }
            else {
                DbGit::executeFileToDbPlan($dbPlan);
            }
            if(!$silent) {
                echo "Done", PHP_EOL;
            }
        }
    }
}
else {
    fwrite(STDERR, "Unknown command $command".PHP_EOL);
    sugar_die('');
}
sugar_cleanup();
