# Packager

Kohana module to serve Satis repository with authorization for private packages.

## Prerequisites

Following programs are required:

* PHP 7+
* Composer 1.4+
* Koseven 3.3+
* Satis 1.23+
* Database

Following Kohana modules are required:

* Minion
* Cache
    * Correctly configured for example with a file cache
* Database 
    * Working connection required, e.g. with MySql
* ORM

## Installation in existing Kohana system

Install the latest version with

```
$ composer require seth-network/packager
```

Enable the module for the Kohana system

## Installation with Seth's Kohana Extension

Install latest version of bootstrapper and packager:

```
$ composer require seth-network/ske-bootstrap seth-network/packager
```

Run init script
```
$ composer run-script init-ske
```

Enable packager module:
```
$ ./minion module --add=MODPATH/vendor/seth-network/packager/modules/packager --enable packager
```

## Configuration

Required is a working configured Kohana system and Database module. A working database connection is also mandatory.
See the Kohana documentation for more information.

Create database structure with default settings:
```
$ ./minion packager:install
 
// To not use default settings, you may use following options:
// ./minion packager:install --db=[DATABASE_GROUP] --prefix=[TABLE_PREFIX]
```

Configure the module, you should use the same directories as defined for the satis' build command:
```
$ ./minion packager:config --output=[PATH_TO_SATIS_OUTPUT] --archive=[PATH_TO_SATIS_ARCHIVE]
```

Further configuration can be done in the module's config file:
```
APPPATH/config/packager.php
```

## Configuration of Satis
To work correctly with the Packager module, your Satis configuration file (`satis.json`) should include following
`prefix-url` in the `archive` node:
```
"prefix-url": "https://yourdomain.com/packager"
``` 
If your Kohana installation does use a different base path, be sure to add this to the prefix-url too! Also make sure your
web server's SSL settings are correctly to not run in any trouble as `https` is not working properly.

### Cron job
As the Satis repository will be periodically build using a cron-job, the Packager's index should also be refreshed:
```
$ ./minion packager:index --create
```
You may add this command to your con-tab as soon as Satis rebuilds its repository.

## How to use
Packager module is used with minion task. A separate UI may follow in a different module.

### Add user
To add a new user, use following task:
```
$ ./minion packager:user --create --enable
```
Without further details, a random user name will be generated. To identify a user, the user's id or name can be used.

```
Please note: A user must be enabled before it can use the repository! 
```
### Add capacity for packages
A new capacity can be create like:
```
$ ./minion packager:capacity --create=joe --package=seth-network/* --enable
```
This will create an unlimited capacity for user joe to all packages of vendor "seth-network" for all versions. You
can list the capacities with:
```
$ ./minion packager:capacity
```

To limit the vendor, packages, versions or downloads, you may edit your already created capacity:
```
$ ./minion packager:capacity --package=seth-network/packager --version=1.0* --downloads=10 1
```
Capacities are reference with their id only.

```
Please note: A capacity must be enabled before it can serve a package! 
```

### List downloads
To display a list of downloads for a user or package, use following task:
```
$ ./minion packager:capacity --downloads [user or package]
```

## How does it work

Packager maintains a list of users with their capacities to download Composer packages from a statis repository (Satis).
Each capacity may contain wildcards for vendor or package name or the package's version. Similar to [Gemfury](https://gemfury.com/l/composer-repository)
the user provides the packager's username for basic auth (without password) to access the capacities to download packages.

Each capacity may be limited to the amount of open downloads (or unlimited) and the packager will bill the most exact
capacity if more than one matches (due to the usage of wildcards). If an unlimited capacity matches a current request, the
download will use this one (means: no open download is consumed).

### Usage with composer
Simple add following snippet to your composer.json to fetch packages from your Packager:
```
"repositories": [{
    "type": "composer",
    "url": "http://yourdomain.org/packager"
  }]
```
If configured correctly, you should see use this snippit on the Satis index page: [https://yourdomain.org/packager/index.html](https://yourdomain.org/packager/index.html)

To provide the user name, either enter the name when ask when running `composer install` or `update` or place an `auth.json`
within your project directory:
```
    "http-basic": {
        "yourdomain": {
            "username": "[username]",
            "password": ""
        }
    }
```
Only the username is important (which may be a hash). Password will always be empty.

## I Need help!

Read the documentation of all task by using the `--help` flag:

```
$ ./minion packager:user --help
```

Feel free to [open an issue on github](https://github.com/seth-network/packager/issues/new). Please be as specific as possible if you want to get help. 


## Reporting bugs
If you've stumbled across a bug, please help us out by [reporting the bug](https://github.com/seth-network/packager/issues/new) you have found. Simply log in or register and submit a new issue, leaving as much information about the bug as possible, e.g.

* Steps to reproduce
* Expected result
* Actual result

This will help us to fix the bug as quickly as possible, and if you'd like to fix it yourself feel free to [fork us on GitHub](https://github.com/seth-network) and submit a pull request!

## Contributing

Any help is more than welcome! 
