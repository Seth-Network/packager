<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Task to install packager module
 *
 * Syntax:
 *  ./minion packager:install [--db=DATABASE_GROUP] [--prefix=TABLE_PREFIX]
 *
 * Available options are
 *  - db: Database group to be used for the packager module, (default: null (which will use your default database connection))
 *  - prefix: Table prefix for packager (default: '')
 *
 * Examples:
 *  ./minion packager:install --db=mysql --prefix=packager_
 *
 * @package    packager
 * @category   Helpers
 * @author     eth4n
 * @copyright  (c) 2009-2017 eth4n
 * @license    http://seth-network.de/license
 */
class Task_Packager_Install extends Packager_Task
{
    const USER_TBL = 'packager_users';
    const CAPACITY_TBL = 'packager_capacities';
    const DOWNLOAD_TBL = 'packager_downloads';

    protected $_options = array(
        'db' => null,
        'prefix' => ''
    );


    protected function _execute(array $params)
    {
        $n = self::NL;
        $prefix = $params['prefix'];
        $db_group = $params['db'];
        $format = "%-50s";
        echo sprintf($format, "# Checking requirements... ");

        // Check if database and ORM module exists
        if (!class_exists('Database')) {
            echo Minion_CLI::color("Failed" . $n . $n, "red");
            return $this->error("Class 'Database' is required but missing! Did you enable the database module?");
        } else if (!class_exists('ORM')) {
            echo Minion_CLI::color("Failed" . $n . $n, "red");
            return $this->error("Class 'ORM' is required but missing! Did you enable the ORM module?");
        }

        // Check if database group exists
        if ($params['db'] != '' && Kohana::$config->load('database')->get($db_group, null) === null) {
            echo Minion_CLI::color("Failed" . $n . $n, "red");
            return $this->error("Database group '" . $db_group . "' does not exists");
        }
        echo Minion_CLI::color("Ok" . $n, "green");

        echo sprintf($format, "# Checking existing tables... ");
        $db = Database::instance($db_group);
        // Check if database does contain tables
        $tables = $db->list_tables();
        foreach (array($prefix . self::USER_TBL,
                     $prefix . self::CAPACITY_TBL,
                     $prefix . self::DOWNLOAD_TBL) as $tbl) {
            if (array_search($tbl, $tables) !== false) {
                echo Minion_CLI::color("Failed" . $n . $n, "red");
                return $this->error("Table '" . $tbl . "' already exists in database. Please clean any previous installation or select a different table prefix (use --prefix).");
            }
        }
        echo Minion_CLI::color("Ok" . $n, "green");

        echo sprintf($format, "# Preparing SQL script... ");
        // load install.sql
        $sql = Kohana::find_file('assets/sql', 'install', 'sql');
        if ($sql == false) {
            echo Minion_CLI::color("Failed" . $n . $n, "red");
            return $this->error("Missing required installation file 'assets/sql/install.sql'.");
        }

        $sql_content = file_get_contents($sql);
        $sql_content = str_replace('$prefix_', $prefix, $sql_content);
        echo Minion_CLI::color("Ok" . $n, "green");

        echo sprintf($format, "# Executing SQL script... ");
        foreach (explode(';', $sql_content) as $sql) { // Kohana MySQLi driver does not support multi queries which is why we need to split the queries:[
            if (trim($sql) != '') {
                $db->query(Database::UPDATE, $sql);
            }
        }
        echo Minion_CLI::color("Ok" . $n, "green");

        echo sprintf($format, "# Writing configuration... ");
        $cfg = Kohana::$config->load(Controller_Packager::CFG);
        $cfg->set('db', $db_group);
        $cfg->set('tbl_user', $prefix . self::USER_TBL);
        $cfg->set('tbl_capacity', $prefix . self::CAPACITY_TBL);
        $cfg->set('tbl_download', $prefix . self::DOWNLOAD_TBL);
        echo Minion_CLI::color("Ok" . $n, "green");

        echo $n . "Installation finished successfully." . $n;
    }


}