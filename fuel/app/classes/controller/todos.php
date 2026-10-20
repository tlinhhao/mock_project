<?php

/**
 * Todo list actions.
 */
class Controller_Todos extends Controller_Base
{
	/**
	 * Show the list and the create form.
	 */
	public function action_index()
	{
		$data = array(
			'todos' => Model_Todo::query()->order_by('created', 'desc')->get(),
		);

		$this->template->title = 'Việc cần làm';
		$this->template->set_safe('content', \View::forge('todos/index', $data));
	}

	/**
	 * Store a new todo.
	 */
	public function action_add()
	{
		$this->require_post();

		$title = trim((string) \Input::post('title', ''));
		$length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
		if ($title === '' || $length > 200)
		{
			\Session::set_flash('error', 'Không thêm được. Hãy nhập nội dung việc cần làm.');
			\Response::redirect('/');
		}

		$todo = Model_Todo::forge(array(
			'title' => $title,
			'completed' => 0,
			'modified' => \Date::time()->format('mysql'),
		));
		$todo->save();

		\Session::set_flash('success', 'Đã thêm việc.');
		\Response::redirect('/');
	}

	/**
	 * Flip the completed flag.
	 *
	 * @param int|string $id Todo id
	 */
	public function action_toggle($id = null)
	{
		$this->require_post();

		$todo = Model_Todo::find($id);
		if ($todo === null)
		{
			throw new \HttpNotFoundException();
		}

		$todo->completed = $todo->completed ? 0 : 1;
		$todo->save();
		\Response::redirect('/');
	}

	/**
	 * Remove a todo.
	 *
	 * @param int|string $id Todo id
	 */
	public function action_delete($id = null)
	{
		$this->require_post();

		$todo = Model_Todo::find($id);
		if ($todo === null)
		{
			throw new \HttpNotFoundException();
		}

		$todo->delete();
		\Session::set_flash('success', 'Đã xóa việc.');
		\Response::redirect('/');
	}

	/**
	 * Accept only a valid POST form.
	 */
	protected function require_post()
	{
		if (\Input::method() !== 'POST' || ! \Security::check_token())
		{
			\Session::set_flash('error', 'Không lưu được. Hãy thử lại.');
			\Response::redirect('/');
		}
	}
}
