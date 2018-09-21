<?php

// Enrico Simonetti
// 2017-01-13

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataUsers;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataTeamsMembershipImport extends Command implements InstanceModeInterface {

    // get common code
    protected function data() {
        return new SystemDataUsers();
    }

    protected function datacli() {
        return new SystemDataCli();
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
        if($this->datacli()->checkJsonFile($path)) {
            $data = $this->datacli()->getData($path);
            $res = $this->data()->saveTeamsMembership($data['users']);
            $output->writeln('Teams Membership imported! '.count($res['processed']).' Team(s) processed, '.(count($res['processed'], COUNT_RECURSIVE) - count($res['processed'])).' Membership(s) processed. '.count($res['skipped']).' Team(s) skipped, '.(count($res['skipped'], COUNT_RECURSIVE) - count($res['skipped'])).' Membership(s) skipped');
        } else {
            $output->writeln($path.' does not exist, aborting.');
        }
    }
}
