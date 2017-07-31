<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Packager extends Controller
{
    const CFG = 'packager';

    public function action_get()
    {
        // fetch file from parameter
        $file = $this->request->param("file");

        // sanitize file path: remove ../ and ./
        $file = preg_replace('/\w+\/\.\.\//', '', $file);

        $user = $this->get_auth_user();
        if ($user !== null) {
            $cfg = Kohana::$config->load(self::CFG);
            $local_file = trim($cfg->get('satis_output'), "/\\") . '/' . $file;
            if (!file_exists($local_file)) {
                $local_file = trim($cfg->get('satis_archive'), "/\\") . '/' . $file;
            }

            if (!file_exists($local_file)) {
                throw new HTTP_Exception_404("File " . $file . " does not exists.");
            }

            // Parse Info / Get Extension
            $path_parts = pathinfo($local_file);
            $ext = strtolower($path_parts["extension"]);

            // package to download
            $package = null;

            // force-load index: this will ensure, that the index is at least created and any unwanted information
            // within the composer.json files are removed (e.g. source/support nodes got purged)
            $index = Packager_Index::factory();
            $index->packages();

            // if file is any type but json, further checking against capacities
            // json files can be read by anyone with a valid user
            $capacity = null;
            if ($ext == 'tar' || $ext == 'zip') {
                $capacities = $user->capacities->where('active', '=', 1)->and_where('open_downloads', '!=', 0)->find_all()->as_array();

                // get package date
                $packages = $index->find_by_uri($file);
                $package = (empty($packages)) ? null : current($packages);

                if ($package === null) {
                    throw new HTTP_Exception_404("No package found for file " . $file . ".");
                }

                // find matching capacities
                $capacities = array_filter($capacities, function ($capacity) use ($package) {
                    return $this->capacity_matches($capacity, $package);
                });

                if (count($tmp = array_filter($capacities, function ($capacity) {
                        return $capacity->open_downloads == -1;
                    })) > 0) {
                    $capacity = current($tmp); // use first unlimited capacity record
                } else {
                    // sort capacities by matching score ASC
                    usort($capacities, function ($a, $b) {
                        return ($a->score() < $b->score()) ? -1 : 1;
                    });

                    // use best-matching capacity (capacity with smallest score
                    if (!empty($capacities)) {
                        $capacity = $capacities[0];
                    }
                }

                if ($capacity === null) {
                    throw new HTTP_Exception_402("No capacity left to serve package " . $package->name() . " (" . $package->version() . ").");
                }
            }

            // Determine Content Type
            $ctype = Kohana_File::mime_by_ext($ext);
            if ($ctype === false) {
                $ctype = "application/force-download";
            }

            // serve file
            header("Content-Type: $ctype");
            ob_clean();
            flush();
            readfile($local_file);

            // if file is served due to a capability: create protocol entry for the download
            if ($capacity !== null && $package !== null) {

                // create download entry
                $download = ORM::factory('Packager_Download');
                $download->capacity_id = $capacity->pk();
                $download->package = $package->name();
                $download->version = $package->version();
                $download->ip = Request::$client_ip;
                $download->host = gethostbyaddr(Request::$client_ip);
                $download->save();

                // reduce number of open downloads (if not unlimited [-1])
                if ($capacity->open_downloads > 0) {
                    $capacity->open_downloads = $capacity->open_downloads - 1;
                    $capacity->save();
                }
            }
        }
    }

    /**
     * Returns true if the given capacity objects matches in package and version for given package object.
     * If true is returned, the capacity will also store the matching score: The smaller the score the more
     * accurate and specific the matching pattern has been within the capacity.
     * Else, returns false.
     *
     * @param Model_Packager_Capacity $capacity
     * @param Model_Packager_Package $package
     * @return bool
     */
    public function capacity_matches(Model_Packager_Capacity $capacity, Model_Packager_Package $package)
    {
        $package_pattern = '/' . str_replace(array('*', '/'), array('.*', '\/'), $capacity->package) . '/mi';
        $version_pattern = '/' . str_replace('*', '.*', $capacity->version) . '/mi';

        if (preg_match($package_pattern, $package->name()) && preg_match($version_pattern, $package->version())) {
            // weighting the differences in package name way higher: This will ensure package match much more valuable than version match
            $score = 100 * levenshtein($capacity->package, $package->name()) + levenshtein($capacity->version, $package->version());
            $capacity->score($score);
            return true;
        }
        return false;
    }

    /**
     * Returns the current authenticated user or null, if no user is authenticated.
     *
     * @return ORM
     */
    private function get_auth_user()
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            $model = ORM::factory('Packager_User')->where('name', '=', trim($user))->find();

            if (trim($password) == '' && $model->loaded() && $model->active == 1) {
                return $model;
            } else if ($model->loaded()) {
                throw new HTTP_Exception_403("User is not activated.");
            }
        }

        $this->response->headers('WWW-Authenticate', 'Basic realm="Packager"');
        $this->response->headers('HTTP/1.0 401 Unauthorized');
        $this->response->headers('Content-Type', 'application/json');
        $error = new stdClass();
        $error->code = "401";
        $error->msg = "Unauthorized";
        echo json_encode($error);
        return null;
    }
}