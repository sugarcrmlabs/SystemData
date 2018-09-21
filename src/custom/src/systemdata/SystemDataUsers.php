<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemDataUsers extends SystemData {

    public function getUsers() {
        global $current_user;

        $db = \DBManagerFactory::getInstance();

        // retrieve also deleted
        $builder = $db->getConnection()->createQueryBuilder();
        $builder->select(array('id', 'user_name'))->from('users');
        $builder->orderBy('id');
        $res = $builder->execute();

        $team_fields = array(
            'team_set_id' => 'team_set_data',
            'acl_team_set_id' => 'acl_team_set_data',
            'default_team' => 'default_team_data',
            'team_id' => 'team_data',
        );

        $list_users = array();

        while ($row = $res->fetch()) {
            if(!empty($row['id']) && !empty($row['user_name'])) {
                // allow retrieval of deleted users as well
                $u = \BeanFactory::getBean('Users', $row['id'], array(), false);

                if(!empty($u) && !empty($u->id)) {
                    foreach($u->field_defs as $user_field_name) {
                        $user_actual_field_name = $user_field_name['name'];
                        if(isset($u->$user_actual_field_name) && $user_field_name['type'] != 'link') {
                            $list_users[$u->id]['fields'][$user_actual_field_name] = $u->$user_actual_field_name;
                        }
                    }
                    unset($list_users[$u->id]['fields']['acl_role_set_id']);
                    unset($list_users[$u->id]['fields']['email1']);

                    if(!$u->deleted) {
                        unset($list_users[$u->id]['fields']['deleted']);

                        // TODO: write on guide to copy the files on upload folder as there might be profile pictures

                        foreach($team_fields as $team_src_field => $team_dst_field) {
                            // get team set team membership and users for private teams if this field exists and it is not empty
                            if(isset($list_users[$u->id]['fields'][$team_src_field])) {
                                $list_users[$u->id]['fields'][$team_dst_field] = $this->getTeamsOrUsersRelevantToTeamset($list_users[$u->id]['fields'][$team_src_field]);
                                unset($list_users[$u->id]['fields'][$team_src_field]);
                            }
                        }

                        // get additional explicit team membership
                        $teams = $u->get_my_teams($u->id);
                        if(!empty($teams)) {
                            foreach($teams as $team) {
                                if(empty($team->implicit_assign) && empty($team->private) && $team->id != 1) {
                                    $list_users[$u->id]['teams'][$team->id] = $team->name;
                                }
                            }
                        }

                        // get role membership
                        $roles = \ACLRole::getUserRoles($u->id, false);
                        if(!empty($roles)) {
                            foreach($roles as $role) {
                                $list_users[$u->id]['roles'][$role->id] = $role->name;
                            }
                        }

                        // get dashboards
                        $dashboards = $this->getUserDashboards($u->id);
                        if(!empty($dashboards)) {
                            $list_users[$u->id]['dashboards'] = array();
                            $list_users[$u->id]['dashboards'] = $dashboards;
                        }

                        // get preferences
                        $prefs = $this->getUserPreferences($u->id);
                        if(!empty($prefs)) {
                            $list_users[$u->id]['preferences'] = array();
                            $list_users[$u->id]['preferences'] = $prefs;
                        }
                    }
                }
            }
        }

        return $list_users;
    }

    public function getUserDashboards($user_id) {
        $db = \DBManagerFactory::getInstance();

        $records = array();
        
        if(!empty($user_id)) {
            $builder = $db->getConnection()->createQueryBuilder();
            $builder->select('*')->from('dashboards');
            $builder->where("deleted = '0' AND assigned_user_id = " . $builder->createPositionalParameter($user_id)); 
            $builder->orderBy('id');
          
            $res = $builder->execute();

            $team_fields = array(
                'team_set_id' => 'team_set_data',
                'acl_team_set_id' => 'acl_team_set_data',
                'team_id' => 'team_data',
            );

            while ($row = $res->fetch()) {
                if(!empty($row['id'])) {
                    unset($row['deleted']);
                    $records[$row['id']] = $row;

                    foreach($team_fields as $team_src_field => $team_dst_field) {
                        // get team set team membership and users for private teams if this field exists and it is not empty
                        if(!empty($row[$team_src_field])) {
                            $records[$row['id']][$team_dst_field] = $this->getTeamsOrUsersRelevantToTeamset($row[$team_src_field]);
                            if (empty($records[$row['id']][$team_dst_field]) || empty(array_filter($records[$row['id']][$team_dst_field]))) {
                                $records[$row['id']][$team_dst_field] = null;
                            }
                            unset($records[$row['id']][$team_src_field]);
                        }
                    }
                }
            }
        }

        return $records;
    }

    public function clearPreviousUserDashboards($user_id) {
        $db = \DBManagerFactory::getInstance();

        if(!empty($user_id)) {
            $builder = $db->getConnection()->createQueryBuilder();
            $builder->delete('dashboards');
            $builder->where('assigned_user_id = ' . $builder->createPositionalParameter($user_id));
            $res = $builder->execute();
        }
    }

    public function getUserPreferences($user_id) {
        $db = \DBManagerFactory::getInstance();

        $records = array();
        
        if(!empty($user_id)) {
            $builder = $db->getConnection()->createQueryBuilder();
            $builder->select('*')->from('user_preferences');
            $builder->where("deleted = '0'");
            $builder->andWhere('assigned_user_id = ' . $builder->createPositionalParameter($user_id)); 
            $builder->orderBy('id');
          
            $res = $builder->execute();

            while ($row = $res->fetch()) {
                if(!empty($row['id'])) {
                    unset($row['deleted']);

                    // decode so that it is clear on the json what we do
                    $row['contents'] = base64_decode($row['contents']);

                    $records[$row['id']] = $row;
                }
            }
        }

        return $records;
    }

    public function clearPreviousUserPreferences($user_id) {
        $db = \DBManagerFactory::getInstance();

        if(!empty($user_id)) {

            // reset all the current user preferences from cache too or we might get skewed values
            $current_preferences = $this->getUserPreferences($user_id);
            if(!empty($current_preferences)) {
                $u = \BeanFactory::getBean('Users', $user_id);
                if(!empty($u) && !empty($u->id)) { 
                    foreach($current_preferences as $current_preference) {
                        $u->resetPreferences($current_preference['category']);
                    }
                }
            }

            // now delete from db
            $builder = $db->getConnection()->createQueryBuilder();
            $builder->delete('user_preferences');
            $builder->where('assigned_user_id = ' . $builder->createPositionalParameter($user_id));
            $res = $builder->execute();
        }
    }

    public function saveUsersArray($users) {
        global $current_user;

        if(!empty($users)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            // first run of saves to save all user's bones first (with private teams and all)
            foreach($users as $user) {

                // unset all fields that need to be saved on the next run if present
                unset($user['fields']['reports_to_id']);
                unset($user['fields']['team_set_data']);
                unset($user['fields']['team_set_id']);
                unset($user['fields']['acl_team_set_data']);
                unset($user['fields']['acl_team_set_id']);
                unset($user['fields']['default_team_data']);
                unset($user['fields']['default_team']);
                unset($user['fields']['team_data']);
                unset($user['fields']['team_id']);
                unset($user['fields']['acl_role_set_id']);
    
                $current_res = $this->saveBean('Users', $user['fields']);

                if(!empty($current_res['update'])) {
                    $res['update'][$user['fields']['id']] = $user['fields']['user_name'];
                } else if(!empty($current_res['create'])) {
                    $res['create'][$user['fields']['id']] = $user['fields']['user_name'];
                }
            }

            // second run of saves to save all user's preferences, dashboards, default teams, reports to etc
            foreach($users as $user) {
                unset($user['fields']['acl_role_set_id']);

                $this->saveBean('Users', $user['fields']);

                // save preferences
                $this->clearPreviousUserPreferences($user['fields']['id']);
                $this->saveUserPreferences($user);

                // save dashboards
                $this->clearPreviousUserDashboards($user['fields']['id']);
                $this->saveUserDashboards($user);
            }

            return $res;
        }
        return false; 
    }

    public function saveTeamsMembership($users) {
        global $current_user;

        if(!empty($users)) {
            $res = array();
            $res['processed'] = array();
            $res['skipped'] = array();

            foreach($users as $user_id => $user) {
                if(!empty($user['teams'])) {

                    foreach($user['teams'] as $team_id => $team_name) {

                        $t = \BeanFactory::getBean('Teams', $team_id);
                        if(empty($t) || empty($t->id)) {
                            $res['skipped'][$team_id][$user_id] = $user['fields']['user_name'];
                        } else {
                            $res['processed'][$team_id][$user_id] = $user['fields']['user_name'];
                            $t->add_user_to_team($user_id);
                        }
                    }
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveRolesMembership($users) {
        global $current_user;

        if(!empty($users)) {
            $res = array();
            $res['processed'] = array();
            $res['skipped'] = array();

            foreach($users as $user_id => $user) {
                if(!empty($user['roles'])) {

                    foreach($user['roles'] as $role_id => $role_name) {

                        $r = \BeanFactory::getBean('ACLRoles', $role_id);
                        if(empty($r) || empty($r->id)) {
                            $res['skipped'][$role_id][$user_id] = $user['fields']['user_name'];
                        } else {
                            $res['processed'][$role_id][$user_id] = $user['fields']['user_name'];
                            $r->load_relationship('users');
                            $r->users->add($user_id);
                        }
                    }
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveUserPreferences($user_params) {
        if(!empty($user_params) && !empty($user_params['fields']['id']) && !empty($user_params['preferences'])) {
            $u = \BeanFactory::getBean('Users', $user_params['fields']['id']);

            if(!empty($u) && !empty($u->id)) {
                foreach($user_params['preferences'] as $preference_id => $preference_content) {

                    $params = array();
                    $params['assigned_user_id'] = $u->id;

                    foreach($preference_content as $field_name => $field_content) {
                        // re-encode as for clarity we decoded it on export
                        if($field_name == 'contents') {
                            $field_content = base64_encode($field_content);
                        }
                        $params[$field_name] = $field_content;
                    }
                    $this->saveBean('UserPreferences', $params); 
                }
                
                return true;
            }
        }

        return false;
    }

    public function saveUserDashboards($user_params) {
        if(!empty($user_params) && !empty($user_params['fields']['id']) && !empty($user_params['dashboards'])) {
            $u = \BeanFactory::getBean('Users', $user_params['fields']['id']);

            if(!empty($u) && !empty($u->id)) {
                $db = \DBManagerFactory::getInstance();

                foreach($user_params['dashboards'] as $dashboard_id => $dashboard_content) {

                    $this->saveBean('Dashboards', $dashboard_content); 

                    // system overwrites the id of assigned user as the current user... need to set it back manually!
                    $builder = $db->getConnection()->createQueryBuilder();
                    $builder->update('dashboards');
                    $builder->set('assigned_user_id', $builder->createPositionalParameter($u->id));
                    $builder->where('id = ' . $builder->createPositionalParameter($dashboard_id));
                    $res = $builder->execute();
                }
                
                return true;
            }
        }

        return false;
    }
}
