<?php

// Enrico Simonetti
// 2017-03-02

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SystemDataAWFExport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataAWF();
    }

    protected function configure() {
        $this
            ->setName('systemdata:export:awf')
            ->setDescription('Export AWF into JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Destination path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $path = $input->getArgument('path');
        $data = $this->data()->getAWF();
        $file = $this->data()->checkPath($path).'awf.json';
        
        $count = 0;
        if(!empty($data)) {
            foreach($data as $key => $val) {
                $count += count($data[$key]);
            }
        }

        $this->data()->putData($file, $data);
        $output->writeln($count.' AWF(s) exported into '.$file);
    }
}
