<?php

/**
 * Register, sign in, and sign out.
 */
class Controller_Auth extends Controller_Base
{
	/**
	 * Show the sign-in form or accept it.
	 */
	public function action_login()
	{
		if ($this->current_user !== null)
		{
			\Response::redirect('/');
		}

		$errors = array();
		$email = '';

		if (\Input::method() === 'POST')
		{
			$this->require_post('login');
			$request = Validate_Login::forge();
			$email = (string) $request->validated('email', '');

			if ($request->fails())
			{
				$errors = $request->errors();
			}
			else
			{
				$user = Model_User::query()
					->where('email', strtolower($email))
					->get_one();

				if ($user === null || ! $user->check_password(\Input::post('password', '')))
				{
					$errors['form'] = 'Email hoặc mật khẩu không đúng.';
				}
				else
				{
					\App_Auth::login($user);
					\Session::set_flash('success', 'Đăng nhập thành công.');
					\Response::redirect('/');
				}
			}
		}

		$this->template->title = 'Đăng nhập';
		$this->template->set_safe('content', \View::forge('auth/login', array(
			'email' => $email,
			'errors' => $errors,
		)));
	}

	/**
	 * Show the registration form or create an account.
	 */
	public function action_register()
	{
		if ($this->current_user !== null)
		{
			\Response::redirect('/');
		}

		$errors = array();
		$values = array(
			'name' => '',
			'email' => '',
		);

		if (\Input::method() === 'POST')
		{
			$this->require_post('register');
			$request = Validate_Register::forge();
			$values['name'] = (string) $request->validated('name', '');
			$values['email'] = (string) $request->validated('email', '');

			if ($request->fails())
			{
				$errors = $request->errors();
			}
			else
			{
				$user = Model_User::forge(array(
					'name' => $values['name'],
					'email' => strtolower($values['email']),
				));
				$user->set_password(\Input::post('password', ''));
				$user->save();

				\Session::set_flash('success', 'Đăng ký thành công.');
				\Response::redirect('login');
			}
		}

		$this->template->title = 'Đăng ký';
		$this->template->set_safe('content', \View::forge('auth/register', array(
			'values' => $values,
			'errors' => $errors,
		)));
	}

	/**
	 * End the current session.
	 */
	public function action_logout()
	{
		$this->require_post('login');
		\Session::delete('user_id');
		\Session::delete('blog_draft');
		\Session::set_flash('success', 'Đã đăng xuất.');
		\Response::redirect('login');
	}

	/**
	 * Accept only a valid POST form.
	 *
	 * @param string $redirect
	 */
	protected function require_post($redirect)
	{
		if (\Input::method() !== 'POST' || ! \Security::check_token())
		{
			\Session::set_flash('error', 'Không lưu được. Hãy thử lại.');
			\Response::redirect($redirect);
		}
	}
}
