<?php
$viewdefs['Administration']['base']['layout']['systemdata-import'] = array(
    'name' => 'main-pane',
    'css_class' => 'main-pane row-fluid',
    'type' => 'simple',
    'span' => 12,
    'components' => array(
        array(
            'view' => 'systemdata-import-header',
        ),
        array(
            'view' => 'systemdata-import',
        ),
    ),
);
