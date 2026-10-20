<?php

/**
 * Form request for signing in.
 */
class Validate_Login extends Validate_Authform
{
	/**
	 * @return static
	 */
	public static function forge()
	{
		return new static();
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			'email' => array(
				array('trim'),
				array('required'),
				array('valid_email'),
			),
			'password' => array(
				array('required'),
			),
		);
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return array(
			'email' => array(
				'required' => 'Hãy nhập email.',
				'valid_email' => 'Email không đúng định dạng.',
			),
			'password' => array(
				'required' => 'Hãy nhập mật khẩu.',
			),
		);
	}
}
