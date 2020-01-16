<?php
$manifest = array(
    'name' => 'dbgit',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Синхронизация данных БД с файлами',
    'is_uninstallable' => true,
    'published_date' => '2017-09-11',
    'type' => 'module',
    'version' => '1.2.0',
);
$installdefs = array(
    'id' => 'dbgit',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
