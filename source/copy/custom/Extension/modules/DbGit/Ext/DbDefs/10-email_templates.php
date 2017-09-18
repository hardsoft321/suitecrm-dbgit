<?php
$db_defs['email_templates'] = array(
    'table' => 'email_templates',
    'module' => 'EmailTemplates',
    'fields' => array(
        'published' => array(
            'name' => 'published',
        ),
        'name' => array(
            'name' => 'name',
        ),
        'description' => array(
            'name' => 'description',
        ),
        'subject' => array(
            'name' => 'subject',
        ),
        'body' => array(
            'name' => 'body',
        ),
        'body_html' => array(
            'name' => 'body_html',
        ),
        'text_only' => array(
            'name' => 'text_only',
        ),
        'type' => array(
            'name' => 'type',
        ),
    ),
    'indices' => array(
        array('fields' => array('name')),
    ),
    'condition' => "deleted = 0 AND name IN ('System-generated password email', 'Forgot Password email')",
);
