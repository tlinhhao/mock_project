<?php

namespace Fuel\Migrations;

/**
 * Store one blog post and its three tab sections.
 */
class Create_blogs
{
	public function up()
	{
		if ( ! \DBUtil::table_exists('blogs'))
		{
			\DBUtil::create_table('blogs', array(
				'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
				'created' => array('type' => 'datetime', 'null' => true),
				'modified' => array('type' => 'datetime', 'null' => true),
			), array('id'));
		}

		if ( ! \DBUtil::table_exists('blog_contents'))
		{
			\DBUtil::create_table('blog_contents', array(
				'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
				'blog_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true),
				'position' => array('constraint' => 1, 'type' => 'tinyint', 'unsigned' => true),
				'body' => array('type' => 'text'),
				'image' => array('constraint' => 255, 'type' => 'varchar'),
				'created' => array('type' => 'datetime', 'null' => true),
				'modified' => array('type' => 'datetime', 'null' => true),
			), array('id'));

			\DBUtil::create_index('blog_contents', array('blog_id', 'position'), 'blog_position', 'UNIQUE');
		}
	}

	public function down()
	{
		\DBUtil::drop_table('blog_contents');
		\DBUtil::drop_table('blogs');
	}
}
