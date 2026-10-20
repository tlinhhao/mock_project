<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Default route
	 * -------------------------------------------------------------------------
	 *
	 */

	'_root_' => 'todos/index',

	/**
	 * -------------------------------------------------------------------------
	 *  Page not found
	 * -------------------------------------------------------------------------
	 *
	 */

	'_404_' => 'welcome/404',

	/**
	 * -------------------------------------------------------------------------
	 *  Example for Presenter
	 * -------------------------------------------------------------------------
	 *
	 *  A route for showing page using Presenter
	 *
	 */

	'hello(/:name)?' => array('welcome/hello', 'name' => 'hello'),

	'login' => 'auth/login',
	'register' => 'auth/register',
	'logout' => 'auth/logout',

	'todos/add' => 'todos/add',
	'todos/toggle/(:num)' => 'todos/toggle/$1',
	'todos/delete/(:num)' => 'todos/delete/$1',

	'blogs' => 'blogs/index',
	'blogs/create' => 'blogs/create',
	'blogs/upload' => 'blogs/upload',
	'blogs/confirm' => 'blogs/confirm',
	'blogs/store' => 'blogs/store',
	'blogs/complete' => 'blogs/complete',
	'blogs/read/(:num)' => 'blogs/read/$1',
	'blogs/child/(:num)' => 'blogs/child/$1',
	'blogs/content/(:num)' => 'blogs/content/$1',
	'blogs/edit/(:num)' => 'blogs/edit/$1',
	'blogs/delete/(:num)' => 'blogs/delete/$1',
);
