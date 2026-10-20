<?php

namespace Fuel\Migrations;

/**
 * Add the fields a newspaper-style article needs on top of the three sections.
 */
class Add_article_fields
{
	public function up()
	{
		if ( ! \DBUtil::table_exists('categories'))
		{
			\DBUtil::create_table('categories', array(
				'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
				'name' => array('constraint' => 100, 'type' => 'varchar'),
				'created' => array('type' => 'datetime', 'null' => true),
				'modified' => array('type' => 'datetime', 'null' => true),
			), array('id'));
		}

		if ((int) \DB::select(\DB::expr('COUNT(*) as count'))->from('categories')->execute()->get('count') === 0)
		{
			$now = date('Y-m-d H:i:s');
			foreach (array('Thời sự', 'Đời sống', 'Công nghệ', 'Thể thao') as $name)
			{
				\DB::insert('categories')->set(array(
					'name' => $name,
					'created' => $now,
					'modified' => $now,
				))->execute();
			}
		}

		if ( ! \DBUtil::field_exists('blogs', array('title')))
		{
			\DBUtil::add_fields('blogs', array(
				'title' => array('constraint' => 200, 'type' => 'varchar', 'default' => ''),
				'summary' => array('constraint' => 500, 'type' => 'varchar', 'default' => ''),
				'user_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true, 'null' => true),
				'category_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true, 'null' => true),
			));
		}

		if ( ! \DBUtil::field_exists('blog_contents', array('image_caption')))
		{
			\DBUtil::add_fields('blog_contents', array(
				'image_caption' => array('constraint' => 255, 'type' => 'varchar', 'default' => ''),
			));
		}
	}

	public function down()
	{
		if (\DBUtil::field_exists('blog_contents', array('image_caption')))
		{
			\DBUtil::drop_fields('blog_contents', 'image_caption');
		}

		if (\DBUtil::field_exists('blogs', array('title')))
		{
			\DBUtil::drop_fields('blogs', array('title', 'summary', 'user_id', 'category_id'));
		}

		\DBUtil::drop_table('categories');
	}
}
