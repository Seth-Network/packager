<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Task to create and search the packager's index
 *
 * Syntax:
 *  ./minion packager:index [--create] [--search PACKAGE]
 *
 * Examples:
 *  ./minion packager:index --create
 *  ./minion packager:index --search seth-network
 *
 * @package    packager
 * @category   Helpers
 * @author     eth4n
 * @copyright  (c) 2009-2017 eth4n
 * @license    http://seth-network.de/license
 */
class Task_Packager_Index extends Packager_Task
{
    protected $_options = array(
        'create' => false,
        'search' => false,
    );

    protected function _execute(array $params)
    {
        $n = self::NL;
        $t = self::TAB;
        $index = Packager_Index::factory();

        if ($params['create'] === null) {
            try {
                $f = "%-50s";
                echo sprintf($f, "Creating index...");
                $index->renew();
                $count = $index->count_packages();
                echo Minion_CLI::color("Ok" . $n, "green");

                echo $n . "Indexed " . $count . " packages successfully. Use following command to search the index: " . $n;
                echo $n . $t . "./minion packager:index --search PACKAGE" . $n;
            } catch (Kohana_Exception $e) {
                echo Minion_CLI::color("Failed" . $n . $n, "red");
                return $this->error($e->getMessage());
            }
        } else if ($params['search'] != false && $params['search'] != '') {
            $this->print_packages_table($index->find_by_name($params['search']));
        } else {
            return $this->error("Unknown parameter set. Use --help to display the task's help text");
        }
    }

    protected function print_packages_table(array $packages)
    {
        $rows = array();
        $basename = basename(Kohana::$config->load(Controller_Packager::CFG)->get("satis_archive"));
        foreach ($packages as $package) {
            $rows[] = array(
                'name' => $package->name(),
                'version' => $package->version(),
                'file' => str_replace($basename . '/' . $package->name() . '/', '', $package->uri())
            );
        }

        echo "\n";
        $this->print_table(
            array(
                'name' => 'Package',
                'version' => 'Version',
                'file' => 'File'),
            $rows,
            " ");
    }
}