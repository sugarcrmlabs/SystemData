<?php

// Enrico Simonetti
// 2017-01-13

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemDataTeams extends SystemData {

    public function getTeams() {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        // retrieve also deleted, but only non private and non global new Teams
        $query = "SELECT id, name, description, deleted " .
            "FROM teams where private = '0' and id != '1' " .
            "order by name, id ";
        $res = $db->query($query);

        $list_records = array();

        while ($row = $db->fetchByAssoc($res)) {
            if(!empty($row['id']) && !empty($row['name'])) {
                foreach($row as $field => $value) {
                    $list_records[$row['id']][$field] = $value;
                }
            
                if(!$row['deleted']) {
                    unset($list_records[$row['id']]['deleted']);
                }
            }
        }

        return $list_records;
    }

    public function saveTeamsArray($teams) {
        global $current_user;

        if(!empty($teams)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            foreach($teams as $id => $team) {
                $current_res = $this->saveTeam($team);

                if(!empty($current_res['update'])) {
                    $res['update'][$team['id']] = $team['name'];
                } else if(!empty($current_res['create'])) {
                    $res['create'][$team['id']] = $team['name'];
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveTeam($params) {
        global $current_user;

        $db = \DBManagerFactory::getInstance();
        // get also deleted records, so we undelete them if there is a match, instead of having a db error
        $b = \BeanFactory::getBean('Teams', $params['id'], array(), false);

        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 

        if(!empty($b) && !empty($b->id)) {
            $res['update'][$b->id] = $b->name;
        } else {
            $res['create'][$params['id']] = $params['name'];
            // creating with existing guid
            $b = \BeanFactory::newBean('Teams');
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

        return $res;
    }
}
