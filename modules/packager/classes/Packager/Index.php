<?php defined('SYSPATH') or die('No direct script access.');

class Packager_Index
{
    const CACHE_KEY = 'packager_index';
    const ERR_CONFIG = 1;

    /**
     * Factory method to create a new index object. Index's data is loaded lazy on first
     * access.
     *
     * @return Packager_Index
     */
    public static function factory()
    {
        return new Packager_Index;
    }

    /**
     * Index data with key packages, index_uri, index_package
     *
     * @var stdClass
     */
    protected $data = null;

    /**
     * Removes the existing index. After the first access of the index's data, a new index will
     * be created.
     *
     * @return void
     */
    public function renew()
    {
        $this->data = null;

        $cache = Cache::instance();
        $cache->delete(self::CACHE_KEY);
    }

    /**
     * Searches the index for given $uri and returns all packages where the given uri is contained.
     *
     * @param String $uri
     * @return Array(Model_Packager_Package)
     */
    public function find_by_uri($uri)
    {
        $hashs = array_filter($this->data()->index_uri, function ($hash, $key) use ($uri) {
            return strpos($key, strtolower($uri)) !== false;
        }, ARRAY_FILTER_USE_BOTH);

        return array_filter($this->data()->packages, function ($package, $hash) use ($hashs) {
            return array_search($hash, $hashs);
        }, ARRAY_FILTER_USE_BOTH);
    }


    /**
     * Searches the index for given package name and returns all packages where the given string is contained.
     *
     * @param String $name
     * @return Array(Model_Packager_Package)
     */
    public function find_by_name($name)
    {
        $hashs = array_filter($this->data()->index_package, function ($hash, $key) use ($name) {
            return strpos($key, strtolower($name)) !== false;
        }, ARRAY_FILTER_USE_BOTH);

        return array_filter($this->data()->packages, function ($package, $hash) use ($hashs) {
            return array_search($hash, $hashs);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns the number of packages within the index.
     *
     * @return int
     */
    public function count_packages()
    {
        return count($this->packages());
    }

    /**
     * Returns all package object within the index.
     *
     * @return Array(Model_Packager_Package)
     */
    public function packages()
    {
        return $this->data()->packages;
    }

    /**
     * Returns the raw index data. If the index data is not loaded yet, it will be loaded from the cache or
     * newly generated.
     *
     * @return stdClass
     */
    protected function data()
    {
        if ($this->data === null) {
            $this->data = $this->get_data_from_cache();
        }
        return $this->data;
    }

    /**
     * Returns the raw index data from cache or recreates the index data and stores it to the cache.
     *
     * @return stdClass
     */
    protected function get_data_from_cache()
    {
        $cache = Cache::instance();

        if (($data = $cache->get(self::CACHE_KEY)) !== null) {

            return $data;
        }

        // create index
        $data = $this->create_index();
        $cache->set(self::CACHE_KEY, $data);
        return $data;
    }

    /**
     * Creates a new index structure and returns the index. The returned class will hold
     * keys:
     *  'packages' => Array( String => Model_Packager_Package)
     *  'index_package' => Array(String => String)
     *  'index_uri' => Array(String => String)
     *
     * The packages array's key is the packages hash which is also used in the index keys.
     *
     * @return stdClass
     */
    protected function create_index()
    {
        $cfg = Kohana::$config->load(Controller_Packager::CFG);

        if ($cfg->get('satis_output') === null || $cfg->get('satis_output') == '') {
            throw new Kohana_Exception("No Satis output directory configured.", array(), self::ERR_CONFIG);
        }
        $dir = rtrim($cfg->get('satis_output'), "/\\") . '/';
        if (!file_exists($dir) || !is_dir($dir)) {
            throw new Kohana_Exception("Configured Satis output directory does not exists or is not a directory: :dir", array(':dir' => $dir), self::ERR_CONFIG);
        }
        if (!file_exists($dir . 'packages.json')) {
            throw new Kohana_Exception("Missing file in Satis output directory: :file", array(':file' => $dir . 'packages.json'), self::ERR_CONFIG);
        }

        $packages = $this->get_packages($dir . 'packages.json');

        $data = new stdClass();
        $data->packages = $packages;
        $data->index_package = array();
        $data->index_uri = array();
        foreach ($packages as $hash => $package) {
            $data->index_uri[strtolower($package->uri())] = $hash;
            $data->index_package[strtolower($package->name())] = $hash;
        }

        return $data;
    }

    /**
     * Reads a composer json file (e.g. packages.json) and returns all included packages. Method will follow includes of
     * further json files.
     * Return packages will use the package's sha hash as a key in the resulting array.
     *
     * @param String $file
     * @return Array(String => Model_Packager_Package)
     */
    private function get_packages($file)
    {
        $cfg = Kohana::$config->load(Controller_Packager::CFG);
        $purge_source = $cfg->get('purge_source', true);
        $purge_support = $cfg->get('purge_support', true);

        $basename_dist = basename($cfg->get("satis_archive"));
        $dir = rtrim(dirname($file), "/\\") . '/';
        $json = json_decode(file_get_contents($file));
        $packages = array();
        if (isset($json->packages)) {
            foreach ($json->packages as $name => $data) {
                foreach ($data as $version => $versioned_data) {
                    if (isset($versioned_data->dist) && isset($versioned_data->dist->url)) {
                        list($vendor, $package) = explode('/', $name, 2);
                        $p = new Model_Packager_Package();
                        $p->vendor($vendor);
                        $p->package($package);
                        $p->version($version);

                        $url = $versioned_data->dist->url;
                        $p->uri(substr($url, strpos($url, $basename_dist . '/' . $name)));

                        $sha = sha1($name . $version);
                        $packages[$sha] = $p;
                    }


                    if ( $purge_source && isset($versioned_data->source)) {
                        unset($versioned_data->source);
                    }

                    if ( $purge_support && isset($versioned_data->support)) {
                        unset($versioned_data->support);
                    }
                }
            }

            // write back if requried
            if ( $purge_source || $purge_support ) {
                file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
        if (isset($json->includes)) {
            foreach ($json->includes as $file => $hash) {
                $packages = array_merge($packages, $this->get_packages($dir . $file));
            }
        }
        return $packages;
    }
}