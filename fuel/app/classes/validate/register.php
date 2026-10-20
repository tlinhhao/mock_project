<?php

/**
 * Form request for creating an account.
 */
class Validate_Register extends Validate_Authform
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
			'name' => array(
				array('trim'),
				array('required'),
				array('max_length', 100),
			),
			'email' => array(
				array('trim'),
				array('required'),
				array('valid_email'),
				array('max_length', 255),
				array(array($this, 'unique_email')),
			),
			'password' => array(
				array('required'),
				array('min_length', 6),
			),
			'password_confirm' => array(
				array('required'),
				array('match_field', 'password'),
			),
		);
	}

	/**
	 * @return array
	 */
	public function messages()
	{
		return array(
			'name' => array(
				'required' => 'Hãy nhập tên.',
				'max_length' => 'Tên tối đa 100 ký tự.',
			),
			'email' => array(
				'required' => 'Hãy nhập email.',
				'valid_email' => 'Email không đúng định dạng.',
				'max_length' => 'Email quá dài.',
				'Validate_Register:unique_email' => 'Email này đã được đăng ký.',
			),
			'password' => array(
				'required' => 'Hãy nhập mật khẩu.',
				'min_length' => 'Mật khẩu tối thiểu 6 ký tự.',
			),
			'password_confirm' => array(
				'required' => 'Hãy nhập lại mật khẩu.',
				'match_field' => 'Mật khẩu nhập lại không khớp.',
			),
		);
	}

	/**
	 * @param string $email
	 * @return bool
	 */
	public function unique_email($email)
	{
		return Model_User::query()->where('email', strtolower(trim((string) $email)))->count() === 0;
	}
}
