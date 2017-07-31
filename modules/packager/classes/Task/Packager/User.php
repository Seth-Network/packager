<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Task to manage packager's user entries. Usernames are unique and case sensitive.
 *
 * Syntax:
 *  ./minion packager:user [--create] [--enable] [--disable] [--rename=NEW_NAME] [--description="Description"] [--delete] [--list] [USER_NAME]
 *
 * Examples:
 *  ./minion packager:user --create --description="New User" --enable username
 *  ./minion packager:user --disable username
 *  ./minion packager:user --rename=username2 username
 *  ./minion packager:user --list
 *  ./minion packager:user --list username
 *  ./minion packager:user --delete username2
 *
 * @package    packager
 * @category   Helpers
 * @author     eth4n
 * @copyright  (c) 2009-2017 eth4n
 * @license    http://seth-network.de/license
 */
class Task_Packager_User extends Packager_Task
{

    protected $_options = array(
        'create' => false,
        'description' => false,
        'rename' => false,
        'enable' => false,
        'disable' => false,
        'delete' => false,
        'list' => false
    );


    protected function _execute(array $params)
    {
        $username = isset($params[1]) ? $params[1] : null;

        if ($params['create'] === null) {
            $this->create_user($username, $params['description'], ($params['enable'] === null));
        } else if ($username !== null
            && ($params['enable'] === null || $params['disable'] === null ||
                $params['rename'] != false || $params['description'] != false)
        ) {
            $this->update_user($username, $params['description'], ($params['enable'] === null ? true : ($params['disable'] === null ? false : null)), $params['rename']);
        } else if ($params['delete'] === null) {
            $this->delete_user($username);
        } else {
            $this->list_user($username);
        }
    }


    public function list_user($search = null)
    {
        $model = ORM::factory('Packager_User');
        if ($search !== null) {
            $model->where('name', 'LIKE', '%' . trim($search) . '%');
        }
        $models = $model->order_by('name', 'ASC')->find_all()->as_array();


        $users = array();
        foreach ($models as $model) {
            $user = $model->as_array();
            $user['active'] = $user['active'] ? 'Yes' : 'No';
            $users[] = $user;
        }

        echo self::NL;
        $this->print_table(array('id' => '#', 'name' => 'Name', 'active' => 'Enabled', 'description' => 'Description'), $users, " ");
        return;
    }

    public function delete_user($username)
    {
        $model = $this->find_user($username);
        if ($model === null) {
            return $this->error('Unknown user "' . trim($username) . '"');
        }

        $name = $model->name;
        $id = $model->id;
        $model->delete();

        echo "User " . $name . " (" . $id . ") deleted successfully." . self::NL;
    }

    public function find_user($name_or_id)
    {
        $model = ORM::factory('Packager_User');
        if (is_numeric($name_or_id)) {
            $model->where('id', '=', $name_or_id);
        } else {
            $model->where('name', '=', trim($name_or_id));
        }
        $model = $model->find();
        if ($model === null || !$model->loaded()) {
            return null;
        }
        return $model;
    }

    public function update_user($username, $description = false, $enabled = null, $new_name = false)
    {
        $model = $this->find_user($username);
        if ($model === null) {
            return $this->error('Unknown user "' . trim($username) . '"');
        }

        if ($description != false) {
            $model->description = $description;
        }
        if ($enabled !== null) {
            $model->active = $enabled ? 1 : 0;
        }
        if ($new_name != false) {
            $model->name = trim($new_name);
        }
        $model->save();


        echo "User " . $model->name . " (" . $model->id . ") updated successfully." . self::NL;
        return true;
    }

    public function create_user($username, $description, $enabled = false)
    {
        if ($username == '' || $username === null) {
            $username = substr(sha1(rand(0, PHP_INT_MAX)), 0, Kohana::$config->load(Controller_Packager::CFG)->get('random_username_size'));
            echo self::NL . "No username provided: Use random string generator to find a username: '" . $username . "'" . self::NL . self::NL;
        }

        $model = ORM::factory('Packager_User');
        $model->name = trim($username);
        $model->active = $enabled ? 1 : 0;
        $model->description = ($description != false) ? $description : '';

        $model->save();

        echo "User " . $model->name . " (" . $model->pk() . ") created successfully." . self::NL;

        return true;
    }


}