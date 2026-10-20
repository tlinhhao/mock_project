<?php

/**
 * Small form request used by login and registration.
 */
abstract class Validate_Authform
{
	protected $validation = null;

	protected $ran = false;

	protected $passed = false;

	/**
	 * @return array
	 */
	abstract public function rules();

	/**
	 * @return array
	 */
	abstract public function messages();

	/**
	 * @return bool
	 */
	public function fails()
	{
		return ! $this->passes();
	}

	/**
	 * @return bool
	 */
	public function passes()
	{
		if ($this->ran)
		{
			return $this->passed;
		}

		$this->ran = true;
		$this->passed = (bool) $this->validator()->run($this->input());

		return $this->passed;
	}

	/**
	 * @return array
	 */
	public function errors()
	{
		$errors = array();

		foreach ($this->validator()->error() as $name => $error)
		{
			$errors[$name] = $error->get_message();
		}

		return $errors;
	}

	/**
	 * @param string $field
	 * @param mixed $default
	 * @return mixed
	 */
	public function validated($field = null, $default = null)
	{
		$this->passes();

		if ($field === null)
		{
			return $this->validator()->validated();
		}

		$value = $this->validator()->validated($field, null);
		if ($value === null)
		{
			return array_key_exists($field, $this->input()) ? $this->input()[$field] : $default;
		}

		return $value;
	}

	/**
	 * @return array
	 */
	protected function input()
	{
		$input = array();

		foreach (array_keys($this->rules()) as $name)
		{
			$input[$name] = \Input::post($name, '');
		}

		return $input;
	}

	/**
	 * @return \Validation
	 */
	protected function validator()
	{
		if ($this->validation !== null)
		{
			return $this->validation;
		}

		$validation = \Validation::forge(get_class($this));
		$validation->add_callable($this);

		foreach ($this->rules() as $name => $rules)
		{
			$field = $validation->add($name, $name);
			foreach ($rules as $rule)
			{
				$callback = $rule[0];
				$params = array_slice($rule, 1);
				call_user_func_array(array($field, 'add_rule'), array_merge(array($callback), $params));
			}
		}

		foreach ($this->messages() as $name => $messages)
		{
			$field = $validation->field($name);
			foreach ($messages as $rule => $message)
			{
				$field->set_error_message($rule, $message);
			}
		}

		$this->validation = $validation;

		return $this->validation;
	}
}
