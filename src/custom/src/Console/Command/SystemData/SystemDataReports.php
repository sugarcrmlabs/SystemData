<?php

// Enrico Simonetti
// 2017-01-24

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

class SystemDataReports extends SystemData {

    public function getReports() {
        return $this->getRowsFromTable('saved_reports', 'name');
    }

    public function saveReportsArray($reports) {
        return $this->saveBeansArray('Reports', $reports, 'name');
    }
}
