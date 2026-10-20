<?php

/**
 * Guests cannot open the todo list or blogs.
 */
class Middleware_Auth
{
	public static function handle()
	{
		$uri = strtok(trim((string) \Input::uri(), '/'), '?');
		if ( ! static::requires_auth($uri === false ? '' : $uri))
		{
			return;
		}

		if (\App_Auth::user() !== null)
		{
			return;
		}

		\Session::set_flash('error', 'Hãy đăng nhập để xem trang này.');
		\Response::redirect('login');
	}

	/**
	 * @param string $uri
	 * @return bool
	 */
	protected static function requires_auth($uri)
	{
		foreach (\Config::get('middleware.auth', array()) as $pattern)
		{
			$pattern = trim($pattern, '/');
			if ($pattern === $uri)
			{
				return true;
			}

			if (substr($pattern, -2) === '/*')
			{
				$prefix = substr($pattern, 0, -2);
				if ($uri === $prefix || strpos($uri, $prefix.'/') === 0)
				{
					return true;
				}
			}
		}

		return false;
	}
}
