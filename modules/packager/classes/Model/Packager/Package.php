<?php defined('SYSPATH') or die('No direct script access.');

class Model_Packager_Package
{
    private $vendor = '';
    private $package = '';
    private $version = '';
    private $uri = '';

    /**
     * Returns the full package name
     *
     * @return String
     */
    public function name()
    {
        return $this->vendor() . '/' . $this->package();
    }

    /**
     * Sets and gets the package's vendor
     *
     * @param String $value
     * @return $this|String
     */
    public function vendor($value = NULL)
    {
        if ($value === null) {
            return $this->vendor;
        }
        $this->vendor = $value;
        return $this;
    }

    /**
     * Sets and gets the package's package name
     *
     * @param String $value
     * @return $this|String
     */
    public function package($value = NULL)
    {
        if ($value === null) {
            return $this->package;
        }
        $this->package = $value;
        return $this;
    }

    /**
     * Sets and gets the package's version
     *
     * @param String $value
     * @return $this|String
     */
    public function version($value = NULL)
    {
        if ($value === null) {
            return $this->version;
        }
        $this->version = $value;
        return $this;
    }

    /**
     * Sets and gets the package's uri
     *
     * @param String $value
     * @return $this|String
     */
    public function uri($value = NULL)
    {
        if ($value === null) {
            return $this->uri;
        }
        $this->uri = $value;
        return $this;
    }
}