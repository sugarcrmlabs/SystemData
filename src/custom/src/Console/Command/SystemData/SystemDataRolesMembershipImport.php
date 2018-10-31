<?php

// Enrico Simonetti
// 2017-01-13

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataRolesMembershipImport extends Command implements InstanceModeInterface
{
    protected function data()
    {
        return new SystemDataCli();
    }

    protected function configure()
    {
        $this
            ->setName('systemdata:import:rolesmembership')
            ->setDescription('Import Roles Membership from JSON data file. It does NOT import Roles or Users')
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
            $res = $this->data()->saveToObject('rolesmembership', $data);
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
