<?php

namespace Fuel\Migrations;

/**
 * Move the three content parts under a child blog so one article can own many children.
 */
class Create_blog_children
{
	public function up()
	{
		if ( ! \DBUtil::table_exists('blog_children'))
		{
			\DBUtil::create_table('blog_children', array(
				'id' => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
				'blog_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true),
				'position' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true),
				'created' => array('type' => 'datetime', 'null' => true),
				'modified' => array('type' => 'datetime', 'null' => true),
			), array('id'));

			\DBUtil::create_index('blog_children', array('blog_id', 'position'), 'blog_child_position', 'UNIQUE');
		}

		if ( ! \DBUtil::field_exists('blog_contents', array('blog_id')))
		{
			return;
		}

		if ( ! \DBUtil::field_exists('blog_contents', array('blog_child_id')))
		{
			\DBUtil::add_fields('blog_contents', array(
				'blog_child_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true, 'null' => true),
			));
		}

		$blogs = \DB::select('id', 'created', 'modified')->from('blogs')->execute();
		$child_ids = array();
		foreach ($blogs as $blog)
		{
			$blog_id = (int) $blog['id'];
			$existing = \DB::select('id')
				->from('blog_children')
				->where('blog_id', $blog_id)
				->where('position', 1)
				->execute()
				->as_array();

			if ($existing)
			{
				$child_ids[$blog_id] = (int) $existing[0]['id'];
				continue;
			}

			$result = \DB::insert('blog_children')->set(array(
				'blog_id' => $blog_id,
				'position' => 1,
				'created' => $blog['created'],
				'modified' => $blog['modified'],
			))->execute();
			$child_ids[$blog_id] = (int) $result[0];
		}

		$contents = \DB::select('id', 'blog_id', 'position', 'image')
			->from('blog_contents')
			->execute();
		foreach ($contents as $content)
		{
			$blog_id = (int) $content['blog_id'];
			if ( ! isset($child_ids[$blog_id]))
			{
				continue;
			}

			$child_id = $child_ids[$blog_id];
			$image = $this->move_legacy_image((string) $content['image'], $blog_id, $child_id, (int) $content['position']);
			\DB::update('blog_contents')
				->set(array(
					'blog_child_id' => $child_id,
					'image' => $image,
				))
				->where('id', (int) $content['id'])
				->execute();
		}

		$index = \DB::query("SHOW INDEX FROM blog_contents WHERE Key_name = 'blog_position'")->execute();
		if (count($index) > 0)
		{
			\DB::query('ALTER TABLE blog_contents DROP INDEX blog_position')->execute();
		}

		\DB::query('DELETE FROM blog_contents WHERE blog_child_id IS NULL')->execute();
		\DBUtil::drop_fields('blog_contents', 'blog_id');
		\DB::query('ALTER TABLE blog_contents MODIFY blog_child_id INT UNSIGNED NOT NULL')->execute();

		$index = \DB::query("SHOW INDEX FROM blog_contents WHERE Key_name = 'child_position'")->execute();
		if (count($index) === 0)
		{
			\DBUtil::create_index('blog_contents', array('blog_child_id', 'position'), 'child_position', 'UNIQUE');
		}
	}

	public function down()
	{
		if (\DBUtil::table_exists('blog_children'))
		{
			\DBUtil::drop_table('blog_children');
		}
	}

	/**
	 * Move uploads/blogs/{blog}/{part}.ext to uploads/blogs/{blog}/{child}/{part}.ext.
	 *
	 * @param string $image
	 * @param int $blog_id
	 * @param int $child_id
	 * @param int $position
	 * @return string
	 */
	protected function move_legacy_image($image, $blog_id, $child_id, $position)
	{
		$pattern = '#^uploads/blogs/'.$blog_id.'/'.$position.'\.([a-z0-9]{1,8})$#';
		if ( ! preg_match($pattern, $image, $match))
		{
			return $image;
		}

		$root = is_dir(DOCROOT.'public') ? DOCROOT.'public'.DIRECTORY_SEPARATOR : DOCROOT;
		$source = $root.$image;
		$relative = 'uploads/blogs/'.$blog_id.'/'.$child_id.'/'.$position.'.'.$match[1];
		$directory = $root.'uploads/blogs/'.$blog_id.'/'.$child_id;
		if ( ! is_file($source))
		{
			return $relative;
		}

		if ( ! is_dir($directory) && ! mkdir($directory, 0775, true))
		{
			return $image;
		}

		if ( ! rename($source, $root.$relative))
		{
			return $image;
		}

		return $relative;
	}
}
