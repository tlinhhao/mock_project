<?php

/**
 * Shared page setup for screens that use the main template.
 */
class Controller_Base extends Controller_Template
{
	public $template = 'template';

	/**
	 * @var \Orm\Model|null
	 */
	protected $current_user = null;

	public function before()
	{
		parent::before();

		$this->current_user = \App_Auth::user();
		$this->template->set('current_user', $this->current_user, false);
	}
}
