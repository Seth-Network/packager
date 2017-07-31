<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Task to manage packager's capacity entries. To modify or deleting a capacity you have to use the capacity's identifier.
 *
 * Syntax:
 *  ./minion packager:capacity [--create=user] [--enable] [--disable] [--package=PACKAGE] [--version=VERSION] [--downloads(=NUMBER)] [--delete] [--list username/package] [id]
 *
 * Actions:
 *  - Create capacity
 *      ./minion packager:capacity --create=[USER] --package=[PACKAGE] (--version=VERSION) (--downloads=[DOWNLOADS]) (--enable)
 *          [USER]      Can either be a user name or user id
 *          [PACKAGE]   Package name of form vendor/package. You can use '*' as wildcard, e.g. to allow all packages of a vendor or all packages with a common prefix
 *          [VERSION]   Semver version string, you can use '*' as wildcard (default: *)
 *          [DOWNLOADS] Integer to limit the downloads, -1 for unlimited (default: -1)
 *
 *  - Edit capacity
 *      ./minion packager:capacity (--downloads=[DOWNLOADS]) (--enable|--disable) (--version=[VERSION]) [CAPACITY]
 *          [CAPACITY]  Identifier of the capacity
 *
 *  - Delete capacity
 *      ./minion packager:capacity --delete [CAPACITY]
 *
 *  - List capacities
 *      ./minion packager:capacity ([SEARCH])
 *          [SEARCH]    Can either be a username or package name; case-insensitive and works with partial strings (default: '')
 *
 *  - List downloads of one or more capacities
 *      ./minion packager:capacity --downloads ([SEARCH])
 *          [SEARCH]    Can either be a capacity identifier, a user or package name; case-insensitive and works with partial strings (default: '')
 *
 * Examples:
 *  ./minion packager:capacity --create=user --package="seth-network/ske" --version=*
 *  ./minion packager:capacity --enable 2
 *  ./minion packager:capacity --downloads=50 2
 *  ./minion packager:capacity --list
 *  ./minion packager:capacity --list username
 *  ./minion packager:capacity --downloads username
 *  ./minion packager:capacity --list package
 *  ./minion packager:capacity --delete 2
 *
 * @package    packager
 * @category   Helpers
 * @author     eth4n
 * @copyright  (c) 2009-2017 eth4n
 * @license    http://seth-network.de/license
 */
class Task_Packager_Capacity extends Packager_Task
{
    /**
     * @var Task_Packager_User
     */
    protected $users;

    protected $_options = array(
        'create' => false,
        'delete' => false,
        'list' => false,
        'package' => false,
        'version' => false,
        'downloads' => false,
        'enable' => false,
        'disable' => false,
    );


    protected function _execute(array $params)
    {
        $this->users = new Task_Packager_User();
        $capacity = isset($params[1]) ? $params[1] : null;

        if ($params['create'] != false) {
            $this->create_capacity($params['create'], $params['package'], $params['version'], $params['downloads'], ($params['enable'] === null));
        } else if ($params['delete'] === null) {
            $this->delete_capacity($capacity);
        } else if ($params['enable'] === null || $params['disable'] === null || $params['version'] != false || ($params['downloads'] != false && $params['downloads'] !== null)) {
            $this->update_capacity($capacity, $params['version'], $params['downloads'], ($params['enable'] === null ? true : ($params['disable'] === null ? false : null)));
        } else if ($params['downloads'] === NULL) {
            $this->list_downloads($capacity);
        } else {
            $this->list_capacity($capacity);
        }
    }

    public function find_capacity($id)
    {
        $model = ORM::factory('Packager_Capacity', $id);

        if ($model === null || !$model->loaded()) {
            return null;
        }
        return $model;
    }

    public function list_capacity($search = null)
    {
        $model = ORM::factory('Packager_Capacity');
        if ($search !== null) {
            $model->join(array(Kohana::$config->load(Controller_Packager::CFG)->get('tbl_user'), 'user'))->on($model->object_name() . '.user_id', '=', 'user.id');
            $model->where('package', 'LIKE', '%' . trim($search) . '%');
            $model->or_where('user.name', 'LIKE', '%' . trim($search) . '%');
        }
        $models = $model->order_by('package', 'ASC')->find_all()->as_array();

        $rows = array();
        foreach ($models as $model) {
            $row = $model->as_array();
            $row['active'] = $row['active'] ? 'Yes' : 'No';
            $row['name'] = $model->user->name;
            $row['open_downloads'] = ($model->open_downloads == -1) ? 'Unlimited' : $model->open_downloads;
            $row['downloads'] = $model->downloads->count_all();
            $rows[] = $row;
        }

        echo "\n";
        $this->print_table(
            array(
                'id' => '#',
                'name' => 'User',
                'package' => 'Package',
                'version' => 'Version',
                'downloads' => 'Downloads',
                'open_downloads' => 'Open Downloads',
                'active' => 'Enabled'),
            $rows,
            " ");
        return;
    }


    public function list_downloads($capacity_or_user = null)
    {
        $model = ORM::factory('Packager_Download');
        if ($capacity_or_user !== null) {
            $model->join(array(Kohana::$config->load(Controller_Packager::CFG)->get('tbl_capacity'), 'capacity'))->on($model->object_name() . '.capacity_id', '=', 'capacity.id');
            $model->join(array(Kohana::$config->load(Controller_Packager::CFG)->get('tbl_user'), 'user'))->on('capacity.user_id', '=', 'user.id');
            $model->where($model->object_name() . '.package', 'LIKE', '%' . trim($capacity_or_user) . '%');
            $model->or_where('user.name', 'LIKE', '%' . trim($capacity_or_user) . '%');
            $model->or_where($model->object_name() . '.capacity_id', '=', $capacity_or_user);
        }
        $models = $model->order_by('date', 'DESC')->find_all()->as_array();

        $rows = array();
        foreach ($models as $model) {
            $row = $model->as_array();
            $row['name'] = $model->capacity->user->name;
            $rows[] = $row;
        }

        echo "\n";
        $this->print_table(
            array(
                'capacity_id' => 'C',
                'name' => 'User',
                'package' => 'Package',
                'version' => 'Version',
                'date' => 'Date',
                'ip' => 'TCP/IP',
                'host' => 'Host'),
            $rows,
            " ");
        return;
    }

    public function delete_capacity($capacity)
    {
        $model = $this->find_capacity($capacity);
        if ($model === null || !$model->loaded()) {
            return $this->error('Unknown capacity with id "' . $capacity . '"');
        }
        $package = $model->package;
        $version = $model->version;
        $user = $model->user;
        $model->delete();

        echo "Capacity for user " . $user->name . " (" . $user->pk() . ") for package " . $package . " (" . $version . ") deleted successfully.";
    }

    public function update_capacity($capacity, $version = false, $downloads = false, $enabled = null)
    {
        $model = $this->find_capacity($capacity);
        if ($model === null || !$model->loaded()) {
            return $this->error('Unknown capacity with id "' . $capacity . '"');
        }

        if ($version != false) {
            $model->version = $version;
        }
        if ($enabled !== null) {
            $model->active = $enabled ? 1 : 0;
        }
        if ($downloads != false && is_numeric($downloads)) {
            $model->open_downloads = $downloads;
        }
        $model->save();

        $user = $model->user;
        echo "Capacity for user " . $user->name . " (" . $user->pk() . ") for package " . $model->package . " (" . $model->version . ") updated successfully." . self::NL;
        return true;
    }

    public function create_capacity($username, $package, $version, $downloads, $enabled = false)
    {
        if ($package == false || trim($package) == '' || $package === null || strpos($package, '/') === false) {
            return $this->error("You have to provide a valid package name of form 'vendor/package'");
        }
        if ($username == '' || $username === null) {
            return $this->error("You have to provide a username");
        }
        $user = $this->users->find_user($username);
        if ($user === null) {
            return $this->error('Unknown user "' . trim($username) . '"');
        }

        $model = ORM::factory('Packager_Capacity');
        $model->user_id = $user->pk();
        $model->package = trim($package);
        $model->version = ($version !== null && $version != false && $version != '') ? trim($version) : '*';
        $model->active = $enabled ? 1 : 0;
        $model->open_downloads = ($downloads !== null && $downloads != false && $downloads != '') ? $downloads : -1;

        $model->save();


        echo "Capacity for user " . $user->name . " (" . $user->pk() . ") for package " . $package . " (" . $model->version . ") created successfully." . self::NL;

        if (!$enabled) {
            echo self::NL . self::NL;
            $this->warn("User capacity created but not enabled! Use following command:");
            echo self::NL . self::TAB . "./minion packager:capacity --enable " . $model->pk() . self::NL;
        }
        return true;
    }

}