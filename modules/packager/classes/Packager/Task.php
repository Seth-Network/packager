<?php defined('SYSPATH') or die('No direct script access.');

abstract class Packager_Task extends Minion_Task
{
    const NL = "\n";
    const TAB = "\t";

    /**
     * Prints a nice-looking table with uniform column width using given columns and rows. Columns' key will be used
     * in the rows to access the values. Prefix will be added to all lines (e.g. indention).
     * If $print_header is set to false, the column definition is not printed.
     *
     * @param Array(String)|Array(String=>String) $cols
     * @param Array(Array(String=>String)|Array(Array(ORM) $rows
     * @param String $prefix
     * @param bool $print_header
     * @return void
     */
    protected function print_table(array $cols, array $rows, $prefix = '', $print_header = true)
    {
        $n = self::NL;
        $max_text_length[] = array();
        $max_text_length_sum = 0;
        $format = $prefix;
        foreach ($cols as $k => $v) {
            $max_text_length[$k] = strlen($v);
            foreach ($rows as $values) {
                if (strlen($values[$k]) > $max_text_length[$k]) {
                    $max_text_length[$k] = strlen($values[$k]);
                }
            }
            $max_text_length[$k] += 5;
            $max_text_length_sum += $max_text_length[$k];
            $format .= '%-' . $max_text_length[$k] . 's';
        }
        $format .= $n;

        if ($print_header) {
            $columns = array_values($cols);
            echo sprintf($format, ...$columns);
            echo sprintf($prefix . "%'=" . $max_text_length_sum . "s" . $n, "=");
        }

        foreach ($rows as $values) {
            $data = array();
            foreach ($cols as $k => $v) {
                if (is_array($values)) {
                    $data[] = $values[$k];
                } else if ($values instanceof ORM) {
                    $data[] = $values->$k;
                }
            }
            echo sprintf($format, ...$data);
        }

    }

    /**
     * Prints out an error
     *
     * @param String $msg
     * @param String $new_line
     * @return void
     */
    protected function error($msg, $new_line = self::NL)
    {
        echo Minion_CLI::color("# Error: " . $msg . $new_line, 'red');
        return false;
    }

    /**
     * Prints out a warning
     *
     * @param String $msg
     * @param String $new_line
     * @return void
     */
    protected function warn($msg, $new_line = self::NL)
    {
        echo Minion_CLI::color("# Warning: " . $msg . $new_line, 'yellow');
    }

    /**
     * Returns the given path will uniformed separators and replaced Kohana path constants. If the second parameter
     * is set to false, only DOCROOT constant will be substituted.
     *
     * @param String $path
     * @param bool $substitute_constant
     * @return String
     */
    protected function path($path, $substitute_constant = true)
    {
        if ($path === null) {
            return null;
        }
        $path = str_replace('\\', '/', $path);
        if ($substitute_constant) {
            return str_replace(array(
                str_replace('\\', '/', APPPATH),
                str_replace('\\', '/', MODPATH),
                str_replace('\\', '/', SYSPATH),
                str_replace('\\', '/', DOCROOT)
            ), array(
                'APPPATH/',
                'MODPATH/',
                'SYSPATH/',
                'DOCROOT/'
            ), $path);
        } else {
            return str_replace(str_replace('\\', '/', DOCROOT), 'DOCROOT/', $path);
        }
    }
}