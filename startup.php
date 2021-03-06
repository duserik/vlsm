<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    // if (empty($_SESSION['csrf'])) {
    //     $_SESSION['csrf'] = bin2hex(random_bytes(32));
    // }    
}

if (php_sapi_name() !== 'cli') {
    // base directory
    $base_dir = __DIR__;

    $doc_root = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);

    // server protocol
    $protocol = (!empty($_SERVER['HTTPS'])  && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // domain name
    $domain = $_SERVER['SERVER_NAME'];

    // base url
    $base_url = preg_replace("!^${doc_root}!", '', $base_dir);

    // server port
    $port = $_SERVER['SERVER_PORT'];
    $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";

    // put em all together to get the complete base URL
    $domain = "${protocol}://${domain}${disp_port}${base_url}";

    defined('DOMAIN')
        || define('DOMAIN', $domain);
}

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', __DIR__);

defined('UPLOAD_PATH')
    || define('UPLOAD_PATH', APPLICATION_PATH . DIRECTORY_SEPARATOR . 'uploads');

defined('TEMP_PATH')
    || define('TEMP_PATH', APPLICATION_PATH . DIRECTORY_SEPARATOR . 'temporary');

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ?
        getenv('APPLICATION_ENV') :
        'production'));


set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'vendor'),
    realpath(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'models'),
    get_include_path()
)));


require_once('autoload.php');
require_once(APPLICATION_PATH . "/configs/config." . APPLICATION_ENV . ".php");

// Let us create database object
$db = new MysqliDb($systemConfig['dbHost'], $systemConfig['dbUser'], $systemConfig['dbPassword'], $systemConfig['dbName'], $systemConfig['dbPort']);
