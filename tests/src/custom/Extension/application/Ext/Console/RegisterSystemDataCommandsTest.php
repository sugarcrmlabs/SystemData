<?php

$commandregistry = Sugarcrm\Sugarcrm\Console\CommandRegistry\CommandRegistry::getInstance();

$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataTest()));
