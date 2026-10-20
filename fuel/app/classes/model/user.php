<?php

/**
 * Account allowed to open the todo list and blogs.
 */
class Model_User extends \Orm\Model
{
	protected static $_table_name = 'users';

	protected static $_properties = array(
		'id',
		'name',
		'email',
		'password',
		'created',
		'modified',
	);

	protected static $_observers = array(
		'Orm\\Observer_CreatedAt' => array(
			'events' => array('before_insert'),
			'mysql_timestamp' => true,
			'property' => 'created',
		),
		'Orm\\Observer_UpdatedAt' => array(
			'events' => array('before_update'),
			'mysql_timestamp' => true,
			'property' => 'modified',
		),
	);

	/**
	 * @param string $password Plain password from the registration form
	 */
	public function set_password($password)
	{
		$this->password = password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * @param string $password Plain password from the login form
	 * @return bool
	 */
	public function check_password($password)
	{
		return password_verify((string) $password, (string) $this->password);
	}
}
