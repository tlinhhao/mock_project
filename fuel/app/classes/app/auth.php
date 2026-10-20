<?php

/**
 * The signed-in account for this request.
 */
class App_Auth
{
	/**
	 * @var \Orm\Model|null|false
	 */
	protected static $user = false;

	/**
	 * @return \Orm\Model|null
	 */
	public static function user()
	{
		if (static::$user !== false)
		{
			return static::$user;
		}

		static::$user = null;
		$user_id = \Session::get('user_id');
		if ($user_id)
		{
			static::$user = Model_User::find($user_id);
			if (static::$user === null)
			{
				\Session::delete('user_id');
			}
		}

		return static::$user;
	}

	/**
	 * @param \Orm\Model $user
	 */
	public static function login($user)
	{
		\Session::set('user_id', (int) $user->id);
		\Session::rotate();
		static::$user = $user;
	}
}
