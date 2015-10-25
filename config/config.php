<?php

defined('APP_PATH') || define('APP_PATH', realpath('.'));

require "../vendor/autoload.php";
$dotenv = new \Dotenv\Dotenv(__DIR__);
$dotenv->load();

return new \Phalcon\Config(array(

    'database' => array(
        'adapter'    => getenv("ADAPTER"),
        'host'       => getenv("HOST"),
        'username'   => getenv("USERNAME"),
        'password'   => getenv("PASSWORD"),
        'dbname'     => getenv("DBNAME"),
        'charset'    => getenv("CHARSET"),
    ),

    'application' => array(
        'modelsDir'      => APP_PATH . '/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'baseUri'        => '/',
    )
));
