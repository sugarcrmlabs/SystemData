<?php
$admin_option_defs = array();
$admin_option_defs['Administration']['systemdata-export'] = array(
    'Administration',
    'LBL_SYSTEMDATA_EXPORT_TITLE',
    'LBL_SYSTEMDATA_EXPORT_DESCRIPTION',
    'javascript:parent.SUGAR.App.router.navigate("SystemData/export", {trigger: true});',
);
$admin_option_defs['Administration']['systemdata-import'] = array(
    'Administration',
    'LBL_SYSTEMDATA_IMPORT_TITLE',
    'LBL_SYSTEMDATA_IMPORT_DESCRIPTION',
    'javascript:parent.SUGAR.App.router.navigate("SystemData/import", {trigger: true});',
);

$admin_group_header[] = array(
    'LBL_SYSTEMDATA_HEADER',
    '',
    false,
    $admin_option_defs, 
    'LBL_SYSTEMDATA_DESCRIPTION'
);
