<?php

// Enrico Simonetti
// 2017-01-13

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class SystemDataTeamsMembershipImport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataUsers();
    }

    protected function configure() {
        $this
            ->setName('systemdata:import:teamsmembership')
            ->setDescription('Import Teams Membership from JSON data file. It does NOT import Teams or Users')
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
            $res = $this->data()->saveTeamsMembership($data);
            $output->writeln('Teams Membership imported! '.count($res['processed']).' Team(s) processed, '.(count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])).' Membership(s) processed. '.count($res['skipped']).' Team(s) skipped, '.(count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped'])).' Membership(s) skipped');
        } else {
            $output->writeln($path.' does not exist, aborting.');
        }
    }
}
