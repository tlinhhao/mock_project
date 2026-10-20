<?php

namespace Fuel\Migrations;

/**
 * Remove the article sapo and the image caption.
 */
class Drop_summary_and_caption
{
	public function up()
	{
		if (\DBUtil::field_exists('blogs', array('summary')))
		{
			\DBUtil::drop_fields('blogs', 'summary');
		}

		if (\DBUtil::field_exists('blog_contents', array('image_caption')))
		{
			\DBUtil::drop_fields('blog_contents', 'image_caption');
		}
	}

	public function down()
	{
		if ( ! \DBUtil::field_exists('blogs', array('summary')))
		{
			\DBUtil::add_fields('blogs', array(
				'summary' => array('constraint' => 500, 'type' => 'varchar', 'default' => ''),
			));
		}

		if ( ! \DBUtil::field_exists('blog_contents', array('image_caption')))
		{
			\DBUtil::add_fields('blog_contents', array(
				'image_caption' => array('constraint' => 255, 'type' => 'varchar', 'default' => ''),
			));
		}
	}
}
