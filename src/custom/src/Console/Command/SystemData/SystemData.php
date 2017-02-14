<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemData {

    public function checkPath($path) {
        if(!is_dir($path)) {
            // mkdir
            sugar_mkdir($path);
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
        if(!empty($teams_and_users)) {
            $teams = array();
            if(!empty($teams_and_users['users_private_teams'])) {
                foreach($teams_and_users['users_private_teams'] as $user_id) {
                    $private_team_id = $this->getUserPrivateTeam($user_id);
                    if(!empty($private_team_id)) {
                        $teams[$private_team_id] = $private_team_id;
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
    
        return false;
    }

    // for 7.8 onwards pass either content of team_set_id or acl_team_set_id

    public function getTeamsOrUsersRelevantToTeamset($teamset_id) {

        $results = array();

        if(!empty($teamset_id)) {
            $teamset = \BeanFactory::getBean('TeamSets');
            $teams = $teamset->getTeamIds($teamset_id);

            if(!empty($teams)) {
                $results = $this->getPublicTeamsAndUsers($teams);
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
}
