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

class SystemDataReportsImport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataReports();
    }

    protected function datacli() {
        return new SystemDataCli();
    }

    protected function configure() {
        $this
            ->setName('systemdata:import:reports')
            ->setDescription('Import Reports from JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Source path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        if($this->datacli()->checkJsonFile($path)) {
            $data = $this->datacli()->getData($path);
            $res = $this->data()->saveReportsArray($data['reports']);
            if(!empty($res['errors'])) {
                foreach($res['errors'] as $message) {
                    $output->writeln($message);
                }
            }
            $output->writeln('Reports imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created.');
        } else {
            $output->writeln($path.' does not exist, aborting.');
        }
    }
}
