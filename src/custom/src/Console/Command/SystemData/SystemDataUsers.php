<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemDataUsers extends SystemData {

    public function getActiveUsers() {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        $query = "SELECT id, user_name " .
            "FROM users " .
            "WHERE deleted = 0 AND status = 'Active' order by id ";
        $res = $db->query($query);

        $list_users = array();

        while ($row = $db->fetchByAssoc($res)) {
            if(!empty($row['id']) && !empty($row['user_name'])) {
                $u = \BeanFactory::getBean('Users', $row['id']);

                if(!empty($u) && !empty($u->id)) {
                    foreach($u->field_defs as $user_field_name) {
                        $user_actual_field_name = $user_field_name['name'];
                        if(isset($u->$user_actual_field_name) && $user_field_name['type'] != 'link') {
                            $list_users[$u->id]['fields'][$user_actual_field_name] = $u->$user_actual_field_name;
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

        return $list_users;
    }

    public function getUserDashboards($user_id) {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        $records = array();
        
        if(!empty($user_id)) {
            $query = "SELECT * " .
                "FROM dashboards " .
                "WHERE deleted = 0 AND assigned_user_id='".$db->quote($user_id)."' order by id ";
            $res = $db->query($query);

            while ($row = $db->fetchByAssoc($res)) {
                if(!empty($row['id'])) {
                    // unset what is not useful (i use this method so that it will be forward compatible as much as possible with other fields)
                    unset($row['date_entered']);
                    unset($row['date_modified']);
                    unset($row['deleted']);
                    unset($row['assigned_user_id']);
                    unset($row['modified_user_id']);
                    unset($row['created_by']);

                    $records[$row['id']] = $row;
                }
            }
        }

        return $records;
    }

    public function getUserPreferences($user_id) {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        $records = array();
        
        if(!empty($user_id)) {
            $query = "SELECT * " .
                "FROM user_preferences " .
                "WHERE deleted = 0 AND assigned_user_id='".$db->quote($user_id)."' order by id ";
            $res = $db->query($query);

            while ($row = $db->fetchByAssoc($res)) {
                if(!empty($row['id'])) {
                    // unset what is not useful (i use this method so that it will be forward compatible as much as possible with other fields)
                    unset($row['date_entered']);
                    unset($row['date_modified']);
                    unset($row['deleted']);
                    unset($row['assigned_user_id']);

                    // decode so that it is clear on the json what we do
                    $row['contents'] = base64_decode($row['contents']);

                    $records[$row['id']] = $row;
                }
            }
        }

        return $records;
    }

    public function saveUsersArray($users) {
        global $current_user;

        if(!empty($users)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            while(count($users) > 0) {

                // get first element
                
                // reverse content keeping keys
                $users = array_reverse($users, true);
                // get last element
                $user = array_pop($users);
                // reverse content keeping keys
                $users = array_reverse($users, true);
               
                // this will only save the record if reports to is empty, or if there is already the report to in the db, or it will empty it out reports to field if there is no record already in the db and on the import file
                $current_res = $this->saveUser($user['fields'], $users);

                //if(!empty($current_res['reports_to_id'])) {
                if($current_res['saved'] === false) {
                    // put the current record back on the queue
                    //echo 'putting user '.$user['id'].' back into queue'.PHP_EOL;
                    // add as last element
                    $users[$user['fields']['id']] = $user;
                } else {

                    if(!empty($current_res['update'])) {
                        $res['update'][$user['fields']['id']] = $user['fields']['user_name'];
                    } else if(!empty($current_res['create'])) {
                        $res['create'][$user['fields']['id']] = $user['fields']['user_name'];
                    }

                    // save preferences
                    $this->saveUserPreferences($user);
                    // save dashboards
                    $this->saveUserDashboards($user);
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveUser($user_params, $users = array()) {
        // get also deleted records, so we undelete them if there is a match, instead of having a db error
        $u = \BeanFactory::getBean('Users', $user_params['id'], array(), false);

        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 
        $res['reports_to_id'] = '';
        $res['saved'] = false;

        if(!empty($u) && !empty($u->id)) {
            $res['update'][$u->id] = $u->user_name;
        } else {
            $res['create'][$user_params['id']] = $user_params['user_name'];
            // creating with existing guid
            $u = \BeanFactory::newBean('Users');
            $u->new_with_id = true;
            $u->id = $user_params['id'];
        }

        foreach($user_params as $ufield => $uvalue) {
            if($ufield != 'id' && $ufield != 'email') {
                $u->$ufield = $uvalue;
            }
        }

        // check for report to
        if(!empty($u->reports_to_id)) {
            // does the user already exist?
            $u_reports = \BeanFactory::getBean('Users', $u->reports_to_id);
            if(empty($u_reports->id)) {
                // does the user exits on this file?
                if(!empty($users[$u->reports_to_id])) {
                    // we need to re-process this user, put back in the list
                    $res['reports_to_id'] = $u->reports_to_id;
                } else {
                    // user is not in the list, setting empty
                    $u->reports_to_id = '';
                }
            }
        }

        // undelete if deleted
        if($u->deleted) {
            $u->mark_undeleted($u->id);
            // TODO global team access?
            // TODO private team undelete and relate?
        }

        if(empty($res['reports_to_id'])) {
            //echo "saving ".$u->id.PHP_EOL;
            $u->save();
            $res['saved'] = true;

        } else {
            //echo "NOT saving ".$u->id.PHP_EOL;
        }

        return $res;
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

                    // retrieve even if deleted on the other environment, to allow restoring
                    $p = \BeanFactory::getBean('UserPreferences', $preference_id, array(), false);

                    if(!empty($p) && !empty($p->id)) {

                        // undelete if deleted
                        if($p->deleted) {
                            $p->mark_undeleted($p->id);
                        }
                       
                        foreach($preference_content as $field_name => $field_content) {
                            // re-encode as for clarity we decoded it on export
                            if($field_name == 'contents') {
                                $field_content = base64_encode($field_content);
                            }
                            $p->$field_name = $field_content;
                        }
                
                        $p->assigned_user_id = $u->id;

                        $p->save();
                    }
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
                foreach($user_params['dashboards'] as $dashboard_id => $dashboard_content) {

                    // retrieve even if deleted on the other environment, to allow restoring
                    $d = \BeanFactory::getBean('Dashboards', $dashboard_id, array(), false);

                    if(!empty($d) && !empty($d->id)) {

                        // undelete if deleted
                        if($d->deleted) {
                            $d->mark_undeleted($d->id);
                        }
                       
                        foreach($dashboard_content as $field_name => $field_content) {
                            $d->$field_name = $field_content;
                        }

                        $d->save();

                        // system overwrites the id of assigned user as the current user... need to set it back manually!
                        $query = "UPDATE dashboards SET assigned_user_id = '".$GLOBALS['db']->quote($u->id)."' WHERE id = '".$GLOBALS['db']->quote($d->id)."'";
                        $GLOBALS['db']->query($query); 
                    }
                }
                
                return true;
            }
        }

        return false;
    }
}
