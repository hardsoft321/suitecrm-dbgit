<?php
$db_defs['versions'] = array(
    'table' => 'versions',
    'module' => 'Versions',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'file_version' => array (
            'name' => 'file_version',
        ),
        'db_version' => array (
            'name' => 'db_version',
        ),
    ),
    'indices' => array(
        array('fields' => array('name')),
    ),
);
