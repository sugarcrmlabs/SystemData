<?php

// Enrico Simonetti
// enricosimonetti.com
// 2018-09-21

use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataAWF;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataReports;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataRoles;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataTeams;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataUsers;

require_once 'include/SugarSmarty/plugins/function.sugar_csrf_form_token.php';
require_once 'include/upload_file.php';

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

function getFromCorrectObject($name) {
    switch ($name) {
        case 'teams':
            $sd = new SystemDataTeams();
            return array('teams' => $sd->getTeams());
            break;
        case 'roles':
            $sd = new SystemDataRoles();
            return array('roles' => $sd->getRoles());
            break;
        case 'users':
            $sd = new SystemDataUsers();
            return array('users' => $sd->getUsers());
            break;
        case 'awf':
            $sd = new SystemDataAWF();
            return array('awf' => $sd->getAWF());
            break;
        case 'reports':
            $sd = new SystemDataReports();
            return array('reports' => $sd->getReports());
            break;
    }
}

function saveOnCorrectObject($name, $data) {
    switch ($name) {
        case 'teams':
            $sd = new SystemDataTeams();
            $res = $sd->saveTeamsArray($data['teams']);
            return 'Teams imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created<br/>';
            break;
        case 'roles':
            $sd = new SystemDataRoles();
            $res = $sd->saveRolesArray($data['roles']);
            return 'Roles imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created<br/>';
            break;
        case 'users':
            $sd = new SystemDataUsers();
            $res = $sd->saveUsersArray($data['users']);
            return 'Users imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created<br/>';
            break;
        case 'awf':
            $sd = new SystemDataAWF();
            $res = $sd->saveAWFArrays($data['awf']);
            $output = [];
            if(!empty($res['errors'])) {
                foreach($res['errors'] as $message) {
                    $output[] = $message;
                }
            }
            $output[] = 'Reports imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created<br/>';
            return implode('<br/>', $output);
            break;
        case 'reports':
            $sd = new SystemDataReports();
            $res = $sd->saveReportsArray($data['reports']);
            $output = [];
            if(!empty($res['errors'])) {
                foreach($res['errors'] as $message) {
                    $output[] = $message;
                }
            }
            $output[] = 'Reports imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created<br/>';
            return implode('<br/>', $output);
            break;
        case 'rolesmembership':
            $sd = new SystemDataUsers();
            $res = $sd->saveRolesMembership($data['users']);
            return 'Roles Membership imported! '.count($res['processed']).' Role(s) processed, '.(count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])).' Membership(s) processed. '.count($res['skipped']).' Roles(s) skipped, '.(count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped'])).' Membership(s) skipped<br/>';
            break;
        case 'teamsmembership':
            $sd = new SystemDataUsers();
            $res = $sd->saveTeamsMembership($data['users']);
            return 'Teams Membership imported! '.count($res['processed']).' Team(s) processed, '.(count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])).' Membership(s) processed. '.count($res['skipped']).' Team(s) skipped, '.(count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped'])).' Membership(s) skipped<br/>';
            break;
    }
}


// processing
if (!empty($_POST['systemdata'])) {
    switch ($_POST['systemdata']) {
        case 'export':
            $output = [];
            foreach ($_POST['export'] as $section) {
                if (in_array($section, array_keys($exportSections))) {
                    $current = getFromCorrectObject($section);
                    $output = array_merge_recursive($output, $current);
                }
            }
            $to_download = json_encode($output, JSON_PRETTY_PRINT);
            
            ini_set('zlib.output_compression','Off');
            header("Pragma: public");
            header("Cache-Control: max-age=1, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=\"export.json\"");
            header("X-Content-Type-Options: nosniff");
            header("Content-Length: " . strlen($to_download));
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
            set_time_limit(0);

            @ob_end_clean();
            ob_start();
            echo $to_download;
            @ob_flush();

            break;
        case 'import':

            $file = $_FILES['importFile'];

            // check mime
            $finfo = new finfo(FILEINFO_MIME);
            $mime = $finfo->file($file['tmp_name']);

            // check passed filename extension
            $extension = end(explode('.', $file['name']));
            if ($extension == 'json' && $mime == 'text/plain; charset=us-ascii') {
   
                // get temp file content 
                $uploadFile = new UploadFile();
                $uploadFile->temp_file_location = $file['tmp_name'];
                $file_contents = $uploadFile->get_file_contents();
                $content = json_decode($file_contents, true);
                $errorfree = (json_last_error() == JSON_ERROR_NONE);
 
                if ($errorfree) {
                    foreach ($_POST['import'] as $section) {
                        if (in_array($section, array_keys($importSections))) {
                            echo saveOnCorrectObject($section, $content);
                        }
                    }
                }
            } else {
                echo 'Error uploading file';
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
    <input type='checkbox' checked='checked' name='export[]' value='<?php echo $section; ?>' /> <?php echo $mod_strings[$label];?><br/>
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
    <input type='checkbox' checked='checked' name='import[]' value='<?php echo $section; ?>' /> <?php echo $mod_strings[$label];?><br/>
<?php
}
?>
    <input type="file" name="importFile" id="importFile">
    <input type='submit' value='<?php echo $mod_strings['LBL_SYSTEMDATA_IMPORT'];?>' />
    </form>
<?php
