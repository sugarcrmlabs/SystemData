<?php
$viewdefs['Administration']['base']['layout']['systemdata-export'] = array(
    'name' => 'main-pane',
    'css_class' => 'main-pane row-fluid',
    'type' => 'simple',
    'span' => 12,
    'components' => array(
        array(
            'view' => 'systemdata-export-header',
        ),
        array(
            'view' => 'systemdata-export',
        ),
    ),
);
