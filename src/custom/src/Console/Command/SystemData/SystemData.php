<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemData {

    public function checkPath($path) {
        if(!is_dir($path)) {
            // mkdir recursively
            sugar_mkdir($path, null, true);
        }

        // does it have trailing slash?
        if(substr($path, -1) !== '/') {
            $path .= '/';
        }
    
        return $path;
    }

    public function checkJsonFile($file) {

        if(file_exists($file) && is_file($file)) {
            $content = file_get_contents($file);
            json_decode($content);
            return (json_last_error() == JSON_ERROR_NONE);
        }
    
        return false;
    }

    public function putData($file, $data, $print_only = false) {
        if(!empty($file)) {
            if($print_only) {
                print_r($data);
            } else {
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }

    public function getData($file) {
        if(!empty($file) && file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
    }

    public function getPublicTeamsAndUsers($teams) {
        $results = array();
        $results['teams'] = array();
        $results['users_private_teams'] = array();

        if(!empty($teams)) {
            foreach($teams as $id) {
                if(!empty($id)) {
                    $t = \BeanFactory::getBean('Teams', $id);
                    if(!empty($t) && !empty($t->id)) {
                        if($t->private && !empty($t->associated_user_id)) {
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

    public function setPublicTeamsAndUsers($teams_and_users) {

        $return = false;

        if(!empty($teams_and_users)) {
            $teams = array();
            if(!empty($teams_and_users['users_private_teams'])) {
                foreach($teams_and_users['users_private_teams'] as $user_id) {
                    $private_team_id = $this->getUserPrivateTeam($user_id);
                    if(!empty($private_team_id)) {
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

            if(!empty($teams_and_users['teams'])) {
                if($this->checkTeamsExistence($teams_and_users['teams'])) {
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

    public function getTeamsOrUsersRelevantToTeamset($teamset_id) {

        $results = array();

        if(!empty($teamset_id)) {
            $teamset = \BeanFactory::getBean('TeamSets');
            $teams = $teamset->getTeamIds($teamset_id);

            if(!empty($teams)) {
                $results = $this->getPublicTeamsAndUsers($teams);
            } else {
                // single team, not teamset
                $results = $this->getPublicTeamsAndUsers(array($teamset_id));
            }
        }

        return $results;
    }

    public function getUserPrivateTeam($user_id) {
        if(!empty($user_id)) {
            $u = \BeanFactory::getBean('Users', $user_id);
            if(!empty($u) && !empty($u->id)) {
                $team_id = $u->getPrivateTeamID();
                if(!empty($team_id)) {
                    return $team_id;
                }
            }
        }

        return false;
    }

    public function checkTeamsExistence($teams) {
        if(!empty($teams)) {
            foreach($teams as $team_id) {
                $t = \BeanFactory::getBean('Teams', $team_id);
                if(empty($t) || empty($t->id)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function createTeamSet($teams) {
        if(!empty($teams)) {
            if($this->checkTeamsExistence($teams)) {
                $teamset = \BeanFactory::getBean('TeamSets');
                return $teamset->addTeams($teams);
            }
        }

        return false;
    }

    public function getRowsFromTable($table_name, $display_field = 'name') {
        global $current_user;

        $list_records = array();

        if(!empty($table_name) && !empty($display_field)) {
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
                if(!empty($row['id'])) {
                    foreach($row as $field => $value) {
                        $list_records[$row['id']][$field] = $value;
                    }

                    foreach($team_fields as $team_src_field => $team_dst_field) {
                        // get team set team membership and users for private teams if this field exists and it is not empty
                        if(!empty($row[$team_src_field])) {
                            $list_records[$row['id']][$team_dst_field] = $this->getTeamsOrUsersRelevantToTeamset($row[$team_src_field]);
                            unset($list_records[$row['id']][$team_src_field]);
                        }
                    }

                    // if not deleted, remove the field
                    if(empty($list_records[$row['id']]['deleted'])) {
                        unset($list_records[$row['id']]['deleted']);
                    }
                }
            }
        }

        return $list_records;
    }

    public function saveBeansArray($bean_name, $records, $display_field = 'name') {
        if(!empty($bean_name) && !empty($records) && !empty($display_field)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            foreach($records as $id => $record) {
                $current_res = $this->saveBean($bean_name, $record);

                if(empty($record[$display_field])) {
                    $name = $record['id'];
                } else {
                    $name = $record[$display_field];
                }

                if(!empty($current_res['update'])) {
                    $res['update'][$record['id']] = $name;
                } else if(!empty($current_res['create'])) {
                    $res['create'][$record['id']] = $name;
                }

                if(!empty($current_res['error'])) {
                    $res['errors'][$record['id']] = $current_res['error'];
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveBean($bean_name, $params) {
        global $current_user;

        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 
        $res['errors'] = array(); 

        if(!empty($bean_name)) {

            // create team sets
            $teams_errors = false;

            $team_fields = array(
                'team_set_data' => 'team_set_id',
                'acl_team_set_data' => 'acl_team_set_id',
                'team_data' => 'team_id',
            );

            // add few more fields if Users
            if($bean_name == 'Users') {
                $team_fields['default_team_data'] = 'default_team';
            }

            foreach($team_fields as $team_src_field => $team_dst_field) {
                unset($params[$team_dst_field]);
                if(!empty($params[$team_src_field])) {
                    $params[$team_dst_field] = $this->setPublicTeamsAndUsers($params[$team_src_field]);
                    if(empty($params[$team_dst_field])) {
                        $teams_errors = true;
                    }
                    unset($params[$team_src_field]);
                }
            }

            if(empty($teams_errors)) {

                // get also deleted records, so we undelete them if there is a match, instead of having a db error
                $b = \BeanFactory::getBean($bean_name, $params['id'], array(), false);

                if(!empty($b) && !empty($b->id)) {
                    $res['update'][$b->id] = $b->name;
                } else {
                    $res['create'][$params['id']] = $params['name'];
                    // creating with existing guid
                    $b = \BeanFactory::newBean($bean_name);
                    $b->new_with_id = true;
                    $b->id = $params['id'];
                }

                foreach($params as $field => $value) {
                    if($field != 'id' && $field != 'deleted') {
                        $b->$field = $value;
                    }
                }

                // undelete if deleted
                if($b->deleted && !$params['deleted']) {
                    $b->mark_undeleted($b->id);
                }

                // delete if deleted
                if($params['deleted'] && !$b->deleted) {
                    $b->mark_deleted($b->id);
                }

                // preserve date date modified
                if(!empty($b->date_modified)) {
                    $b->update_date_modified = false;
                }
    
                if(!empty($b->modified_user_id)) {
                    $b->update_modified_by = false;
                }

                // preserve created by
                if(!empty($b->created_by)) {
                    $b->set_created_by = false;
                }

                $b->save();
            } else {
                // problem with team sets
                $res['error'] = 'Could not save record for '.$bean_name.' with id '.$params['id'].' due to missing Teams, please check that all Users and Teams have been imported correctly';
            }
        }

        return $res;
    }
}
