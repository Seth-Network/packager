<?php defined('SYSPATH') or die('No direct script access.');

class Model_Packager_User extends ORM
{

    protected $_has_many = array('capacities' => array(
        'model' => 'Packager_Capacity',
        'foreign_key' => 'user_id',
    ));


    protected function _initialize()
    {
        $cfg = Kohana::$config->load(Controller_Packager::CFG);
        $this->_table_name = $cfg->get('tbl_user');
        $this->_db_group = $cfg->get('db');
        return parent::_initialize();
    }
}