<?php defined('SYSPATH') or die('No direct script access.');

class Model_Packager_Download extends ORM
{

    protected $_belongs_to = array('capacity' => array(
        'model' => 'Packager_Capacity',));


    protected function _initialize()
    {
        $cfg = Kohana::$config->load(Controller_Packager::CFG);
        $this->_table_name = $cfg->get('tbl_download');
        $this->_db_group = $cfg->get('db');
        return parent::_initialize();
    }
}