<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Task to manage packager's configuration
 *
 * Syntax:
 *  ./minion packager:config [--output=OUTPUT_DIR] [--archive=ARCHIVE_DIR] [--show]
 *
 * Available actions are
 *  - show: Dumps current configuration
 *  - output: Sets the output directory as defined when calling satis build
 *  - archive: Directory containing all artifacts from satis
 *
 * Examples:
 *  ./minion packager:config --output=APPPATH/satis --archive=APPPATH/satis/dist
 *
 *
 * @package    packager
 * @category   Helpers
 * @author     eth4n
 * @copyright  (c) 2009-2017 eth4n
 * @license    http://seth-network.de/license
 */
class Task_Packager_Config extends Packager_Task
{

    protected $_options = array(
        'output' => '',
        'archive' => '',
        'show' => false,
    );


    protected function _execute(array $params)
    {
        if ($params['show'] === null || ($params['output'] == '' && $params['archive'] == '')) {
            return $this->show_config();
        } else if ($params['output'] != '' || $params['archive'] != '') {
            return $this->write_config($params['output'], $params['archive']);
        }
    }

    protected function write_config($output = '', $archive = '')
    {
        $n = self::NL;
        $cfg = Kohana::$config->load(Controller_Packager::CFG);

        if ($output != '') {
            $output = trim($output, "/\\");
            if (!file_exists($output)) {
                return $this->error('Output "' . $output . '" does not exists.');
            } else if (!is_dir($output)) {
                return $this->error('Output "' . $output . '" is not a valid directory.');
            } else if (!file_exists($output . '/packages.json')) {
                return $this->error('Output directory "' . $output . '" does not contain a packages.json.');
            }
            $cfg->set('satis_output', $output);
            echo "Output directory set to '" . $this->path($output) . "'" . $n;
        }

        if ($archive != '') {
            $archive = trim($archive, "/\\");
            if (!file_exists($archive)) {
                return $this->error('Archive "' . $archive . '" does not exists.');
            } else if (!is_dir($archive)) {
                return $this->error('Archive "' . $archive . '" is not a valid directory.');
            }
            $cfg->set('satis_archive', $archive);
            echo "Archive directory set to '" . $this->path($output) . "'" . $n;
        }
    }

    protected function show_config()
    {
        $n = self::NL;
        $cfg = Kohana::$config->load(Controller_Packager::CFG);

        echo "Packager configuration:" . $n . $n;
        $this->print_table(array('Key', 'Value'), array(
            array('Output directory:', $this->path($cfg->get('satis_output'))),
            array('Archive directory:', $this->path($cfg->get('satis_archive')))
        ), "", false);
    }

}