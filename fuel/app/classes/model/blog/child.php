<?php

/**
 * One child blog belonging to a main article.
 * Each child owns exactly three content parts.
 */
class Model_Blog_Child extends \Orm\Model
{
	protected static $_table_name = 'blog_children';

	protected static $_properties = array(
		'id',
		'blog_id',
		'position',
		'created',
		'modified',
	);

	protected static $_belongs_to = array(
		'blog' => array(
			'key_from' => 'blog_id',
			'model_to' => 'Model_Blog',
			'key_to' => 'id',
		),
	);

	protected static $_has_many = array(
		'contents' => array(
			'key_from' => 'id',
			'model_to' => 'Model_Blog_Content',
			'key_to' => 'blog_child_id',
			'cascade_save' => true,
			'cascade_delete' => true,
			'conditions' => array(
				'order_by' => array('position' => 'asc'),
			),
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
