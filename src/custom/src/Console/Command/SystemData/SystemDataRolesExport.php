<?php

// Enrico Simonetti
// 2017-01-12

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataRoles;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataRolesExport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataRoles();
    }

    protected function datacli() {
        return new SystemDataCli();
    }

    protected function configure() {
        $this
            ->setName('systemdata:export:roles')
            ->setDescription('Export Roles into JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Destination path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        $data = $this->data()->getRoles();
        $file = $this->datacli()->checkPath($path).'roles.json';
        $this->datacli()->putData($file, array('roles' => $data));
        $output->writeln(count($data).' Role(s) exported into '.$file);
    }
}
