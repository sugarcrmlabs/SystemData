<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SystemDataUsersExport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataUsers();
    }

    protected function configure() {
        $this
            ->setName('systemdata:export:users')
            ->setDescription('Export Users into JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Destination path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        $data = $this->data()->getActiveUsers();
        $file = $this->data()->checkPath($path).'users.json';
        $this->data()->putData($file, $data);
        $output->writeln(count($data).' User(s) exported into '.$file);
    }
}
