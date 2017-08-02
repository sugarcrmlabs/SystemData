<?php

// Enrico Simonetti
// 2017-01-13

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemDataTeams extends SystemData {

    public function getTeams() {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        // retrieve also deleted, but only non private and non global new Teams
        $builder = $db->getConnection()->createQueryBuilder();
        $builder->select(array('id', 'name', 'description', 'deleted'))->from('teams');
        $builder->where("private = '0' AND id != '1'");
        $builder->orderBy('name');
        $builder->addOrderBy('id');

        $res = $builder->execute();

        $list_records = array();

        while ($row = $res->fetch()) {
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
        return $this->saveBeansArray('Teams', $teams, 'name');
    }
}
