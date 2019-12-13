<?php
$manifest['id'] = 'system_data_cli_tests';
$manifest['built_in_version'] = '7.9.1.0';
$manifest['name'] = 'System Data CLI Testing Export Import tool';
$manifest['description'] = 'System Data CLI Testing Export Import tool';
$manifest['author'] = 'Enrico Simonetti, SugarCRM Inc.';
$manifest['acceptable_sugar_versions']['regex_matches'] = array('^8.[\d]+.[\d]+$', '^9.[\d]+.[\d]+$');
$manifest['dependencies'] = array(
    array(
        'id_name' => 'system_data',
        'version' => '1.0'
    ),
);
