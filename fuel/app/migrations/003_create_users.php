<?php

namespace Fuel\Migrations;

/**
 * Store accounts that can open the todo list and blogs.
 */
class Create_users
{
	public function up()
	{
		if (\DBUtil::table_exists('users'))
		{
			return;
		}

		\DBUtil::create_table('users', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'name' => array('constraint' => 100, 'type' => 'varchar'),
			'email' => array('constraint' => 255, 'type' => 'varchar'),
			'password' => array('constraint' => 255, 'type' => 'varchar'),
			'created' => array('type' => 'datetime', 'null' => true),
			'modified' => array('type' => 'datetime', 'null' => true),
		), array('id'));

		\DBUtil::create_index('users', array('email'), 'users_email', 'UNIQUE');
	}

	public function down()
	{
		\DBUtil::drop_table('users');
	}
}
