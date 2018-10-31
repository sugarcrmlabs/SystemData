<?php

// Enrico Simonetti
// 2017-01-24

namespace Sugarcrm\Sugarcrm\custom\systemdata;

class SystemDataReports extends SystemData
{
    public function getReports()
    {
        $this->enforceAdmin();

        return $this->getRowsFromTable('saved_reports', 'name');
    }

    public function saveReportsArray($reports)
    {
        $this->enforceAdmin();

        return $this->saveBeansArray('Reports', $reports, 'name');
    }
}
