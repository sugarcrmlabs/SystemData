<?php

// Enrico Simonetti
// 2017-03-02

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SystemDataAWFImport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataAWF();
    }

    protected function configure() {
        $this
            ->setName('systemdata:import:awf')
            ->setDescription('Import AWF from JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Source path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        if($this->data()->checkJsonFile($path)) {
            $data = $this->data()->getData($path);
            $res = $this->data()->saveAWFArrays($data);
            if(!empty($res['errors'])) {
                foreach($res['errors'] as $message) {
                    $output->writeln($message);
                }
            }
            $output->writeln('AWF(s) imported! '.count($res['update']).' record(s) updated, '.count($res['create']).' record(s) created.');
        } else {
            $output->writeln($path.' does not exist, aborting.');
        }
    }
}
