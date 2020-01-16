<?php
$db_defs['config'] = array(
    'table' => 'config',
    'module' => '',
    'fields' => array(
        'category' => array(
            'name' => 'category',
        ),
        'name' => array(
            'name' => 'name',
        ),
        'value' => array(
            'name' => 'value',
        ),
    ),
    'indices' => array(
        array('fields' => array('category', 'name')),
    ),
    'condition' => "(
           category = 'info' AND name = 'sugar_version'
        OR category = 'MySettings' AND name = 'tab'
        OR category = 'MySettings' AND name = 'disable_useredit'
        OR category = 'MySettings' AND name = 'hide_subpanels'
    )",
);
