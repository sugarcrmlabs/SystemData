<?php

// Enrico Simonetti
// 2017-03-02

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemDataAWF extends SystemData
{
    private $modules_not_to_sync = array(
        'pmse_inbox' => 'pmse_Inbox', // pmse_Inbox
        'pmse_bpm_flow' => 'pmse_BpmFlow', // pmse_Project/pmse_BpmFlow
        'pmse_bpm_group_user' => 'pmse_BpmGroupUser', // pmse_Project/pmse_BpmGroupUser
        'pmse_bpm_activity_user' => 'pmse_BpmActivityUser', // pmse_Project/pmse_BpmActivityUser
        'pmse_bpm_config' => 'pmse_BpmConfig', // pmse_Project/pmse_BpmConfig
        'pmse_bpm_activity_step' => 'pmse_BpmActivityStep', // pmse_Project/pmse_BpmActivityStep
        'pmse_bpm_form_action' => 'pmse_BpmFormAction', // pmse_Project/pmse_BpmFormAction
        'pmse_bpm_thread' => 'pmse_BpmThread', // pmse_Project/pmse_BpmThread
        'pmse_bpm_group' => 'pmse_BpmGroup', // pmse_Project/pmse_BpmGroup
        'pmse_bpm_notes' => 'pmse_BpmNotes', // pmse_Project/pmse_BpmNotes
    );
    
    private $modules_to_sync = array(
        'pmse_business_rules' => 'pmse_Business_Rules', // pmse_Business_Rules
        'pmse_bpmn_data' => 'pmse_BpmnData', // pmse_Project/pmse_BpmnData
        'pmse_bpmn_flow' => 'pmse_BpmnFlow', // pmse_Project/pmse_BpmnFlow
        'pmse_bpm_activity_definition' => 'pmse_BpmActivityDefinition', // pmse_Project/pmse_BpmActivityDefinition
        'pmse_bpmn_extension' => 'pmse_BpmnExtension', // pmse_Project/pmse_BpmnExtension
        'pmse_bpm_event_definition' => 'pmse_BpmEventDefinition', // pmse_Project/pmse_BpmEventDefinition
        'pmse_bpmn_diagram' => 'pmse_BpmnDiagram', // pmse_Project/pmse_BpmnDiagram
        'pmse_bpmn_bound' => 'pmse_BpmnBound', // pmse_Project/pmse_BpmnBound
        'pmse_bpmn_documentation' => 'pmse_BpmnDocumentation', // pmse_Project/pmse_BpmnDocumentation
        'pmse_project' => 'pmse_Project', // pmse_Project
        'pmse_bpm_process_definition' => 'pmse_BpmProcessDefinition', // pmse_Project/pmse_BpmProcessDefinition
        'pmse_bpmn_lane' => 'pmse_BpmnLane', // pmse_Project/pmse_BpmnLane
        'pmse_bpmn_process' => 'pmse_BpmnProcess', // pmse_Project/pmse_BpmnProcess
        'pmse_bpm_dynamic_forms' => 'pmse_BpmDynaForm', // pmse_Project/pmse_BpmDynaForm
        'pmse_bpm_gateway_definition' => 'pmse_BpmGatewayDefinition', // pmse_Project/pmse_BpmGatewayDefinition
        'pmse_bpmn_laneset' => 'pmse_BpmnLaneset', // pmse_Project/pmse_BpmnLaneset
        'pmse_bpm_related_dependency' => 'pmse_BpmRelatedDependency', // pmse_Project/pmse_BpmRelatedDependency
        'pmse_bpmn_event' => 'pmse_BpmnEvent', // pmse_Project/pmse_BpmnEvent
        'pmse_bpmn_participant' => 'pmse_BpmnParticipant', // pmse_Project/pmse_BpmnParticipant
        'pmse_bpmn_activity' => 'pmse_BpmnActivity', // pmse_Project/pmse_BpmnActivity
        'pmse_bpmn_gateway' => 'pmse_BpmnGateway', // pmse_Project/pmse_BpmnGateway
        'pmse_bpmn_artifact' => 'pmse_BpmnArtifact', // pmse_Project/pmse_BpmnArtifact
        'pmse_emails_templates' => 'pmse_Emails_Templates', // pmse_Emails_Templates 
        'pmse_email_message' => 'pmse_EmailMessage', // pmse_Emails_Templates/pmse_EmailMessage
    );

    private $manual_tables = array(
        'pmse_business_rules',
        'pmse_project',
        'pmse_emails_templates',
        'pmse_bpmn_activity',
        'pmse_bpm_activity_definition',
    );

    // order in this table matters, we need to prune first activity and then project, otherwise the activities will be already empty
    private $pruning_rules = array(
        'pmse_bpmn_activity' => array(
            'source' => 'id',
            'destination' => 'id',
            'tables' => array(
                'pmse_bpm_activity_definition',
            ),
        ),
        'pmse_project' => array(
            'source' => 'id',
            'destination' => 'prj_id',
            'tables' => array(
                'pmse_bpmn_data',
                'pmse_bpmn_flow',
                'pmse_bpmn_extension',
                'pmse_bpm_event_definition',
                'pmse_bpmn_diagram',
                'pmse_bpmn_bound',
                'pmse_bpmn_documentation',
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
            ),
        ),
    );

    public function verifyKnownTableList()
    {
        $this->enforceAdmin();

        // do we know all the tables? or is there a discrepancy and should we stop?
        $current_tables = $this->getDBTables();
        foreach ($current_tables as $key => $table) {
            if (substr($table, 0, strlen('pmse_')) != 'pmse_') {
                // remove all tables that are not from awf    
                unset($current_tables[$key]);
            } else {
                // verify if we know all of them by removing all the known ones
                if (!empty($this->modules_not_to_sync[$table]) || !empty($this->modules_to_sync[$table])) {
                    unset($current_tables[$key]);
                }
            }
        }
        if (empty($current_tables)) {
            return true;
        } else {
            return false;
        }
    }

    public function getAWF()
    {
        $this->enforceAdmin();

        $list_records = array();

        // check if we know the exact table format
        if ($this->verifyKnownTableList()) {

            // 3 main tables
            $list_records['pmse_project'] = $this->getRowsFromTable('pmse_project', 'name');
            $list_records['pmse_emails_templates'] = $this->getRowsFromTable('pmse_emails_templates', 'name');
            $list_records['pmse_business_rules'] = $this->getRowsFromTable('pmse_business_rules', 'name');

            // special tables
            $list_records['pmse_bpmn_activity'] = $this->getRowsFromTable('pmse_bpmn_activity', 'name');
            $list_records['pmse_bpm_activity_definition'] = $this->getRowsFromTable('pmse_bpm_activity_definition', 'name');

            // remaining tables
            foreach ($this->modules_to_sync as $table => $module) {
                // if table has not already been saved
                if (!in_array($table, $this->manual_tables)) {
                    $list_records[$table] = $this->getRowsFromTable($table, 'name');
                }
            }
        }

        return $list_records;
    }

    public function saveAWFArrays($awfs)
    {
        $this->enforceAdmin();

        $list_records = array();

        // check if we know the exact table format
        if (!empty($awfs) && $this->verifyKnownTableList()) {

            // do not use this, it is only for testing purposes
            //$this->truncateAllProcessTables();

            // prune relevant records to avoid orphans
            $this->pruneAWFArrays($awfs);

            // 3 main tables
            $list_records['pmse_project'] = $this->saveBeansArray($this->modules_to_sync['pmse_project'], $awfs['pmse_project'], 'name');
            $list_records['pmse_emails_templates'] = $this->saveBeansArray($this->modules_to_sync['pmse_emails_templates'], $awfs['pmse_emails_templates'], 'name');
            $list_records['pmse_business_rules'] = $this->saveBeansArray($this->modules_to_sync['pmse_business_rules'], $awfs['pmse_business_rules'], 'name');

            // special tables
            $list_records['pmse_bpmn_activity'] = $this->saveBeansArray($this->modules_to_sync['pmse_bpmn_activity'], $awfs['pmse_bpmn_activity'], 'name');
            $list_records['pmse_bpm_activity_definition'] = $this->saveBeansArray($this->modules_to_sync['pmse_bpm_activity_definition'], $awfs['pmse_bpm_activity_definition'], 'name');

            // remaining tables
            foreach ($this->modules_to_sync as $table => $module) {
                // if table has not already been saved
                if (!in_array($table, $this->manual_tables)) {
                    $list_records[$table] = $this->saveBeansArray($module, $awfs[$table], 'name');
                }
            }
        }

        return $this->getSaveTotals($list_records);
    }

    // do not use this, it is only for testing purposes!
    public function truncateAllProcessTables()
    {
        $this->enforceAdmin();

        $db = \DBManagerFactory::getInstance();
        foreach ($this->modules_to_sync as $table => $module) {
            $db->query($db->truncateTableSQL($db->quote($table)));
        }
        foreach ($this->modules_not_to_sync as $table => $module) {
            $db->query($db->truncateTableSQL($db->quote($table)));
        }
    }

    public function pruneAWFArrays($awfs)
    {
        $this->enforceAdmin();

        if (!empty($awfs) && $this->verifyKnownTableList()) {
            foreach ($this->pruning_rules as $table => $aaa) {
                if (!empty($awfs[$table])) {
                    $this->pruneAWFArray($awfs[$table], $table);
                }
            }
        }
    }

    public function pruneAWFArray($records, $table)
    {
        $this->enforceAdmin();

        if (!empty($records) && !empty($table) && !empty($this->pruning_rules[$table])) {
            foreach ($records as $record) {
                if (!empty($record)) {
                    if (!empty($record[$this->pruning_rules[$table]['source']])) {
                        if (!empty($this->pruning_rules[$table]['tables'])) {
                            foreach ($this->pruning_rules[$table]['tables'] as $table_to_prune) {
                                $this->pruneAWFSingleProcess($table_to_prune, $this->pruning_rules[$table]['destination'], $record[$this->pruning_rules[$table]['source']]);
                            }
                        }     
                    }
                }
            }
        }
    }

    public function pruneAWFSingleProcess($table, $field, $value)
    {
        $this->enforceAdmin();

        $db = \DBManagerFactory::getInstance();
        if (!empty($table) && !empty($field) && !empty($value) && in_array($table, $this->getDBTables())) {
            $query = "UPDATE ".$db->quote($table)." SET deleted = '1' WHERE ".$db->quote($field)." = ?";
            $db->getConnection()->executeQuery($query, array($value));
        }
    }

    public function getSaveTotals($list_records)
    {
        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 
        $res['errors'] = array(); 

        if (!empty($list_records)) {
            foreach ($list_records as $key => $record) {

                if (!empty($record['errors'])) {
                    foreach ($record['errors'] as $error) {
                        $res['errors'][] = $error;
                    }
                }

                if (!empty($record['update'])) {
                    foreach ($record['update'] as $update) {
                        $res['update'][] = $update;
                    }
                }

                if (!empty($record['create'])) {
                    foreach ($record['create'] as $create) {
                        $res['create'][] = $create;
                    }
                }
 
            }
        }

        return $res;
    }
}
