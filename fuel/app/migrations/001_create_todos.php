<?php

namespace Fuel\Migrations;

/**
 * Create the todos table when it does not already exist.
 */
class Create_todos
{
	public function up()
	{
		if (\DBUtil::table_exists('todos'))
		{
			return;
		}

		\DBUtil::create_table('todos', array(
			'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'title' => array('constraint' => 200, 'type' => 'varchar'),
			'completed' => array('constraint' => 1, 'type' => 'tinyint', 'default' => 0),
			'created' => array('type' => 'datetime', 'null' => true),
			'modified' => array('type' => 'datetime', 'null' => true),
		), array('id'));
	}

	public function down()
	{
		\DBUtil::drop_table('todos');
	}
}
