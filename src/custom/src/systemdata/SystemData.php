<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemData
{
    protected $exportSections = [
        'teams' => 'LBL_SYSTEMDATA_TEAMS',
        'roles' => 'LBL_SYSTEMDATA_ROLES',
        'users' => 'LBL_SYSTEMDATA_USERS',
        'reports' => 'LBL_SYSTEMDATA_REPORTS',
        'awf' => 'LBL_SYSTEMDATA_AWF',
    ];

    protected $additionalImportSections = [
        'rolesmembership' => 'LBL_SYSTEMDATA_ROLESMEMBERSHIP',
        'teamsmembership' => 'LBL_SYSTEMDATA_TEAMSMEMBERSHIP',
    ];

    protected $sectionsOutputLabelMapping = [
        'teams' => 'LBL_SYSTEMDATA_MSG_TEAMS',
        'roles' => 'LBL_SYSTEMDATA_MSG_ROLES',
        'users' => 'LBL_SYSTEMDATA_MSG_USERS',
        'teamsmembership' => 'LBL_SYSTEMDATA_MSG_TEAMS_MEMBERSHIP',
        'rolesmembership' => 'LBL_SYSTEMDATA_MSG_ROLES_MEMBERSHIP',
        'reports' => 'LBL_SYSTEMDATA_MSG_REPORTS',
        'awf' => 'LBL_SYSTEMDATA_MSG_AWF',
    ];

    protected $sectionToDataMapping = [
        'teams' => 'teams',
        'roles' => 'roles',
        'users' => 'users',
        'teamsmembership' => 'users',
        'rolesmembership' => 'users',
        'reports' => 'reports',
        'awf' => 'awf',
    ];

    protected $dbTables = [];

    public function getUISectionLabels($section = 'import')
    {
        if ($section == 'import') {
            $allLabels = array_merge($this->exportSections, $this->additionalImportSections);
            $labelsOutput = [];
            foreach ($this->sectionToDataMapping as $key => $val) {
                if (!empty($allLabels[$key])) {
                    $labelsOutput[$key] = $allLabels[$key];
                }
            }
            return $labelsOutput;
        } else {
            return $this->exportSections;
        }    
    }

    public function enforceAdmin()
    {
        global $current_user, $app_strings;
        if (empty($current_user) || !$current_user->isAdmin()) {
            throw new SugarApiExceptionNotAuthorized($app_strings['EXCEPTION_NOT_AUTHORIZED']);
        }
    }

    public function getPublicTeamsAndUsers($teams)
    {
        $this->enforceAdmin();

        $results = array();
        $results['teams'] = array();
        $results['users_private_teams'] = array();

        if (!empty($teams)) {
            foreach ($teams as $id) {
                if (!empty($id)) {
                    $t = \BeanFactory::getBean('Teams', $id);
                    if (!empty($t) && !empty($t->id)) {
                        if ($t->private && !empty($t->associated_user_id)) {
                            // private
                            $results['users_private_teams'][$t->associated_user_id] = $t->associated_user_id;
                        } else {
                            // public
                            $results['teams'][$t->id] = $t->id;
                        }
                    }
                }
            }
        }

        return $results;
    }

    // expects same format returned from getPublicTeamsAndUsers

    public function setPublicTeamsAndUsers($teams_and_users)
    {
        $this->enforceAdmin();

        $return = false;

        if (!empty($teams_and_users)) {
            $teams = array();
            if (!empty($teams_and_users['users_private_teams'])) {
                foreach ($teams_and_users['users_private_teams'] as $user_id) {
                    $private_team_id = $this->getUserPrivateTeam($user_id);
                    if (!empty($private_team_id)) {
                        $teams[$private_team_id] = $private_team_id;
                        // to make sure we return this, if there are only private teams
                        $return = $private_team_id;
                    } else {
                        // could not find, stop import
                        return false;
                    }
                }
            }

            // up to now we know the teams exist

            if (!empty($teams_and_users['teams'])) {
                if ($this->checkTeamsExistence($teams_and_users['teams'])) {
                    return $this->createTeamSet(array_merge($teams, $teams_and_users['teams']));
                } else {
                    // missing team
                    return false;
                }
            }
        }

        return $return;
    }

    // for 7.8 onwards pass either content of team_set_id or acl_team_set_id

    public function getTeamsOrUsersRelevantToTeamset($teamset_id)
    {
        $this->enforceAdmin();

        $results = array();

        if (!empty($teamset_id)) {
            $teamset = \BeanFactory::getBean('TeamSets');
            $teams = $teamset->getTeamIds($teamset_id);

            if (!empty($teams)) {
                $results = $this->getPublicTeamsAndUsers($teams);
            } else {
                // single team, not teamset
                $results = $this->getPublicTeamsAndUsers(array($teamset_id));
            }
        }

        return $results;
    }

    public function getUserPrivateTeam($user_id)
    {
        $this->enforceAdmin();

        if (!empty($user_id)) {
            $u = \BeanFactory::getBean('Users', $user_id);
            if (!empty($u) && !empty($u->id)) {
                $team_id = $u->getPrivateTeamID();
                if (!empty($team_id)) {
                    return $team_id;
                }
            }
        }

        return false;
    }

    public function checkTeamsExistence($teams)
    {
        if (!empty($teams)) {
            foreach ($teams as $team_id) {
                $t = \BeanFactory::getBean('Teams', $team_id);
                if (empty($t) || empty($t->id)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function createTeamSet($teams)
    {
        $this->enforceAdmin();

        if (!empty($teams)) {
            if ($this->checkTeamsExistence($teams)) {
                $teamset = \BeanFactory::getBean('TeamSets');
                return $teamset->addTeams($teams);
            }
        }

        return false;
    }

    public function getDBTables()
    {
        $db = \DBManagerFactory::getInstance();
        if (empty($this->dbTables)) {
            $this->dbTables = $db->getTablesArray();
        }
        return $this->dbTables;
    }

    public function getRowsFromTable($table_name, $display_field = 'name')
    {
        $this->enforceAdmin();

        $db_tables = $this->getDBTables();
        $list_records = array();

        if (!empty($table_name) && !empty($display_field) && in_array($table_name, $db_tables)) {
            $db = \DBManagerFactory::getInstance();

            // retrieve also deleted
            $builder = $db->getConnection()->createQueryBuilder();
            $builder->select('*')->from($table_name);
            $builder->orderBy($display_field);
            $builder->addOrderBy('id');

            $res = $builder->execute();

            $team_fields = array(
                'team_set_id' => 'team_set_data',
                'acl_team_set_id' => 'acl_team_set_data',
                'team_id' => 'team_data',
            );

            while ($row = $res->fetch()) {
                if (!empty($row['id'])) {
                    foreach ($row as $field => $value) {
                        $list_records[$row['id']][$field] = $value;
                    }

                    foreach ($team_fields as $team_src_field => $team_dst_field) {
                        // get team set team membership and users for private teams if this field exists and it is not empty
                        if (!empty($row[$team_src_field])) {
                            $list_records[$row['id']][$team_dst_field] = $this->getTeamsOrUsersRelevantToTeamset($row[$team_src_field]);
                            unset($list_records[$row['id']][$team_src_field]);
                        }
                    }

                    // if not deleted, remove the field
                    if (empty($list_records[$row['id']]['deleted'])) {
                        unset($list_records[$row['id']]['deleted']);
                    }
                }
            }
        }

        return $list_records;
    }

    public function saveBeansArray($bean_name, $records, $display_field = 'name')
    {
        $this->enforceAdmin();

        if (!empty($bean_name) && !empty($records) && !empty($display_field)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array();

            foreach ($records as $id => $record) {
                $current_res = $this->saveBean($bean_name, $record);

                if (empty($record[$display_field])) {
                    $name = $record['id'];
                } else {
                    $name = $record[$display_field];
                }

                if (!empty($current_res['update'])) {
                    $res['update'][$record['id']] = $name;
                } else if (!empty($current_res['create'])) {
                    $res['create'][$record['id']] = $name;
                }

                if (!empty($current_res['error'])) {
                    $res['errors'][$record['id']] = $current_res['error'];
                }
            }

            return $res;
        }
        return false;
    }

    public function saveBean($bean_name, $params)
    {
        $this->enforceAdmin();

        $res = array();
        $res['update'] = array();
        $res['create'] = array();
        $res['errors'] = array();

        if (!empty($bean_name)) {

            // does the bean exist in this system?
            $sampleBean = \BeanFactory::newBean($bean_name);
            if ($sampleBean === null) {
                // problem with non existing bean
                $res['error'] = sprintf(
                    translate('LBL_SYSTEMDATA_ERROR_BEAN'),
                    $bean_name
                );
                return $res;
            } else {
                // does the table exists?
                $tableName = $sampleBean->table_name;
                $tables = $this->getDBTables();
                if (!in_array($tableName, $tables)) {
                    $res['error'] = sprintf(
                        translate('LBL_SYSTEMDATA_ERROR_BEAN'),
                        $bean_name
                    );                   
                    return $res;
                }
            }

            // create team sets
            $teams_errors = false;

            $team_fields = array(
                'team_set_data' => 'team_set_id',
                'acl_team_set_data' => 'acl_team_set_id',
                'team_data' => 'team_id',
            );

            // add few more fields if Users
            if ($bean_name == 'Users') {
                $team_fields['default_team_data'] = 'default_team';
            }

            foreach ($team_fields as $team_src_field => $team_dst_field) {
                unset($params[$team_dst_field]);
                if (!empty($params[$team_src_field])) {
                    $params[$team_dst_field] = $this->setPublicTeamsAndUsers($params[$team_src_field]);
                    if (empty($params[$team_dst_field])) {
                        $teams_errors = true;
                    }
                    unset($params[$team_src_field]);
                }
            }

            if (empty($teams_errors)) {

                // get also deleted records, so we undelete them if there is a match, instead of having a db error
                $b = \BeanFactory::getBean($bean_name, $params['id'], array(), false);

                if (!empty($b) && !empty($b->id)) {
                    $res['update'][$b->id] = $b->name;
                } else {
                    $res['create'][$params['id']] = $params['name'];
                    // creating with existing guid
                    $b = \BeanFactory::newBean($bean_name);
                    $b->new_with_id = true;
                    $b->id = $params['id'];
                }

                foreach ($params as $field => $value) {
                    if ($field != 'id' && $field != 'deleted') {
                        $b->$field = $value;
                    }
                }

                // undelete if deleted
                if ($b->deleted && !$params['deleted']) {
                    $b->mark_undeleted($b->id);
                }

                // delete if deleted
                if ($params['deleted'] && !$b->deleted) {
                    $b->mark_deleted($b->id);
                }

                // preserve date date modified
                if (!empty($b->date_modified)) {
                    $b->update_date_modified = false;
                }

                if (!empty($b->modified_user_id)) {
                    $b->update_modified_by = false;
                }

                // preserve created by
                if (!empty($b->created_by)) {
                    $b->set_created_by = false;
                }

                $b->save();
            } else {
                // problem with team sets
                $res['error'] = sprintf(
                    translate('LBL_SYSTEMDATA_ERROR_TEAMSETS'),
                    $bean_name,
                    $params['id']
                );
            }
        }

        return $res;
    }

    public function isValidJson($json)
    {
        if (!empty($json)) {
            json_decode($json);
            return (json_last_error() == JSON_ERROR_NONE);
        }
        
        return false;
    }
    
    public function jsonEncode($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function jsonDecode($json)
    {
        if ($this->isValidJson($json)) {
            return json_decode($json, true);
        } else {
            return false;
        }
    }

    public function getFromObject($name)
    {
        $this->enforceAdmin();

        if (!empty($this->exportSections[$name]) && !empty($name)) {
            switch ($name) {
                case 'teams':
                    $sd = new SystemDataTeams();
                    return array($name => $sd->getTeams());
                    break;
                case 'roles':
                    $sd = new SystemDataRoles();
                    return array($name => $sd->getRoles());
                    break;
                case 'users':
                    $sd = new SystemDataUsers();
                    return array($name => $sd->getUsers());
                    break;
                case 'awf':
                    $sd = new SystemDataAWF();
                    return array($name => $sd->getAWF());
                    break;
                case 'reports':
                    $sd = new SystemDataReports();
                    return array($name => $sd->getReports());
                    break;
            }
        }

        return array();
    }

    public function saveToObject($name, $data)
    {
        $this->enforceAdmin();

        if (empty($name)) {
            return [
                translate('LBL_SYSTEMDATA_MSG_ERROR_EMPTY')
            ];
        }

        if (!empty($this->sectionsOutputLabelMapping[$name]) && !empty($data[$this->sectionToDataMapping[$name]])) {
            switch ($name) {
                case 'teams':
                    $sd = new SystemDataTeams();
                    $res = $sd->saveTeamsArray($data[$this->sectionToDataMapping[$name]]);
                    return [
                        sprintf(
                            translate($this->sectionsOutputLabelMapping[$name]),
                            count($res['update']),
                            count($res['create'])
                        )
                    ];
                    break;
                case 'roles':
                    $sd = new SystemDataRoles();
                    $res = $sd->saveRolesArray($data[$this->sectionToDataMapping[$name]]);
                    return [
                        sprintf(
                            translate($this->sectionsOutputLabelMapping[$name]),
                            count($res['update']),
                            count($res['create'])
                        )
                    ];
                    break;
                case 'users':
                    $sd = new SystemDataUsers();
                    $res = $sd->saveUsersArray($data[$this->sectionToDataMapping[$name]]);
                    return [
                        sprintf(
                            translate($this->sectionsOutputLabelMapping[$name]),
                            count($res['update']),
                            count($res['create'])
                        )
                    ];
                    break;
                case 'awf':
                    $sd = new SystemDataAWF();
                    $res = $sd->saveAWFArrays($data[$this->sectionToDataMapping[$name]]);
                    $output = [];
                    if(!empty($res['errors'])) {
                        foreach($res['errors'] as $message) {
                            $output[] = $message;
                        }
                    }
                    $output[] = sprintf(
                        translate($this->sectionsOutputLabelMapping[$name]),
                        count($res['update']),
                        count($res['create'])
                    );
                    return $output;
                    break;
                case 'reports':
                    $sd = new SystemDataReports();
                    $res = $sd->saveReportsArray($data[$this->sectionToDataMapping[$name]]);
                    $output = [];
                    if(!empty($res['errors'])) {
                        foreach($res['errors'] as $message) {
                            $output[] = $message;
                        }
                    }
                    $output[] = sprintf(
                        translate($this->sectionsOutputLabelMapping[$name]),
                        count($res['update']),
                        count($res['create'])
                    );
                    return $output;
                    break;
                case 'rolesmembership':
                    $sd = new SystemDataUsers();
                    $res = $sd->saveRolesMembership($data[$this->sectionToDataMapping[$name]]);
                    return [
                        sprintf(
                            translate($this->sectionsOutputLabelMapping[$name]),
                            count($res['processed']),
                            (count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])),
                            count($res['skipped']),
                            (count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped']))
                        )
                    ];
                    break;
                case 'teamsmembership':
                    $sd = new SystemDataUsers();
                    $res = $sd->saveTeamsMembership($data[$this->sectionToDataMapping[$name]]);
                    return [
                        sprintf(
                            translate($this->sectionsOutputLabelMapping[$name]),
                            count($res['processed']),
                            (count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])),
                            count($res['skipped']),
                            (count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped']))
                        )
                    ];
                    break;
            }
        }

        $labels = $this->getUISectionLabels('import');
        // empty data for this section
        return [
            sprintf(
                translate('LBL_SYSTEMDATA_MSG_ERROR_EMPTY_FOR_SECTION'),
                translate($labels[$name])
            )
        ];
    }
}
