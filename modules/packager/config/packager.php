<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Configuration for the packager module. Packager is used to turn
 * a generated Satis repository into a private repository with
 * access restriction.
 */
return array(
    /**
     * Output directory defined when running satis build.
     * Directory should contain composer.json and include/
     */
    'satis_output' => '',

    /**
     * Satis archive to hold all packages
     */
    'satis_archive' => '',

    /**
     * If set to true, the satis' composer.json files will be purged of any source-references of
     * the package. This will ensure that composer can get the package only by the 'dist' node using
     * the satis/packager server.
     */
    'purge_source' => true,

    /**
     * If set to true, the satis' composer.json files will be purged of any support nodes
     * which may reference a public VCS
     */
    'purge_support' => false,

    /**
     * Lifetime of the packager's index in seconds after which it will be
     * regenerated automatically. Use following minion task to enforce
     * recreation:
     * ./minion packager:index --create
     */
    'index_lifetime' => 60 * 60 * 24 * 7,

    /**
     * Database group to use. Use NULL for the default database
     */
    'db' => null,

    /**
     * Table names
     */
    'tbl_user' => 'packager_users',
    'tbl_capacity' => 'packager_capacities',
    'tbl_download' => 'packager_downloads',

    /**
     * When generating random usernames, a random string with this size is used.
     */
    'random_username_size' => 16
);