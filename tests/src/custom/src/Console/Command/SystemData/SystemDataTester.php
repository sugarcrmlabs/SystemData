<?php

// Enrico Simonetti
// 2017-08-03

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemData;

class SystemDataTester extends SystemData {

    private $tables_to_truncate = array(
        'users',
        'user_preferences',
        'team_sets_modules',
        'saved_reports',
        'report_cache',
        'email_addresses',
        'email_addr_bean_rel',
        'dashboards',
        'acl_fields',
        'acl_role_sets',
        'acl_role_sets_acl_roles',
        'acl_roles_actions',
        'acl_roles',
        'acl_roles_users',
        'pmse_business_rules',
        'pmse_bpmn_data',
        'pmse_bpmn_flow',
        'pmse_bpm_activity_definition',
        'pmse_bpmn_extension',
        'pmse_bpm_event_definition',
        'pmse_bpmn_diagram',
        'pmse_bpmn_bound',
        'pmse_bpmn_documentation',
        'pmse_project',
        'pmse_bpm_process_definition',
        'pmse_bpmn_lane',
        'pmse_bpmn_process',
        'pmse_bpm_dynamic_forms',
        'pmse_bpm_gateway_definition',
        'pmse_bpmn_laneset',
        'pmse_bpm_related_dependency',
        'pmse_bpmn_event',
        'pmse_bpmn_participant',
        'pmse_bpmn_activity',
        'pmse_bpmn_gateway',
        'pmse_bpmn_artifact',
        'pmse_emails_templates',
    );

    private $src_directory = './cache/files/';
    private $dst_directory = './cache/files/export/';

    public function truncateAllTables()
    {
        $db = \DBManagerFactory::getInstance();
        foreach($this->tables_to_truncate as $table) {
            $db->query($db->truncateTableSQL($db->quote($table)));
        }
        // need to delete all teams and team membership that are not global
        $this->deleteNonGlobalTeams();
        // clear cache
        \TeamSetManager::flushBackendCache();
    }

    public function deleteNonGlobalTeams()
    {
        $db = \DBManagerFactory::getInstance();

        $builder = $db->getConnection()->createQueryBuilder();
        $builder->delete('team_memberships');
        $builder->execute();

        $builder = $db->getConnection()->createQueryBuilder();
        $builder->delete('teams');
        $builder->where("id != '1'");
        $builder->execute();
    }

    public function executeInitialStep($input, $output)
    {
        $command = new SystemDataUsersImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step0/users.json')), $output);
    }

    public function executeStep($step, $input, $output)
    {
        $command = new SystemDataTeamsImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/teams.json')), $output);
        $command = new SystemDataRolesImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/roles.json')), $output);
        $command = new SystemDataUsersImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/users.json')), $output);
        $command = new SystemDataTeamsMembershipImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/users.json')), $output);
        $command = new SystemDataRolesMembershipImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/users.json')), $output);
        $command = new SystemDataReportsImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/reports.json')), $output);
        $command = new SystemDataAWFImport();
        $res = $command->run(new ArrayInput(array('path' => $this->src_directory.'step'.(int)$step.'/awf.json')), $output);
    }

    public function verifyStep($step, $input, $output)
    {
        $command = new SystemDataTeamsExport();
        $res = $command->run(new ArrayInput(array('path' => $this->dst_directory.'step'.(int)$step.'/')), $output);
        $command = new SystemDataRolesExport();
        $res = $command->run(new ArrayInput(array('path' => $this->dst_directory.'step'.(int)$step.'/')), $output);
        $command = new SystemDataUsersExport();
        $res = $command->run(new ArrayInput(array('path' => $this->dst_directory.'step'.(int)$step.'/')), $output);
        $command = new SystemDataReportsExport();
        $res = $command->run(new ArrayInput(array('path' => $this->dst_directory.'step'.(int)$step.'/')), $output);
        $command = new SystemDataAWFExport();
        $res = $command->run(new ArrayInput(array('path' => $this->dst_directory.'step'.(int)$step.'/')), $output);

        return !$this->isDifferent($step);
    }

    public function isDifferent($step)
    {
        $to_verify = array(
            'teams.json',
            'roles.json',
            'users.json',
            'reports.json',
            'awf.json',
        );

        $return = false;

        foreach($to_verify as $file) {
            $src = $this->getData($this->src_directory.'step'.(int)$step.'/'.$file);
            $export = $this->getData($this->dst_directory.'step'.(int)$step.'/'.$file);

            if($file == 'users.json') {
                // remove some stuff that would make the records look different even if they are not
                $src = $this->removeUnwatendUsersElements($src);
                $export = $this->removeUnwatendUsersElements($export);
            }

            $diff1 = deepArrayDiff($src, $export, false);
            $diff2 = deepArrayDiff($export, $src, false);
            if(!empty($diff1) || !empty($diff2)) {
                echo PHP_EOL . 'There was a problem with the following files: ' .
                    $this->src_directory.'step'.(int)$step.'/'.$file . ' and: ' .
                    $this->dst_directory.'step'.(int)$step.'/'.$file . PHP_EOL;
                
                print_r($diff1);
                print_r($diff2);

                $return = true;
            }
        }

        return $return;
    }

    public function removeUnwatendUsersElements($data)
    {
        foreach($data['users'] as $userkey => $user) {
            if(!empty($user['fields'])) {

                // unset fields that might be different and expected to be
                unset($data['users'][$userkey]['fields']['pwd_last_changed']);
                unset($data['users'][$userkey]['fields']['date_entered']);
                unset($data['users'][$userkey]['fields']['date_modified']);
                unset($data['users'][$userkey]['fields']['last_login']);
                unset($data['users'][$userkey]['fields']['modified_by_name']);
                unset($data['users'][$userkey]['fields']['email1']);

                // the role set will be different every time, but we keep in line the 'roles' a user is part of
                unset($data['users'][$userkey]['fields']['acl_role_set_id']);

                // unset all empty fields as they might look like a difference
                foreach($user['fields'] as $fieldname => $fieldvalue) {
                    // mostly for team data contents that are empty
                    if(is_array($fieldvalue)) {
                        foreach($fieldvalue as $teamfieldkey => $teamfieldvalue) {
                            if(empty($teamfieldvalue)) {
                                unset($data['users'][$userkey]['fields'][$fieldname][$teamfieldkey]);
                            }
                        } 
                    }

                    // remove empty fields
                    if(empty($data['users'][$userkey]['fields'][$fieldname])) {
                        unset($data['users'][$userkey]['fields'][$fieldname]);
                    }
                }

                // unset fields from the email addresses that will definitely be different
                if(!empty($user['fields']['email'])) {
                    foreach($user['fields']['email'] as $emailkey => $email) {
                        unset($data['users'][$userkey]['fields']['email'][$emailkey]['id']);
                        unset($data['users'][$userkey]['fields']['email'][$emailkey]['email_address_id']);
                        unset($data['users'][$userkey]['fields']['email'][$emailkey]['date_modified']);
                        unset($data['users'][$userkey]['fields']['email'][$emailkey]['date_created']);
                    }
                }
            }

            if(!empty($user['preferences'])) {
                foreach($user['preferences'] as $prefkey => $preference) {
                    if($preference['category'] == 'global') {
                        // ignore global as they might differ due to different calendar keys
                        unset($data['users'][$userkey]['preferences'][$prefkey]);
                    }
                }
            }
        }
        
        return $data;
    }
}
