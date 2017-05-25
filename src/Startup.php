<?php

namespace Copona\System;

class Startup
{

    const VERSION = '2.3.0.3_rc';

    const PHP_VERSION_REQUIRED = '5.6.4';

    private $public_dir;

    public function __construct($public_dir)
    {
        $this->public_dir = $public_dir;

        spl_autoload_register('library_autoload');
        spl_autoload_extensions('.php');
    }

    /**
     * Check if SSL
     *
     * @return bool
     */
    public function checkSSL()
    {
        if (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
            $_SERVER['HTTPS'] = true;
        } else {
            $_SERVER['HTTPS'] = false;
        }

        return $_SERVER['HTTPS'];
    }

    /**
     * DEfine timezone default
     */
    public function defineTimeZone()
    {
        if (!ini_get('date.timezone')) {
            date_default_timezone_set('UTC');
        }
    }

    /**
     * Check PHP Version
     */
    public function checkPhpVersion()
    {
        if (version_compare(PHP_VERSION, self::PHP_VERSION_REQUIRED, '<') == true) {
            exit('PHP ' . self::PHP_VERSION_REQUIRED . '+ required. Your version is:' . PHP_VERSION);
        }
    }


    function start($application_config)
    {
        require_once(DIR_SYSTEM . 'framework.php');
    }

    /**
     * Windows IIS Compatibility
     */
    public function windowsCompatibily()
    {
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
            }
        }

        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            if (isset($_SERVER['PATH_TRANSLATED'])) {
                $_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
            }
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'], 1);

            if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
        }
    }

    /**
     * Define constants
     */
    protected function defineConstants()
    {
        // Version
        define('VERSION', self::VERSION);

        define('APPLICATION', basename(realpath('')) == 'admin' ? 'admin' : 'catalog');

        //Get port
        $server_port = '';
        if (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != 80) && $_SERVER['SERVER_PORT'] != 443) {
            $server_port = ':' . $_SERVER['SERVER_PORT'];
        }

        //define domain url constant
        define('DOMAIN_NAME', isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] . $server_port : null);

        $parse_url = parse_url($_SERVER['SCRIPT_NAME']);
        define('BASE_URI', str_replace(['index.php', '//'], '', $parse_url['path']));

        define('BASE_URL', DOMAIN_NAME . BASE_URI);

        define('BASE_URL_CATALOG', (str_replace(['index.php', 'admin', '//'], '', BASE_URL)));

        // HTTP
        define('HTTP_SERVER', 'http://' . BASE_URL);
        define('HTTP_CATALOG', 'http://' . BASE_URL_CATALOG);

        // HTTPS
        define('HTTPS_SERVER', 'https://' . BASE_URL);
        define('HTTPS_CATALOG', 'https://' . BASE_URL_CATALOG);

        // DIR
        define('DIR_APPLICATION', DIR_PUBLIC . '/catalog/');
        define('DIR_SYSTEM', DIR_PUBLIC . '/system/');
        define('DIR_IMAGE', DIR_PUBLIC . '/image/');
        define('DIR_LANGUAGE', DIR_PUBLIC . '/catalog/language/');
        define('DIR_TEMPLATE', DIR_PUBLIC . '/catalog/view/theme/');
        define('DIR_CONFIG', DIR_PUBLIC . '/system/config/');
        define('DIR_CACHE', DIR_PUBLIC . '/system/storage/cache/');
        define('DIR_DOWNLOAD', DIR_PUBLIC . '/system/storage/download/');
        define('DIR_LOGS', DIR_PUBLIC . '/system/storage/logs/');
        define('DIR_MODIFICATION', DIR_PUBLIC . '/system/storage/modification/');
        define('DIR_UPLOAD', DIR_PUBLIC . '/system/storage/upload/');
    }

    /**
     * Load engines
     *
     * @param array $engines
     */
    public function loadEngines(Array $engines)
    {
        foreach ($engines as $engine) {
            require_once(modification($engine));
        }
    }

    /**
     * Load helpers
     *
     * @param array $helpers
     */
    public function loadHelpers(Array $helpers)
    {
        foreach ($helpers as $helper) {
            require_once($helper);
        }
    }


//    /**
//     * Load Engines
//     */
//    protected function loadEngines()
//    {
//        require_once(modification(DIR_SYSTEM . 'engine/action.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/controller.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/event.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/hook.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/front.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/loader.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/model.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/registry.php'));
//        require_once(modification(DIR_SYSTEM . 'engine/proxy.php'));
//    }

//    /**
//     * Load Helpers
//     */
//    protected function loadHelpers()
//    {
//        require_once(DIR_SYSTEM . 'helper/general.php');
//        require_once(DIR_SYSTEM . 'helper/json.php');
//        require_once(DIR_SYSTEM . 'helper/utf8.php');
//    }

    /**
     * Modification Override
     *
     * @param $filename
     * @return string
     */
    public function modification($filename)
    {
        if (defined('DIR_CATALOG')) {
            $file = DIR_MODIFICATION . 'admin/' . substr($filename, strlen(DIR_APPLICATION));
        } elseif (defined('DIR_OPENCART')) {
            $file = DIR_MODIFICATION . 'install/' . substr($filename, strlen(DIR_APPLICATION));
        } else {
            $file = DIR_MODIFICATION . 'catalog/' . substr($filename, strlen(DIR_APPLICATION));
        }

        if (substr($filename, 0, strlen(DIR_SYSTEM)) == DIR_SYSTEM) {
            $file = DIR_MODIFICATION . 'system/' . substr($filename, strlen(DIR_SYSTEM));
        }

        if (is_file($file)) {
            return $file;
        }

        return $filename;
    }

    /**
     * Library Autoload
     */
    protected function library_autoload($class)
    {
        $file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

        if (is_file($file)) {
            include_once(modification($file));
        }
    }
}


$database_config = DIR_PUBLIC . '/config/database.php';
if (is_file($database_config)) {
    require($database_config);
} else {
    die('Duplicate "config/database.php.example" to "config/database.php" and define the constants');
}

// Debug helper
require_once(DIR_SYSTEM . 'helper/debug.php');

//Errors handler
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();


//// Universal Host redirect to correct hostname
//if (defined('HTTP_HOST') && defined('HTTPS_HOST') && $_SERVER['HTTP_HOST'] != parse_url(HTTPS_SERVER)['host'] && $_SERVER['HTTP_HOST'] != parse_url(HTTP_SERVER)['host']) {
//    header("Location: " . ($_SERVER['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER) . ltrim('/', $_SERVER['REQUEST_URI']));
//}