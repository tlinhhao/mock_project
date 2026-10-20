<?php

/**
 * Main article. Child blogs live in blog_children.
 */
class Model_Blog extends \Orm\Model
{
	protected static $_table_name = 'blogs';

	protected static $_properties = array(
		'id',
		'title',
		'user_id',
		'category_id',
		'created',
		'modified',
	);

	protected static $_has_many = array(
		'children' => array(
			'key_from' => 'id',
			'model_to' => 'Model_Blog_Child',
			'key_to' => 'blog_id',
			'cascade_save' => true,
			'cascade_delete' => true,
			'conditions' => array(
				'order_by' => array('position' => 'asc'),
			),
		),
	);

	protected static $_belongs_to = array(
		'user' => array(
			'key_from' => 'user_id',
			'model_to' => 'Model_User',
			'key_to' => 'id',
		),
		'category' => array(
			'key_from' => 'category_id',
			'model_to' => 'Model_Category',
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
