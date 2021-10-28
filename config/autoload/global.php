<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

use Zend\Session\Storage\SessionArrayStorage;

return [
    'db' => [
        'driver' => 'Pdo',
        'dsn' => "mysql:dbname=".getenv('DB_NAME').";host=".getenv('DB_HOST').";charset=utf8",
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS')
    ],
    'session_config' => [
        'cookie_lifetime' => 60*60*24,
        'gc_maxlifetime' => 60*60*24*30
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class
    ],
    'app' => [
        'name' => getenv('WEB_NAME'),
        'email' => getenv('MAIL_ADDRESS'),
        'root_path' => '/var/www/html/' //$config['app']['root_path'].'public/img/tests/';
    ],
    'mail' => [
        'server' => getenv('MAIL_HOST'),
        'port' => getenv('MAIL_PORT'),
        'username' => getenv('MAIL_USER'),
        'password' => getenv('MAIL_PASS')
    ]
];
