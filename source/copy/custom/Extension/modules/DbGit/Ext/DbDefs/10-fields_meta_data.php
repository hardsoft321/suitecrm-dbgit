<?php
$db_defs['fields_meta_data'] = array(
    'table' => 'fields_meta_data',
    'module' => 'relationship',
    'fields' => array(
        'name' => array(
            'name' =>'name',
        ),
        'vname' => array(
            'name' =>'vname',
        ),
        'comments' => array(
            'name' => 'comments',
        ),
        'help' => array(
            'name' =>'help',
        ),
        'custom_module' => array(
            'name' =>'custom_module',
         ),
        'type' => array(
            'name' =>'type',
        ),
        'len' => array(
            'name' => 'len',
        ),
        'required' => array(
            'name' =>'required',
        ),
        'default_value' => array(
            'name' => 'default_value',
        ),
        'audited' => array(
            'name' => 'audited',
        ),
        'massupdate' => array(
            'name' =>'massupdate',
        ),
        'duplicate_merge' => array(
            'name' => 'duplicate_merge',
        ),
        'reportable' => array(
            'name'=>'reportable',
        ),
        'importable' => array(
            'name'=>'importable',
        ),
        'ext1' => array(
            'name' =>'ext1',
        ),
        'ext2' => array(
            'name' =>'ext2',
        ),
        'ext3' => array(
            'name' =>'ext3',
        ),
        'ext4' => array(
            'name' =>'ext4',
        ),
    ),
    'indices' => array(
        array('fields' => array('custom_module', 'name')),
    ),
);
