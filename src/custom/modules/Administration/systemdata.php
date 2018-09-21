<?php

// Enrico Simonetti
// enricosimonetti.com
// 2018-09-21

die();
require_once 'include/SugarSmarty/plugins/function.sugar_csrf_form_token.php';

$exportSections = [
    'teams' => 'LBL_SYSTEMDATA_TEAMS',
    'roles' => 'LBL_SYSTEMDATA_ROLES',
    'users' => 'LBL_SYSTEMDATA_USERS',
    'awf' => 'LBL_SYSTEMDATA_AWF',
    'reports' => 'LBL_SYSTEMDATA_REPORTS',
];

$importSections = [
    'rolesmembership' => 'LBL_SYSTEMDATA_ROLESMEMBERSHIP',
    'teamsmembership' => 'LBL_SYSTEMDATA_TEAMSMEMBERSHIP',
];

$importSections = array_merge($exportSections, $importSections);

global $mod_strings;

// processing
if (!empty($_POST['systemdata'])) {
    switch ($_POST['systemdata']) {
        case 'export':
            foreach ($_POST['export'] as $section) {
                echo $section . '<br/>';
            }
            break;
        case 'import':
            foreach ($_POST['import'] as $section) {
                echo $section . '<br/>';
            }
            break;
    }
}

$csrfToken = smarty_function_sugar_csrf_form_token(array(), $smarty);

?>
    <form method='post' action='index.php?module=Administration&action=systemdata'>
    <input type='hidden' name='systemdata' value ='export' />
<?php
echo $csrfToken;

foreach ($exportSections as $section => $label) {
?>
    <input type='checkbox' name='export[]' value='<?php echo $section; ?>' /> <?php echo $mod_strings[$label];?><br/>
<?php
}
?>
    <input type='submit' value='<?php echo $mod_strings['LBL_SYSTEMDATA_EXPORT'];?>' />
    </form>
<?php

?>
    <form method='post' action='index.php?module=Administration&action=systemdata' enctype='multipart/form-data'>
    <input type='hidden' name='systemdata' value ='import' />
<?php
echo $csrfToken;

foreach ($importSections as $section => $label) {
?>
    <input type='checkbox' name='import[]' value='<?php echo $section; ?>' /> <?php echo $mod_strings[$label];?><br/>
<?php
}
?>
    <input type="file" name="importFile" id="importFile">
    <input type='submit' value='<?php echo $mod_strings['LBL_SYSTEMDATA_IMPORT'];?>' />
    </form>
<?php
