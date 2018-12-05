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
        'dsn' => 'mysql:dbname=quiz;host=localhost;charset=utf8',
        'username' => 'homestead',
        'password' => 'secret'
    ],
    'session_config' => [
        'cookie_lifetime' => 60*60*24,
        'gc_maxlifetime' => 60*60*24*30
    ],
    'session_storage' => [
        'type' => SessionArrayStorage::class
    ],
    'app' => [
        'name' => '10Quiz',
        'email' => 'example@gmail.com',
        'root_path' => '/home/vagrant/quiz/'
    ],
    'mail' => [
        'server' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'example@gmail.com',
        'password' => '12345678'
    ]
];