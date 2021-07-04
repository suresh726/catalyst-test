<?php

function validOptions($parsed_options, $key)
{
  $valid = false;
  $key = is_array($key) ? $key : [$key];
  foreach ($key as $k => $v) {
    if(array_key_exists($v, $parsed_options) && $parsed_options[$v]) {
      $valid = true;
    }
  }
  return $valid;
}

function showHelp()
{
  return 
"Imports users data to the database
Usage:  php user_upload.php -h<db_hostname> -u<db_username> -p<db_password> --file=<data_filename> 
  php user_upload.php -h<db_hostname> -u<db_username> -p<db_password> --create_table
  php user_upload.php --file=<data_filename> --dry_run
  php user_upload.php --help
  -u  MySQL username
  -p  MySQL password
  -h  MySQL Hostname 
  --file [csv file name]      Name of the CSV to be parsed
  --create_table              Creates the table in the database
  --dry_run                   Runs the script without adding records in the database
  --help                      Displays the details of the script
    \n";
}

function runSQLQuery($connection, $sql, $error_code)
{
  if (!$connection->query($sql) === TRUE) {
    echo $error_messages[$error_code]."\n\n";
    die();
  }
}
  $error_messages = include('error_messages.php');
  $short_options = 'u:h:p:';
  $long_options = ['file:', 'dry_run', 'create_table', 'help'];

  $parsed_options = getopt($short_options, $long_options);

  if (count($parsed_options) === 0) {
    echo $error_messages['NO_ARGUMENTS']."\n";
    echo showHelp();
    die();
  }

  foreach ($parsed_options as $opt_key => $opt_val) {
    switch($opt_key) {
      case 'dry_run':
        // only required params are file
        if(!validOptions($parsed_options, 'file')) {
          echo $error_messages['FILENAME_REQUIRED']."\n\n";
          die();
        } else {
          // validate filename
          // reaad the csv file
          // validate the data
          // outputs the message
        }
        break;
      case 'create_table':
        // this commands creates table in the db
        if(!validOptions($parsed_options, ['h', 'u', 'p'])) {
          echo $error_messages['DB_CREDS_REQUIRED']."\n\n";
          die();
        }
        $hostname = $parsed_options['h'];
        $username = $parsed_options['u'];
        $password = $parsed_options['p'];
        $database_name = 'catalyst_test';
        $table_name = 'users';

        // creating a db connection with supplied credentials
        $connection = new mysqli($hostname, $username, $password);

        if ($connection->connect_error) {
          echo $connection->connect_error. "\n";
          die();
        }

        $create_db_sql = "CREATE DATABASE IF NOT EXISTS $database_name;";
        $select_db_sql = "use $database_name";
        $create_table_sql = "CREATE TABLE IF NOT EXISTS $table_name (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,  name varchar(100), surname varchar(100), email varchar(100) NOT NULL, UNIQUE KEY unique_email(email) );";

        runSQLQuery($connection, $create_db_sql, 'DB_CREATION_FAILED');
        runSQLQuery($connection, $select_db_sql, 'DB_NOT_FOUND');
        runSQLQuery($connection, $create_table_sql, 'TABLE_CREATION_FAILED');

        echo ucfirst($table_name)." table is successfully created\n";

        //closing db connection
        $connection->close();
    }
  }
?>