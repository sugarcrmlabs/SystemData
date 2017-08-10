# Sugar Labs CLI System Data

CLI System Data is a command line tool built to simplify some of the data transferring tasks between environments of the same system.<br />
The System Data tool helps you export and import some of the data available in Sugar using JSON data files.

## Requirements
It requires Sugar version 7.9 or above, with the new CLI framework and with prepared statements. The latest release has been built on 7.9.1.0<br />
It requires to be able to run the Sugar CLI framework.<br />
It requires that the source and destinations systems are exactly the same in terms of code and customizations (including if changes are completed via Studio/Module Builder).

## Features

Can export and import from one instance to another:
- Teams
- Roles
- Users
- Teams Membership
- Roles Membership
- Reports
- AWF (Advanced Workflows)

It re-stores deleted records if necessary/found.<br />
It deletes records if flagged accordingly on the JSON file.<br />
It updates and creates records aligning them to the JSON files provided as input to the script.<br /><br />

It does NOT add fields/customisations for you. The assumption is that you already have the same exact code deployed on multiple instances, and you want to re-populate some of the data that lets you use a system.<br />
It does NOT remove records that are on an instance, and that are not present on your JSON file(s).<br />
It does NOT remove Users from Teams or Roles, it only adds Users to them.<br />
It does NOT copy the user's pictures for you. You will have to transfer across the upload folder's content between systems.

## In more depth
### Features

#### Teams
Only newly created Teams are transferred, no private or global Teams are exported

#### Roles
It exports both module level and field level. It does "smart update" of all field level security, aligning completely a Role to its newly deployed version.<br />
It exports data from the database, converting actions/permissions into human readable format (eg: ACL_ALLOW_NORMAL, ACL_ALLOW_ENABLED, ACL_ALLOW_DEFAULT, ACL_ALLOW_OWNER and so on). This can give the flexibility of versioning Roles files and it is possible and really simple to update manually few elements within the JSON structure, to update the matching Roles accordingly.

#### Users
It does "smart" insert of users with reporting structure, keeping all users that report to someone at last. It assumes all users it depends on, are either part of the JSON file or inside the database already.<br />
It includes Teams membership and Roles membership ONLY for explicit assignment. It does not remove users from other explicit/implicit Teams membership or Role membeship.

#### Reports
It exports Reports, including all Teams and Team Sets used on the records. Now from 7.8 onwards there is an additional Team Set field for Team based ACL: acl_team_set_id and the script is already compatible with it.<br />
In addition to export the Report records, it completes an export of the full list of Teams that comprise the related Team Sets. It also completes an export of the full list of Users that have a Private Team part of the Team Set (as private Teams are not exported by the tool, so that it can find the newly matching private Team for the same Users).

#### AWF
It exports AWF records, including business rules and email templates.<br />
Some AWF records required Teams and Team Sets. Similarly to the Reports export, the tool keeps track of what Teams and/or Users are related to the record. 

#### Importing of Data

Please note that the order of import matters and it is really important.

The order should be:
- Teams
- Roles
- Users
- Teams Membership
- Roles Membership
- Reports
- AWF

This tool is only compatible with its own export files.

## Installation
* Download the latest .zip package here: https://github.com/sugarcrmlabs/SystemData/releases/latest
* Load the package in your target instance using Module Loader
* Make sure the file located on `<sugar folder>/bin/sugarcrm` is executable

## Use

### Export
`sudo -u www-data ./bin/sugarcrm systemdata:export:teams /tmp`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:export:roles /tmp`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:export:users /tmp`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:export:reports /tmp`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:export:awf /tmp`<br />


### Import
`sudo -u www-data ./bin/sugarcrm systemdata:import:teams /tmp/teams.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:roles /tmp/roles.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:users /tmp/users.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:teamsmembership /tmp/users.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:rolesmembership /tmp/users.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:reports /tmp/reports.json`<br />
`sudo -u www-data ./bin/sugarcrm systemdata:import:awf /tmp/awf.json`<br />

Note: You would need to use `sudo -u www-data` to execute the CLI framework as your Apache user (if the Apache user is `www-data`).<br />
In Debian the Apache default user is `www-data`, in RedHat/CentOS is `httpd`. Please double check and replace based on your needs.


IMPORTANT: Make sure to keep the users.json file safe. User's passwords (even if hashed) are available on that file.

## Testing
TESTING IS DISTUPTIVE AND WILL WIPE YOUR DATABASE.
Do not test the script with its own tools on any production like environment. To run the test command you actually have to type as agreement the argument `i-agree-to-completely-delete-my-database`

## Contributing
Everyone is welcome to contribute to this project! If you make a contribution, then the [Contributor Terms](CONTRIBUTOR_TERMS.pdf) apply to your submission.

Please check out our [Contribution Guidelines](CONTRIBUTING.md) for helpful hints and tips that will make it easier for us to accept your pull requests.


## Changelog

Changelog is available [here](CHANGELOG.md)

-----
Copyright (c) 2017 SugarCRM Inc. Licensed by SugarCRM under the Apache 2.0 license.
