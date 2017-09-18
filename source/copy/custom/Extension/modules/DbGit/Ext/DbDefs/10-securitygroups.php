<?php
$db_defs['securitygroups'] = array(
    'table' => 'securitygroups',
    'module' => 'SecurityGroups',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'description' => array (
            'name' => 'description',
        ),
        'noninheritable' => array (
            'name' => 'noninheritable',
        ),
    ),
    'indices' => array(
        array('fields' => array('name')),
    ),
);

$db_defs['securitygroups_default'] = array(
    'table' => 'securitygroups_default',
    'module' => 'relationship',
    'fields' => array(
        'securitygroup_id' => array (
            'name' => 'securitygroup_id',
            'type' => 'id',
            'table' => 'securitygroups',
            'required' => true,
        ),
        'module' => array (
            'name' => 'module',
        ),
    ),
    'indices' => array(
        array('fields' => array('securitygroup_id', 'module')),
    ),
);

$db_defs['securitygroups_acl_roles'] = array(
    'table' => 'securitygroups_acl_roles',
    'module' => 'relationship',
    'fields' => array(
        'securitygroup_id' => array (
            'name' => 'securitygroup_id',
            'type' => 'id',
            'table' => 'securitygroups',
            'required' => true,
        ),
        'role_id' => array (
            'name' => 'role_id',
            'type' => 'id',
            'table' => 'acl_roles',
            'required' => true,
        ),
    ),
    'indices' => array(
        array('fields' => array('securitygroup_id', 'role_id')),
    ),
);
