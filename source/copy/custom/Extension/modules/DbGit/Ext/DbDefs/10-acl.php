<?php
$db_defs['acl_roles'] = array(
    'table' => 'acl_roles',
    'module' => 'ACLRoles',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'description' => array (
            'name' => 'description',
        ),
    ),
    'indices' => array(
        array('fields' => array('name')),
    ),
);

$db_defs['acl_actions'] = array(
    'table' => 'acl_actions',
    'module' => 'ACLActions',
    'fields' => array(
        'name' => array (
            'name' => 'name',
        ),
        'category' => array (
            'name' => 'category',
        ),
        'acltype' => array (
            'name' => 'acltype',
        ),
        'aclaccess' => array (
            'name' => 'aclaccess',
        ),
    ),
    'indices' => array(
        array('fields' => array('name', 'category', 'acltype')),
    ),
);

$db_defs['acl_roles_actions'] = array(
    'table' => 'acl_roles_actions',
    'module' => 'relationship',
    'fields' => array(
        'role_id' => array (
            'name' => 'role_id',
            'type' => 'id',
            'table' => 'acl_roles',
            'required' => true,
        ),
        'action_id' => array (
            'name' => 'action_id',
            'type' => 'id',
            'table' => 'acl_actions',
            'required' => true,
        ),
        'access_override' => array (
            'name' => 'access_override',
        ),
    ),
    'indices' => array(
        array('fields' => array('role_id', 'action_id')),
    ),
);
