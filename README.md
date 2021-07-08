# Technical Test Solution
The repository contains the necessary code, files required to solve the technical test sent by __Catalyst IT Australia__

## Directory Structure
* logic_test.php
* README.md
* user_upload.php
* users.csv

## Dependency
This solution does not need any extra packages to run. 
However, user_upload.php will not run on windows as script uses [getopt](https://www.php.net/manual/en/function.getopt.php) function.

## Usage
`php user_upload.php -h<db_hostname> -u<db_username> -p<db_password> --file=<data_filename>`

`php user_upload.php -h<db_hostname> -u<db_username> -p<db_password> --create_table`

`php user_upload.php --file=<data_filename> --dry_run`

`php user_upload.php --help`

`php logic_test.php`

## Author
Created by Suresh Lamichhane @suresh726


