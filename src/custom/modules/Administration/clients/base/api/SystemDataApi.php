<?php

// Enrico Simonetti
// enricosimonetti.com
// 2018-10-24

use Sugarcrm\Sugarcrm\custom\systemdata\SystemData;

class SystemDataApi extends AdministrationApi
{
    /**
     * Register endpoints
     * @return array
     */
    public function registerApiRest()
    {
        return array(
            'getSystemDataSections' => array(
                'reqType' => array('GET'),
                'path' => array('Administration', 'SystemData', 'sections', '?'),
                'pathVars' => array('', '', '', 'section'),
                'method' => 'getSystemDataSections',
                'shortHelp' => 'Get Sections and Labels',
                'exceptions' => array(
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ),
            ),
            'handleSystemDataImport' => array(
                'reqType' => array('POST'),
                'path' => array('Administration', 'SystemData', 'import'),
                'pathVars' => array(''),
                'method' => 'handleSystemDataImport',
                'shortHelp' => 'Perform SystemData import, overriding existing data',
                'exceptions' => array(
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ),
            ),
            'handleSystemDataExport' => array(
                'reqType' => array('GET'),
                'path' => array('Administration', 'SystemData', 'export'),
                'pathVars' => array(''),
                'method' => 'handleSystemDataExport',
                'shortHelp' => 'Perform SystemData export',
                'exceptions' => array(
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ),
            ),
        );
    }

    /**
     * getSystemDataSections
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function getSystemDataSections(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();
        $sd = new SystemData();
        return $sd->getUISectionLabels($args['section']);
    }

    /**
     * handleSystemDataImport
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function handleSystemDataImport(ServiceBase $api, array $args)
    {
        global $app_strings;
        $this->ensureAdminUser();

        $output = [];
        $sd = new SystemData();

        if (!empty($args['data']) && !empty($args['modules'])) {

            $allSections = $sd->getUISectionLabels('import');

            $content = $sd->jsonDecode($args['data']);
            if (!empty($content)) {
                foreach ($args['modules'] as $section) {
                    if (in_array($section, array_keys($allSections))) {
                        $output[] = $sd->saveToObject($section, $content);
                    }
                }
            } else {
                $output[] = $app_strings['LBL_SYSTEMDATA_MSG_ERROR_EMPTY'];
            }
        } else {
            $output[] = $app_strings['LBL_SYSTEMDATA_MSG_ERROR_EMPTY'];
        }

        return $output;
    }

    /**
     * handleSystemDataExport
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function handleSystemDataExport(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $sd = new SystemData();
    
        $output = [];
        $allSections = $sd->getUISectionLabels('export');
        foreach ($args['modules'] as $section) {
            if (in_array($section, array_keys($allSections))) {
                $current = $sd->getFromObject($section);
                $output = array_merge_recursive($output, $current);
            }
        }
        return $sd->jsonEncode($output);
    }
}
