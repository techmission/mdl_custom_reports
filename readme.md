# Moodle Custom Reports (mdl_custom_reports)

This is a set of custom reports for Moodle.

## Download

This project's canonical version is available via [Github](https://github.com/techmission/mdl_custom_reports)

## Release Status & Bug Reporting

This code is currently under development. Some scripts work; others do not work yet.

## Prerequisites

* A Drupal installation above the root of this directory (version <7). 
* A Moodle database in which to install the tables in the script's SQL file
* Courses with assignments, and users enrolled in courses (or most of the queries will return no results)

## Installation

1. Clone the repository from Github, and move into a subdirectory of a Drupal installation. Make all files readable by the Web server.
2. Add a connection string to the Drupal settings.php file for Moodle database. Ensure permissions are correct.
3. Run the SQL install script from the command line.

## Configuration

None.

## Usage

1. Run the script from the command line, or web browser, depending on the script.
2. Some of the scripts are intended to produce output to a web browser. 
   Other scripts are intended are intended to build tables that you can then query in MySQL.

## Known Issues

This code is currently under development; many issues are unknown.

## Further Development

More scripts will be added, and bugs will be fixed.

If someone else wishes to fork to make them run off the Moodle database engine code alone, that would be great.

## Licensing & Collaboration

This project is licensed under the [GNU GPL v2](http://www.gnu.org/licenses/gpl-2.0.html).

You are free to redistribute and modify as you wish, as long as you maintain a readme file with the original credit (including email), as below.

## Credit & Contact

Evan Donovan, on behalf of [TechMission](http://www.techmission.org) and [City Vision College](http://www.cityvision.edu)
Email: firstname at techmission dot org

You may contact him with any questions. Bug reports and feature requests should be done via Github.