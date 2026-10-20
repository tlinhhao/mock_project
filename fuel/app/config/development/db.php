<?php

/**
 * MySQL settings for the local todo list.
 * The application container reaches MySQL by the service name mysql-local.
 */
return array(
	'default' => array(
		'type' => 'pdo',
		'connection' => array(
			'dsn' => 'mysql:host=mysql-local;dbname=todo_list;charset=utf8mb4',
			'username' => 'user',
			'password' => 'user',
		),
		'charset' => 'utf8mb4',
	),
);
