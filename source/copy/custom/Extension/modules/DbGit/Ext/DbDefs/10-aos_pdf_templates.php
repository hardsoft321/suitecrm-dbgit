<?php
$db_defs['aos_pdf_templates'] = array(
    'table' => 'aos_pdf_templates',
    'module' => 'AOS_PDF_Templates',
    'fields' => array(
        'name' => array(
            'name' => 'name',
        ),
        'active' => array(
            'name' => 'active',
        ),
        'type' => array(
            'name' => 'type',
        ),
        'description' => array(
            'name' => 'description',
        ),
        'pdfheader' => array(
            'name' => 'pdfheader',
        ),
        'pdffooter' => array(
            'name' => 'pdffooter',
        ),
        'margin_left' => array(
            'name' => 'margin_left',
        ),
        'margin_right' => array(
            'name' => 'margin_right',
        ),
        'margin_top' => array(
            'name' => 'margin_top',
        ),
        'margin_bottom' => array(
            'name' => 'margin_bottom',
        ),
        'margin_header' => array(
            'name' => 'margin_header',
        ),
        'margin_footer' => array(
            'name' => 'margin_footer',
        ),
        'page_size' => array(
            'name' => 'page_size',
        ),
        'orientation' => array(
            'name' => 'orientation',
        ),
    ),
    'indices' => array(
        array('fields' => array('name')),
    ),
);
