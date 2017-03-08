<?php

$commandregistry = Sugarcrm\Sugarcrm\Console\CommandRegistry\CommandRegistry::getInstance();

// users (includes roles membership and teams membership export)
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataUsersExport()));
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataUsersImport()));

// roles
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataRolesExport()));
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataRolesImport()));

// roles membership import only
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataRolesMembershipImport()));

// teams
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataTeamsExport()));
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataTeamsImport()));

// teams membership import only
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataTeamsMembershipImport()));

// reports (includes team list and user list for private teams)
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataReportsExport()));
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataReportsImport()));

// awf
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataAWFExport()));
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\SystemData\SystemDataAWFImport()));
