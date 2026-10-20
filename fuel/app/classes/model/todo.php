<?php

/**
 * Todo row stored in the todos table.
 */
class Model_Todo extends \Orm\Model
{
	protected static $_table_name = 'todos';

	protected static $_properties = array(
		'id',
		'title',
		'completed',
		'created',
		'modified',
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
