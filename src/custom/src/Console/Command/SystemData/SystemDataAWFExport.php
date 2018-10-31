<?php

// Enrico Simonetti
// 2017-03-02

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Sugarcrm\Sugarcrm\custom\systemdata\SystemDataCli;

class SystemDataAWFExport extends Command implements InstanceModeInterface
{
    protected function data()
    {
        return new SystemDataCli();
    }

    protected function configure()
    {
        $this
            ->setName('systemdata:export:awf')
            ->setDescription('Export AWF into JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = $this->data()->getFromObject('awf');
        $output->writeln($this->data()->formatOutputData($data));
    }
}
