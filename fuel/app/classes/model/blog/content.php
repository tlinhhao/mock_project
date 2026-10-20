<?php

/**
 * One of the three content parts that belong to a child blog.
 */
class Model_Blog_Content extends \Orm\Model
{
	protected static $_table_name = 'blog_contents';

	protected static $_properties = array(
		'id',
		'blog_child_id',
		'position',
		'body',
		'image',
		'created',
		'modified',
	);

	protected static $_belongs_to = array(
		'child' => array(
			'key_from' => 'blog_child_id',
			'model_to' => 'Model_Blog_Child',
			'key_to' => 'id',
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
