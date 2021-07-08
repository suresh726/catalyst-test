<?php

class ErrorMessage
{
    private $code;
    private $message;

    public function setError($code, $message = 'Something went wrong')
    {
        $this->code = $code;
        $this->message = $message;
    }

    public function getError()
    {
        return ['code' => $this->code, 'message' => $this->message];
    }

    public function showErrorAndDie()
    {
        echo $this->message . PHP_EOL;
        die();
    }
}

class DbConnection
{
    private const DATABASE_NAME = 'catalyst_test';

    private $hostname;
    private $username;
    private $password;
    private $connection;
    private $error;

    public function __construct(ErrorMessage $error)
    {
        $this->error = $error;
    }

    public function setCredentials($hostname, $username, $password)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect()
    {
        if (!$this->hostname || !$this->username || !$this->password) {
            $this->error->setError('DB_CREDS_MISSING', 'Database credentials are missing');
            return $this->error;
        }
        $this->connection = @new mysqli($this->hostname, $this->username, $this->password, self::DATABASE_NAME);

        if ($this->connection->connect_error) {
            $this->error->setError('DB_CONNECT_FAILED', $this->connection->connect_error);
            return $this->error;
        }
        return $this->connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function runSQLQuery($sql)
    {
        if (!$this->connection) {
            return $this->error->setError('DB_CONNECT_ERROR', 'Error in connecting to the database');
        }
        if ($this->connection->query($sql) != TRUE) {
            return $this->error->setError('QUERY_FAILED', 'Error in running sql');
        }
    }

    public function disconnect()
    {
        $this->connection->close();
    }

}

class UserUpload
{
    public const DATABASE_NAME = 'catalyst_test';
    public const TABLE_NAME = 'users';
    private const SHORT_OPTIONS = 'u:h:p:';
    private const LONG_OPTIONS = ['file:', 'dry_run', 'create_table', 'help'];

    public $error;
    public $db;
    private $filename;
    private $action;

    public function __construct(ErrorMessage $error, DbConnection $db)
    {
        $this->error = $error;
        $this->db = $db;
    }

    /* displays error message and exits the program */

    public function parseCommand()
    {
        $parsed_options = getopt(self::SHORT_OPTIONS, self::LONG_OPTIONS);

        // show an error and exit if there are no arguments
        if (count($parsed_options) === 0) {
            $this->error->setError('NO_ARGUMENTS', 'No arguments were supplied to the script');
            $this->sendOutput();
        }

        // loop through each options to extract the value
        foreach ($parsed_options as $key => $val) {
            switch ($key) {
                case 'h':
                    $this->hostname = $parsed_options['h'];
                    break;
                case 'u':
                    $this->username = $parsed_options['u'];
                    break;
                case 'p':
                    $this->password = $parsed_options['p'];
                    break;
                case 'file':
                    $this->filename = $parsed_options['file'];
                    break;
                case 'dry_run':
                    $this->action = 'dry_run';
                    break;
                case 'create_table':
                    $this->action = 'create_table';
                    break;
                case 'help':
                    $this->action = 'help';
                    break;
                default:
                    $this->action = $this->action ?? 'unknown';
            }
        }

        /* if other than allowed options are set, the command will be invalid
          if no invalid commands are set & none from help, create_table, dry_run exists
          it will add records to db
        */
        if ($this->action === 'unknown') {
            $this->error->setError('INVALID_COMMAND', 'Invalid command');
            $this->sendOutput();
        } else {
            $this->action = $this->action ?? 'process';
        }

        return $parsed_options;
    }


    /* parses the given command and extracts the arguments */

    public function sendOutput()
    {
        $error = $this->error->getError();
        echo $error['message'] . PHP_EOL;
        die();
    }

    /* returns the current action: process, create_table, dry_run, help */

    public function getAction()
    {
        if (!$this->action) {
            $this->error->setError('INVALID_COMMAND', 'Invalid command');
            $this->sendOutput();
        }
        return $this->action;
    }

    /* validates supplied data file  */
    public function validateFile()
    {
        if (!$this->filename) {
            $this->error->setError('FILENAME_REQUIRED', 'Filename is required');
            $this->sendOutput();
        }

        // checks if file with given filename exists
        if (!file_exists($this->filename)) {
            $this->error->setError('INVALID_FILENAME', 'No such file exists');
            $this->sendOutput();
        }

        // check the file extension
        if (pathinfo($this->filename, PATHINFO_EXTENSION) !== 'csv') {
            $this->error->setError('INVALID_FILE_TYPE', 'Invalid filetype');
            $this->sendOutput();
        }

        // opens the file in reading mode
        if (($handle = fopen($this->filename, "r")) != TRUE) {
            $this->error->setError('FILE_ERROR_IN_OPENING', 'Error in opening file');
            $this->sendOutput();
        }
        return $handle;
    }

    /* check if dependent options are missing file in dry_run & process */
    public function isOptionValid($options, $key)
    {
        $valid = false;
        $key = is_array($key) ? $key : [$key];
        foreach ($key as $k => $v) {
            if (!array_key_exists($v, $options)) {
                $valid = false;
                return $valid;
            } else {
                $valid = true;
            }
        }
        return $valid;
    }

    /* Displays help message */
    public function showHelp()
    {
        echo "Imports users data to the database
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
        exit;
    }

}

// execution starts here
$db_error = new ErrorMessage();
$upload_error = new ErrorMessage();
$db = new DbConnection($db_error);
$demo_obj = new UserUpload($upload_error, $db);

$parsed_options = $demo_obj->parseCommand();

if ($demo_obj->getAction() === 'dry_run') {
    echo 'Dry running...' . PHP_EOL;
    $results = dryRunCommand($demo_obj);
    showDryRunResult($results);
} elseif ($demo_obj->getAction() === 'help') {
    $demo_obj->showHelp();
} elseif ($demo_obj->getAction() === 'create_table') {
    echo "Creating " . UserUpload::TABLE_NAME . " table in " . UserUpload::DATABASE_NAME . "..." . PHP_EOL;
    createDbTable($demo_obj, $parsed_options);
    $demo_obj->db->disconnect();
    echo 'Table is created' . PHP_EOL;
} elseif ($demo_obj->getAction() === 'process') {
    // crate table
    $handle = $demo_obj->validateFile();
    echo "Creating " . UserUpload::TABLE_NAME . " table in " . UserUpload::DATABASE_NAME . "..." . PHP_EOL;
    $demo_obj2 = createDbTable($demo_obj, $parsed_options);
    echo 'Table is created' . PHP_EOL;

    // process the file
    $result = dryRunCommand($demo_obj);

    // insert the data
    $db_connection = $demo_obj->db->getConnection();
    $prepare_sql = "INSERT INTO " . UserUpload::TABLE_NAME . " (name, surname, email) VALUES (?, ?, ?)";
    $statement = $db_connection->prepare($prepare_sql);

    // convert into batch insert
    foreach ($result['records'] as $key => $row) {
        $statement->bind_param("sss", $row[0], $row[1], $row[2]);
        $statement->execute();
    }

    // show the result
    showDryRunResult($result);
    echo 'Data is successfully added to database' . PHP_EOL;
    $demo_obj->db->disconnect();
}

function createDbTable($user_upload_obj, $parsed_options)
{
    // Checking if required params h u p for this action are present or not
    if (!$user_upload_obj->isOptionValid($parsed_options, ['h', 'u', 'p'])) {
        $user_upload_obj->error->setError('DB_CREDS_REQUIRED', 'DB credentials are required to create a table');
        $user_upload_obj->sendOutput();
    }

    // creating a db connection with supplied credentials
    $user_upload_obj->db->setCredentials(
        $parsed_options['h'],
        $parsed_options['u'],
        $parsed_options['p']
    );

    // connect to the database
    $result = $user_upload_obj->db->connect();
    if ($result instanceof ErrorMessage) {
        $result->showErrorAndDie();
    }

    // creating db if not present
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS " . UserUpload::DATABASE_NAME . ";" . PHP_EOL;
    $user_upload_obj->db->runSQLQuery($create_db_sql);
    if ($result instanceof ErrorMessage) {
        $result->showErrorAndDie();
    }
    echo 'Database is created' . PHP_EOL;

    // selecting that database;
    $select_db_sql = "use " . UserUpload::DATABASE_NAME . ";" . PHP_EOL;
    $user_upload_obj->db->runSQLQuery($select_db_sql);

    // creating the database if not present
    $create_table_sql = "CREATE TABLE IF NOT EXISTS " . UserUpload::TABLE_NAME . " (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,  name varchar(100), surname varchar(100), email varchar(100) NOT NULL, UNIQUE KEY unique_email(email) );";
    $user_upload_obj->db->runSQLQuery($create_table_sql);
    return $user_upload_obj;
}

function dryRunCommand($user_upload_obj)
{
    $handle = $user_upload_obj->validateFile();

    $records = [];
    $num_of_line = 0;
    $invalid_records = [];
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num_of_line++;

        // skips the header row
        if ($num_of_line === 1) {
            continue;
        }

        // process each row
        $name = ucfirst(strtolower(trim($data[0])));
        $surname = ucfirst(strtolower(trim($data[1])));
        $email = strtolower(trim($data[2]));

        // checks if email address is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid_records[] = $email;
            continue;
        }

        $records[] = [$name, $surname, $email];
    }
    fclose($handle);
    $valid_record_count = count($records);
    $invalid_record_count = count($invalid_records);

    return [
        'valid_record_count' => $valid_record_count,
        'invalid_record_count' => $invalid_record_count,
        'invalid_records' => $invalid_records,
        'records' => $records
    ];
}

function showDryRunResult($result)
{
    echo "Total records: " . ($result['valid_record_count'] + $result['invalid_record_count']) . ",
Valid records: " . $result['valid_record_count'] . ",
Invalid records: " . $result['invalid_record_count'] . ", 
Invalid emails: " . implode(',', $result['invalid_records']) . "\n";
}
