<?php

// Enrico Simonetti
// 2016-12-22

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataUsersImport extends Command implements InstanceModeInterface
{
    protected function data()
    {
        return new SystemDataCli();
    }

    protected function configure()
    {
        $this
            ->setName('systemdata:import:users')
            ->setDescription('Import Users from JSON data file')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Source path for the JSON data file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        if ($this->data()->checkJsonFile($path)) {
            $data = $this->data()->getData($path);
            $res = $this->data()->saveToObject('users', $data);
            if (!empty($res)) {
                foreach ($res as $message) {
                    $output->writeln($message);
                }
            }
        } else {
            $output->writeln(
                sprintf(
                    translate('LBL_SYSTEMDATA_MSG_ERROR_PATH'),
                    $path
                )
            );
        }
    }
}
