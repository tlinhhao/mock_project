<?php

/**
 * Runs the middleware stack before the controller.
 */
class Middleware
{
	public static function run()
	{
		\Config::load('middleware', true);

		foreach (\Config::get('middleware.pipeline', array()) as $class)
		{
			if ( ! class_exists($class) || ! method_exists($class, 'handle'))
			{
				continue;
			}

			$class::handle();
		}
	}
}
