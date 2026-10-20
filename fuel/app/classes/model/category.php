<?php

/**
 * Newspaper section such as Thời sự or Công nghệ.
 */
class Model_Category extends \Orm\Model
{
	protected static $_table_name = 'categories';

	protected static $_properties = array(
		'id',
		'name',
		'created',
		'modified',
	);

	protected static $_has_many = array(
		'blogs' => array(
			'key_from' => 'id',
			'model_to' => 'Model_Blog',
			'key_to' => 'category_id',
		),
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
}
