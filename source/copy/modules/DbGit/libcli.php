<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

function getArgvParams($subjectCount, $allowedOptions)
{
    global $argv;
    $options = array();
    $subjects = array();
    for($i = 1; $i < count($argv); $i++) {
        if($argv[$i][0] == '-') {
            $optPair = explode("=", ltrim($argv[$i], '-'));
            $name = $optPair[0];
            if(in_array($name.':', $allowedOptions)) {
                if(count($optPair) < 2) {
                    throw new Exception("Value must be specified for option {$argv[$i]}");
                }
                $options[$name] = $optPair[1];
            }
            elseif(in_array($name, $allowedOptions)) {
                $options[$name] = true;
            }
            else {
                throw new Exception("Unknown option {$argv[$i]}");
            }
        }
        else {
            if($subjectCount !== false && count($subjects) >= $subjectCount) {
                throw new Exception("Unknown option {$argv[$i]}");
            }
            $subjects[] = $argv[$i];
        }
    }
    if($subjectCount !== false && count($subjects) < $subjectCount) {
        throw new Exception("Some params are missing.");
    }
    return array($subjects, $options);
}

function rowToString($row)
{
    $str = var_export($row, true);
    $str = preg_replace('/^\s+/m', '', $str);
    $str = preg_replace('/,$/m', ', ', $str);
    $str = str_replace("\r", '', $str);
    $str = str_replace("\n", '', $str);
    return $str;
}

function printDbGitPlan($dbPlan, $showData = true)
{
    $total = array();
    $skipped = array();
    $tables = array();
    foreach($dbPlan as $tablePlan) {
        $table = $tablePlan['table'];
        $condition = $showData && $tablePlan['condition'] !== false ? " ({$tablePlan['condition']})": '';
        $tables[$table]['condition'] = $condition;
        if(isset($tablePlan['error'])) {
            $skipped[$table] = $tablePlan['error'];
            continue;
        }
        echo "Table {$table}{$condition}:", PHP_EOL, PHP_EOL;
        $total[$table] = array(
            DbGit::$ADD_CMD => 0,
            DbGit::$DELETE_CMD => 0,
            DbGit::$MODIFY_CMD => 0,
            );
        foreach($tablePlan['diff'] as $diff) {
            list($cmd, $from, $to) = $diff;
            $total[$table][$cmd]++;
            if($showData) {
                echo "  {$cmd}: ";
                if(!empty($from)) {
                    echo rowToString($from['data']);
                }
                if(!empty($from) && !empty($to)) {
                    echo "\n",str_repeat(" ", strlen($cmd))," -> ";
                }
                if(!empty($to)) {
                    echo rowToString($to['data']);
                }
                echo PHP_EOL;
            }
        }
        if(!empty($tablePlan['diff']) && $showData) {
            echo PHP_EOL;
        }
    }

    if(!empty($skipped)) {
        echo "Skipped tables:", PHP_EOL;
        foreach($skipped as $table => $error) {
            echo "  $table{$tables[$table]['condition']}", $error == '-' ? "" : ": \033[0;31m$error\033[0m", PHP_EOL;
        }
        echo PHP_EOL;
    }

    echo "Total:", PHP_EOL;
    foreach ($total as $table => $tableTotal) {
        $toRun = array_filter($tableTotal);
        echo "  {$table}{$tables[$table]['condition']}: "
            , (!empty($toRun)
                ? implode(', ', array_map(function($cmd) use ($toRun) {
                    return "$cmd - {$toRun[$cmd]}";
                }, array_keys($toRun)))
                : ' -')
            , PHP_EOL;
    }
    echo PHP_EOL;
}

function cliConfirm($question, $options)
{
    if(!empty($options['n'])) {
        return false;
    }

    if(!empty($options['y'])) {
        return true;
    }

    do {
        echo $question, " [y/N/q]:", PHP_EOL;
        $answer = trim(fgets(STDIN));
        $answered = false;
        if(strtolower($answer) === 'q') {
            throw new Exception("Quit requested by user");
        }
        if(strtolower($answer) === 'n' || $answer === '') {
            return false;
        }
        if(strtolower($answer) === 'y') {
            return true;
        }
        if(!$answered) {
            echo "Please repeat", PHP_EOL;
        }
    }
    while(!$answered);
}
