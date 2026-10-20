<?php

/**
 * Middleware runs before the controller.
 * auth lists the paths that require a signed-in account.
 */
return array(
	'pipeline' => array(
		'Middleware_Auth',
	),
	'auth' => array(
		'',
		'todos',
		'todos/*',
		'blogs',
		'blogs/*',
	),
);
