<?php

// Enrico Simonetti
// 2017-01-12

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SystemDataRolesExport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataRoles();
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
        $file = $this->data()->checkPath($path).'roles.json';
        $this->data()->putData($file, $data);
        $output->writeln(count($data).' Role(s) exported into '.$file);
    }
}
