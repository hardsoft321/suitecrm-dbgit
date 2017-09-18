<?php
$db_defs['schedulers'] = array(
    'table' => 'schedulers',
    'module' => 'Schedulers',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'job' => array (
            'name' => 'job',
        ),
        'job_interval' => array (
            'name' => 'job_interval',
        ),
        'status' => array (
            'name' => 'status',
        ),
        'catch_up' => array (
            'name' => 'catch_up',
        ),
    ),
    'indices' => array(
        array('fields' => array('job')),
    ),
);
