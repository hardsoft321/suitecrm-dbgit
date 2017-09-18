<?php
$db_defs['currencies'] = array(
    'table' => 'currencies',
    'module' => 'Currencies',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'symbol' => array (
            'name' => 'symbol',
        ),
        'iso4217' => array (
            'name' => 'iso4217',
        ),
        'conversion_rate' => array (
            'name' => 'conversion_rate',
        ),
        'status' => array (
            'name' => 'status',
        ),
    ),
    'indices' => array(
        array('fields' => array('iso4217')),
    ),
);
