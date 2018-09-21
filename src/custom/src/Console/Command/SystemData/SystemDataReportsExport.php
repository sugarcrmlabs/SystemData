<?php

// Enrico Simonetti
// 2017-01-24

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataReports;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataReportsExport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataReports();
    }

    protected function datacli() {
        return new SystemDataCli();
    }

    protected function configure() {
        $this
            ->setName('systemdata:export:reports')
            ->setDescription('Export Reports into JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Destination path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        $data = $this->data()->getReports();
        $file = $this->datacli()->checkPath($path).'reports.json';
        $this->datacli()->putData($file, array('reports' => $data));
        $output->writeln(count($data).' Report(s) exported into '.$file);
    }
}
