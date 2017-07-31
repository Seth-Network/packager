<?php defined('SYSPATH') or die('No direct script access.');

class Model_Packager_Capacity extends ORM
{

    protected $_belongs_to = array('user' => array(
        'model' => 'Packager_User',));

    protected $_has_many = array('downloads' => array(
        'model' => 'Packager_Download',
        'foreign_key' => 'capacity_id',
    ));

    /**
     * Score property is not stored in the database but used when trying to match the capacity against a specific package.
     * The more specific the capacity's data (package and version) is, the higher the score. Wildcards will reduce the
     * score.
     *
     * @var int
     */
    protected $score = 0;

    protected function _initialize()
    {
        $cfg = Kohana::$config->load(Controller_Packager::CFG);
        $this->_table_name = $cfg->get('tbl_capacity');
        $this->_db_group = $cfg->get('db');
        return parent::_initialize();
    }

    /**
     * Sets and gets the capacity's score
     *
     * @param int $value
     * @return $this|int
     */
    public function score($value = NULL)
    {
        if ($value === null) {
            return $this->score;
        }
        $this->score = $value;
        return $this;
    }
}