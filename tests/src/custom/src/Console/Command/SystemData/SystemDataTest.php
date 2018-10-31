<?php

// Enrico Simonetti
// 2017-08-03

namespace Sugarcrm\Sugarcrm\custom\Console\Command\SystemData;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\NullOutput;

class SystemDataTest extends Command implements InstanceModeInterface
{
    protected function tester()
    {
        return new SystemDataTester();
    }

    protected function configure()
    {
        $this
            ->setName('systemdata:test')
            ->setDescription('Test System Data')
            ->addArgument(
                'disruptive',
                InputArgument::OPTIONAL,
                'Acknowledge that this command is disruptive by typing i-agree-to-completely-delete-my-database'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $disruptive = $input->getArgument('disruptive');
        if ($disruptive != 'i-agree-to-completely-delete-my-database') {
            $output->writeln('This command will wipe out the content of your database. Please make sure you agree, by adding `i-agree-to-completely-delete-my-database` after the command');
            return;
        }

        $nulloutput = new NullOutput();

        // clear database
        $this->tester()->truncateAllTables();

        // step0
        $this->tester()->executeInitialStep($input, $nulloutput);

        // step1
        $this->tester()->executeStep(1, $input, $nulloutput);
        $test1 = $this->tester()->verifyStep(1, $input, $nulloutput);

        // step2
        $this->tester()->executeStep(2, $input, $nulloutput);
        $test2 = $this->tester()->verifyStep(2, $input, $nulloutput);

        if(!empty($test1)) {
            $output->writeln('Test 1 passed');
        } else {
            $output->writeln('Test 1 FAILED!');
        }

        if(!empty($test2)) {
            $output->writeln('Test 2 passed');
        } else {
            $output->writeln('Test 2 FAILED!');
        }
    }
}
