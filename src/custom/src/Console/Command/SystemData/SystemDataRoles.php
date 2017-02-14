<?php

// Enrico Simonetti
// 2017-01-12

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;
include('modules/ACLActions/actiondefs.php');
include('modules/ACLFields/actiondefs.php');

class SystemDataRoles extends SystemData {

    public $acl_modules_keywords = array(
        'ACL_ALLOW_ADMIN_DEV',
        'ACL_ALLOW_ADMIN',
        'ACL_ALLOW_DEV',
        'ACL_ALLOW_ALL',
        'ACL_ALLOW_ENABLED',
        'ACL_ALLOW_SELECTED_TEAMS',
        'ACL_ALLOW_OWNER',
        'ACL_ALLOW_NORMAL',
        'ACL_ALLOW_DEFAULT',
        'ACL_ALLOW_DISABLED',
        'ACL_ALLOW_NONE',
    );
    
    public $acl_fields_keywords = array(
        'ACL_READ_ONLY',
        'ACL_READ_WRITE',
        'ACL_READ_SELECTED_TEAMS_WRITE',
        'ACL_SELECTED_TEAMS_READ_OWNER_WRITE',
        'ACL_SELECTED_TEAMS_READ_WRITE',
        'ACL_OWNER_READ_WRITE',
        'ACL_READ_OWNER_WRITE',
        'ACL_ALLOW_NONE',
        'ACL_ALLOW_DEFAULT',
        'ACL_FIELD_DEFAULT',
    );

    public $acl_module_default = 'ACL_ALLOW_DEFAULT';    
    public $acl_field_default = 'ACL_FIELD_DEFAULT';    

    public function getRoles() {
        global $current_user;
        $db = \DBManagerFactory::getInstance();

        // retrieve also deleted
        $query = "SELECT id, name, description, deleted " .
            "FROM acl_roles " .
            "order by name, id ";
        $res = $db->query($query);

        $list_records = array();

        while ($row = $db->fetchByAssoc($res)) {
            if(!empty($row['id']) && !empty($row['name'])) {
                foreach($row as $field => $value) {
                    $list_records[$row['id']]['role_info'][$field] = $value;
                }
            
                if(!$row['deleted']) {
                    unset($list_records[$row['id']]['role_info']['deleted']);
                    $list_records[$row['id']]['modules_info'] = $this->getRoleLevelPermissions($row['id']);
                    $list_records[$row['id']]['fields_info'] = $this->getFieldLevelPermissions($row['id']);
                }
            }
        }

        return $list_records;
    }

    public function getRoleLevelPermissions($role_id) {

        $list_records = array();

        if(!empty($role_id)) {
 
            global $current_user;
            $db = \DBManagerFactory::getInstance();

            $query = "SELECT ara.id, ara.access_override, ara.deleted, aa.category, aa.name " .
                "FROM acl_roles_actions ara JOIN acl_actions aa ON ara.action_id = aa.id " .
                "WHERE ara.deleted = 0 and aa.deleted = 0 and ara.role_id = '".$db->quote($role_id)."' order by aa.category, aa.name, ara.id ";
            $res = $db->query($query);

            $list_records = array();

            while ($row = $db->fetchByAssoc($res)) {

                $list_records[$row['category']][$row['name']] = $this->getModulePermName($row['access_override']);
            }
        }

        return $list_records;
    }

    public function clearRoleLevelPermissions($role_id) {
        if(!empty($role_id)) {
            $db = \DBManagerFactory::getInstance();

            //$query = "UPDATE acl_roles_actions set deleted = '1' where role_id = '".$db->quote($role_id)."'";
            // let's do a hard delete here too, to keep things clean
            $query = "DELETE from acl_roles_actions where role_id = '".$db->quote($role_id)."'";
            $db->query($query);
        }
    }

    public function getFieldLevelPermissions($role_id) {

        $list_records = array();

        if(!empty($role_id)) {
            global $current_user;
            $db = \DBManagerFactory::getInstance();

            $query = "SELECT id, name, category, aclaccess, deleted " .
                "FROM acl_fields " .
                "WHERE deleted = 0 and role_id = '".$db->quote($role_id)."' order by category, name, id ";
            $res = $db->query($query);

            while ($row = $db->fetchByAssoc($res)) {
                $list_records[$row['category']][$row['name']] = $this->getFieldPermName($row['aclaccess']);
            }
        }

        return $list_records;
    }

    public function clearFieldLevelPermissions($role_id) {
        if(!empty($role_id)) {
            $db = \DBManagerFactory::getInstance();

            // need hard delete as the core code only inserts
            $query = "DELETE from acl_fields where role_id = '".$db->quote($role_id)."'";
            $db->query($query);
        }
    }

    public function saveRolesArray($roles) {
        global $current_user;

        if(!empty($roles)) {

            $res = array();
            $res['update'] = array();
            $res['create'] = array(); 

            foreach($roles as $id => $role) {
                $current_res = $this->saveRole($role);

                if(!empty($current_res['update'])) {
                    $res['update'][$role['role_info']['id']] = $role['role_info']['name'];
                } else if(!empty($current_res['create'])) {
                    $res['create'][$role['role_info']['id']] = $role['role_info']['name'];
                }
            }

            return $res;
        }
        return false; 
    }

    public function saveRole($params) {
        global $current_user;

        $db = \DBManagerFactory::getInstance();
        // get also deleted records, so we undelete them if there is a match, instead of having a db error
        $b = \BeanFactory::getBean('ACLRoles', $params['role_info']['id'], array(), false);

        $res = array();
        $res['update'] = array();
        $res['create'] = array(); 

        if(!empty($b) && !empty($b->id)) {
            $res['update'][$b->id] = $b->name;
        } else {
            $res['create'][$params['role_info']['id']] = $params['role_info']['name'];
            // creating with existing guid
            $b = \BeanFactory::newBean('ACLRoles');
            $b->new_with_id = true;
            $b->id = $params['role_info']['id'];
        }

        foreach($params['role_info'] as $field => $value) {
            if($field != 'id' && $field != 'deleted') {
                $b->$field = $value;
            }
        }

        // undelete if deleted
        if($b->deleted && !$params['role_info']['deleted']) {
            $b->mark_undeleted($b->id);
        }

        $b->save();
        $b->retrieve($b->id);

        // delete if deleted
        if($params['role_info']['deleted']) {
            $b->mark_deleted($b->id);
            $this->clearRoleLevelPermissions($b->id);
            $this->clearFieldLevelPermissions($b->id);
        } else {

            // handle module acl
            if(!empty($params['modules_info'])) {
                // wipe all existing settings so that unset values will be actually unset
                $this->clearRoleLevelPermissions($b->id);

                foreach($params['modules_info'] as $mod => $perm) {
                    if(!empty($perm)) {
                        foreach($perm as $action => $access) {
                            // need to retrieve action id from db now
                            $query = "SELECT id " .
                                "FROM acl_actions " .
                                "WHERE category = '".$db->quote($mod)."' and name = '".$db->quote($action)."' and deleted = '0' ";
                            $actionres = $db->query($query);

                            if($row = $db->fetchByAssoc($actionres)) {
                                if(!empty($row['id'])) {
                                    //echo $mod.' '.$b->id.' '.$action.' '.$access.' '.constant($access).PHP_EOL;
                                    $b->setAction($b->id, $row['id'], constant($access));
                                }
                            }
                        }
                    }
                }
            }

            // handle field acl
            $aclfield = \BeanFactory::newBean('ACLFields');
            if(!empty($params['fields_info'])) {
                // wipe all existing settings so that unset values will be actually unset
                $this->clearFieldLevelPermissions($b->id);

                foreach($params['fields_info'] as $mod => $perm) {
                    if(!empty($perm)) {
                        foreach($perm as $field_id => $access) {
                            //echo $mod.' '.$b->id.' '.$field_id.' '.$access.' '.constant($access).PHP_EOL;
                            $aclfield->setAccessControl($mod, $b->id, $field_id, constant($access));
                        }
                    }
                }
            }
        }

        return $res;
    }
    
    public function getModulePermValue($perm_name) {
        if(!empty($perm_name)) {
            if(!empty($this->acl_modules_keywords[$perm_name])) {
                return constant($perm_name);     
            } else {
                // bad, we do not know this permission, this script might be outdated! we could return the default but we don't want to as we are unaware of this permission
                die('bad stuff, block here');
                // TODO handle this
            }
        } else {
            // return value of constant
            return constant($this->acl_module_default);
        }
    }

    public function getFieldPermValue($perm_name) {
        if(!empty($perm_name)) {
            if(!empty($this->acl_fields_keywords[$perm_name])) {
                return constant($perm_name);     
            } else {
                // bad, we do not know this permission, this script might be outdated! we could return the default but we don't want to as we are unaware of this permission
                die('bad stuff, block here');
                // TODO handle this
            }
        } else {
            // return value of constant
            return constant($this->acl_field_default);
        }
    }

    public function getModulePermName($perm_value) {
        if(!empty($perm_value)) {
            foreach($this->acl_modules_keywords as $name) {
                if(constant($name) == $perm_value) {
                    return $name;
                }
            }
        }
        // return name of constant
        return $this->acl_module_default;
    }

    public function getFieldPermName($perm_value) {
        if(!empty($perm_value)) {
            foreach($this->acl_fields_keywords as $name) {
                if(constant($name) == $perm_value) {
                    return $name;
                }
            }
        }
        // return name of constant
        return $this->acl_field_default;
    }
}
