<?php

/**
 * Form request for a blog made of child posts with one rich-text body each.
 */
class Validate_Blog
{
	protected $blog_id;

	protected $children = array();

	protected $owned_child_ids = array();

	protected $normalized = false;

	protected $article = array(
		'title' => '',
		'category_id' => '',
	);

	protected $validation = null;

	protected $ran = false;

	protected $passed = false;

	/**
	 * @param int|null $blog_id
	 * @param array $children Existing child blogs used when an image is kept
	 */
	public function __construct($blog_id, array $children)
	{
		$this->blog_id = $blog_id ? (int) $blog_id : null;
		$this->children = $children;
		foreach ($children as $child)
		{
			if ( ! empty($child['id']))
			{
				$this->owned_child_ids[] = (int) $child['id'];
			}
		}
	}

	/**
	 * @param int|null $blog_id
	 * @param array $children
	 * @return static
	 */
	public static function forge($blog_id, array $children)
	{
		return new static($blog_id, $children);
	}

	/**
	 * Field rules, in the same shape as a form request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = array(
			'title' => array(
				array('trim'),
				array('required'),
				array('max_chars', 200),
			),
			'category_id' => array(
				array(array($this, 'validate_category')),
			),
			'children_present' => array(
				array(array($this, 'validate_child_count')),
			),
		);

		foreach ($this->posted_child_indexes() as $child)
		{
			$rules['child_'.$child.'_body'] = array(
				array(array($this, 'validate_body'), $child),
				array(array($this, 'validate_body_length'), $child),
			);
		}

		return $rules;
	}

	/**
	 * Messages keyed by field, then rule name.
	 *
	 * @return array
	 */
	public function messages()
	{
		$messages = array(
			'title' => array(
				'required' => 'Hãy nhập tiêu đề.',
				'max_chars' => 'Tiêu đề tối đa 200 ký tự.',
			),
			'category_id' => array(
				'validate_category' => 'Hãy chọn chuyên mục.',
				'Validate_Blog:validate_category' => 'Hãy chọn chuyên mục.',
			),
			'children_present' => array(
				'Validate_Blog:validate_child_count' => 'Hãy thêm từ 1 đến 20 blog con.',
			),
		);

		foreach ($this->posted_child_indexes() as $child)
		{
			$messages['child_'.$child.'_body'] = array(
				'Validate_Blog:validate_body' => 'Hãy nhập nội dung blog con '.$child.'.',
				'Validate_Blog:validate_body_length' => 'Nội dung blog con '.$child.' tối đa 20000 ký tự.',
			);
		}

		return $messages;
	}

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
	 * Errors grouped by child index.
	 *
	 * @return array
	 */
	public function errors()
	{
		$errors = array();
		$index_map = array();
		$normalized = 0;
		foreach ($this->posted_child_indexes() as $posted_index)
		{
			$normalized++;
			$index_map[$posted_index] = $normalized;
		}

		foreach ($this->validator()->error() as $name => $error)
		{
			if ($name === 'children_present')
			{
				$errors['children_form'] = $error->get_message();
				continue;
			}

			if (in_array($name, array('title', 'category_id'), true))
			{
				$errors[$name] = $error->get_message();
				continue;
			}

			if ( ! preg_match('/^child_(\d+)_body$/', $name, $match))
			{
				continue;
			}

			$child = isset($index_map[(int) $match[1]]) ? $index_map[(int) $match[1]] : (int) $match[1];
			$errors['children'][$child]['body'] = $error->get_message();
		}

		return $errors;
	}

	/**
	 * @return int
	 */
	public function first_error()
	{
		$errors = $this->errors();
		if (empty($errors['children']))
		{
			return 1;
		}

		return min(array_keys($errors['children']));
	}

	/**
	 * Child blogs after validation, numbered from 1.
	 *
	 * @return array
	 */
	public function validated()
	{
		$this->passes();
		if ($this->normalized)
		{
			return $this->children;
		}

		$title = $this->validator()->validated('title', null);
		if ($title !== null)
		{
			$this->article['title'] = $title;
		}

		$category_id = $this->validator()->validated('category_id', null);
		if ($category_id !== null && $category_id !== '')
		{
			$this->article['category_id'] = (int) $category_id;
		}

		$index = 0;
		$children = array();
		foreach ($this->children as $child)
		{
			$index++;
			$body = isset($child['body']) ? (string) $child['body'] : '';
			if (empty($child['prepared']))
			{
				$body = Blog_Html::sanitize($body);
			}

			$children[$index] = array(
				'id' => $child['id'],
				'body' => $body,
			);
		}

		$this->children = $children;
		$this->normalized = true;

		return $this->children;
	}

	/**
	 * @return array
	 */
	public function children()
	{
		$this->passes();
		$this->validated();

		return $this->children;
	}

	/**
	 * Headline and category after validation.
	 *
	 * @return array
	 */
	public function article()
	{
		$this->passes();
		$this->validated();

		return $this->article;
	}

	/**
	 * Accept between 1 and 20 child blogs.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function validate_child_count($value)
	{
		$count = count($this->posted_child_indexes());

		return $count >= 1 && $count <= 20;
	}

	/**
	 * Store the cleaned body and require text or an image.
	 *
	 * @param string $value
	 * @param int $child
	 * @return bool
	 */
	public function validate_body($value, $child)
	{
		$this->prepare_body($child, $value);

		return ! Blog_Html::is_empty($this->children[$child]['body']);
	}

	/**
	 * @param string $value
	 * @param int $child
	 * @return bool
	 */
	public function validate_body_length($value, $child)
	{
		$this->prepare_body($child, $value);
		$body = $this->children[$child]['body'];
		$size = function_exists('mb_strlen') ? mb_strlen($body) : strlen($body);

		return $size <= 20000;
	}

	/**
	 * Reject a value longer than the character limit.
	 *
	 * @param string $value
	 * @param int $length
	 * @return bool
	 */
	public function _validation_max_chars($value, $length)
	{
		$size = function_exists('mb_strlen') ? mb_strlen((string) $value) : strlen((string) $value);

		return $size <= $length;
	}

	/**
	 * Require a category that exists.
	 *
	 * @param string $value
	 * @return bool
	 */
	public function validate_category($value)
	{
		if ($value === '' || $value === null)
		{
			return false;
		}

		return Model_Category::find($value) !== null;
	}

	/**
	 * @param string $relative
	 */
	public static function delete_public_file($relative)
	{
		if ( ! static::is_managed_image($relative))
		{
			return;
		}

		$file = DOCROOT.$relative;
		if (is_file($file))
		{
			unlink($file);
		}
	}

	/**
	 * @param string $relative
	 * @return bool
	 */
	public static function is_managed_image($relative)
	{
		return (bool) preg_match(
			'#^uploads/blogs/(tmp/[a-f0-9]{32}|[1-9][0-9]*/[1-9][0-9]*/(?:[123]|[a-f0-9]{32}))\.[a-z0-9]{1,8}$#',
			(string) $relative
		);
	}

	/**
	 * @param int $child
	 * @param string $value
	 */
	protected function prepare_body($child, $value)
	{
		if ( ! empty($this->children[$child]['prepared']))
		{
			return;
		}

		$child_id = isset($this->children[$child]['id']) ? $this->children[$child]['id'] : null;
		$body = Blog_Html::sanitize((string) $value);
		$body = Blog_Html::filter_images($body, function ($path) use ($child_id) {
			return $this->image_allowed($path, $child_id);
		});

		$this->children[$child]['body'] = $body;
		$this->children[$child]['prepared'] = true;
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

		$validation = \Validation::forge('blog_form');
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

	/**
	 * @return array
	 */
	protected function input()
	{
		$input = array();

		$this->article['title'] = trim((string) \Input::post('title', ''));
		$this->article['category_id'] = (string) \Input::post('category_id', '');
		$input['title'] = $this->article['title'];
		$input['category_id'] = $this->article['category_id'];

		$posted = \Input::post('children', array());
		$children = array();
		foreach ($this->posted_child_indexes() as $child)
		{
			$row = isset($posted[$child]) && is_array($posted[$child]) ? $posted[$child] : array();
			$child_id = isset($row['id']) ? (int) $row['id'] : 0;
			if ( ! in_array($child_id, $this->owned_child_ids, true))
			{
				$child_id = null;
			}

			$body = isset($row['body']) ? (string) $row['body'] : '';
			$children[$child] = array(
				'posted_index' => $child,
				'id' => $child_id,
				'body' => $body,
				'prepared' => false,
			);
			$input['child_'.$child.'_body'] = $body;
		}

		$this->children = $children;
		$input['children_present'] = $children ? '1' : '';

		return $input;
	}

	/**
	 * Posted child indexes from 1 upward, ignoring anything past 20.
	 *
	 * @return array
	 */
	protected function posted_child_indexes()
	{
		$posted = \Input::post('children', array());
		if ( ! is_array($posted))
		{
			return array();
		}

		$indexes = array();
		foreach ($posted as $index => $child)
		{
			$index = (int) $index;
			if ($index >= 1 && $index <= 20 && is_array($child))
			{
				$indexes[] = $index;
			}
		}

		$indexes = array_values(array_unique($indexes));
		sort($indexes);

		return $indexes;
	}

	/**
	 * @param string $relative
	 * @param int|null $child_id
	 * @return bool
	 */
	protected function image_allowed($relative, $child_id)
	{
		if ( ! static::is_managed_image($relative) || ! is_file(DOCROOT.$relative))
		{
			return false;
		}

		if (strpos($relative, 'uploads/blogs/tmp/') === 0)
		{
			return true;
		}

		return $this->blog_id
			&& $child_id
			&& in_array((int) $child_id, $this->owned_child_ids, true)
			&& strpos($relative, 'uploads/blogs/'.$this->blog_id.'/'.(int) $child_id.'/') === 0;
	}
}
