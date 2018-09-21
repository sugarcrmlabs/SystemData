# Changelog

## [v0.4]
- Added module packager functionality
- Added support for 8.0.x with the changes in dashboards
- Fixed a Team bug for Reports
- Modified file format (not backward compatible). This can eventually allow in the future to have a single export file
- Updated tests accordingly

## [v0.3]
- Modified all SQL queries to leverage prepared statements
- Fixed bug on Teams that would not return the import record count
- Fixed a number of bugs on Users and refactored the way it works so that default Teams work correctly
- Added some static testing to make the whole project a little more predictable and repeatable

## [v0.2]
- Added AWF (Advanced Workflows) import and export functionality
- Modified Users import/export to handle deleted records

## [v0.1]
- Initial release
