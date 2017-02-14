<?php

// Enrico Simonetti
// 2017-01-24

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemDataReports extends SystemData {

    public function getReports() {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        // retrieve also deleted
        $query = "SELECT * " .
            "FROM saved_reports " .
            "order by name, id ";
        $res = $db->query($query);

        $list_records = array();

        while ($row = $db->fetchByAssoc($res)) {
            if(!empty($row['id']) && !empty($row['name'])) {
                foreach($row as $field => $value) {
                    $list_records[$row['id']][$field] = $value;
                }

                // get team set team membership and users for private teams
                if(!empty($row['team_set_id'])) {
                    $list_records[$row['id']]['team_set_data'] = $this->getTeamsOrUsersRelevantToTeamset($row['team_set_id']);
                }
                unset($list_records[$row['id']]['team_set_id']);
                
                // get team set team membership and users for private teams for the acl feature
                if(!empty($row['acl_team_set_id'])) {
                    $list_records[$row['id']]['acl_team_set_data'] = $this->getTeamsOrUsersRelevantToTeamset($row['acl_team_set_id']);
                }
                unset($list_records[$row['id']]['acl_team_set_id']);
            
                if(!$row['deleted']) {
                    unset($list_records[$row['id']]['deleted']);
                }
            }
        }

        return $list_records;
    }

    public function saveReportsArray($reports) {
        global $current_user;

        if(!empty($reports)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            foreach($reports as $id => $report) {
                $current_res = $this->saveReport($report);

                if(!empty($current_res['update'])) {
                    $res['update'][$report['id']] = $report['name'];
                } else if(!empty($current_res['create'])) {
                    $res['create'][$report['id']] = $report['name'];
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveReport($params) {
        global $current_user;

        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 

        // create team sets
        $team_set_id = '';
        if(!empty($params['team_set_data'])) {
            $team_set_id = $this->setPublicTeamsAndUsers($params['team_set_data']);
            $params['team_set_id'] = $team_set_id;
        }

        // create team sets
        $params['acl_team_set_id'] = '';
        if(!empty($params['acl_team_set_data'])) {
            $params['acl_team_set_id'] = $this->setPublicTeamsAndUsers($params['acl_team_set_data']);
        }

        // only save if there is a team set        
        if(!empty($team_set_id)) {

            // get also deleted records, so we undelete them if there is a match, instead of having a db error
            $b = \BeanFactory::getBean('Reports', $params['id'], array(), false);


            if(!empty($b) && !empty($b->id)) {
                $res['update'][$b->id] = $b->name;
            } else {
                $res['create'][$params['id']] = $params['name'];
                // creating with existing guid
                $b = \BeanFactory::newBean('Reports');
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

            $b->save();
        } else {
            $res['errors'][] = 'Could not save Report '.$params['id'].' due to missing Teams, please check that all Users and Teams have been imported correctly';
        }

        return $res;
    }
}
