<?php

/**
 * Create and edit a main article made of any number of child blogs.
 */
class Controller_Blogs extends Controller_Base
{
	/**
	 * List post
	 */
	public function action_index()
	{
		$per_page = 3;
		$keyword = trim((string) \Input::get('q', ''));
		if (function_exists('mb_substr'))
		{
			$keyword = mb_substr($keyword, 0, 100);
		}
		else
		{
			$keyword = substr($keyword, 0, 100);
		}

		$ids = null;
		if ($keyword !== '')
		{
			$ids = $this->blog_ids_for_search($keyword);
		}

		if ($ids === array())
		{
			$total = 0;
			$blogs = array();
		}
		else
		{
			$count = Model_Blog::query();
			$list = Model_Blog::query()->order_by('created', 'desc');
			if ($ids !== null)
			{
				$count->where('id', 'in', $ids);
				$list->where('id', 'in', $ids);
			}

			$total = (int) $count->count();
			$pages = max(1, (int) ceil($total / $per_page));
			$page = (int) \Input::get('page', 1);
			if ($page < 1)
			{
				$page = 1;
			}
			if ($page > $pages)
			{
				$page = $pages;
			}

			$blogs = $list
				->rows_limit($per_page)
				->rows_offset(($page - 1) * $per_page)
				->get();
		}

		if ($total === 0)
		{
			$page = 1;
			$pages = 0;
		}
		else
		{
			$pages = max(1, (int) ceil($total / $per_page));
			if ( ! isset($page))
			{
				$page = 1;
			}
		}

		$data = array(
			'blogs' => $blogs,
			'page' => $page,
			'pages' => $pages,
			'keyword' => $keyword,
			'pager' => $this->pager_items($page, $pages),
		);

		$this->template->title = 'Bài blog';
		$this->template->set_safe('content', \View::forge('blogs/index', $data, false));
	}

	/**
	 * Newspaper-style reading page for one post.
	 *
	 * @param int|string|null $id Blog id
	 */
	public function action_read($id = null)
	{
		$blog = $this->find_blog($id);
		$title = trim((string) $blog->title);

		$this->template->title = $title !== '' ? $title : 'Bài blog #'.$blog->id;
		$this->template->set_safe('content', \View::forge('blogs/read', array(
			'blog' => $blog,
		), false));
	}

	/**
	 * Detail screen for one child blog.
	 *
	 * @param int|string|null $id Child blog id
	 */
	public function action_child($id = null)
	{
		$child = Model_Blog_Child::find($id);
		if ($child === null)
		{
			throw new \HttpNotFoundException();
		}

		$blog = $this->find_blog($child->blog_id);
		$current = null;
		foreach ($blog->children as $item)
		{
			if ((int) $item->id === (int) $child->id)
			{
				$current = $item;
				break;
			}
		}

		if ($current === null)
		{
			throw new \HttpNotFoundException();
		}

		$this->template->title = 'Blog con '.(int) $current->position;
		$this->template->set_safe('content', \View::forge('blogs/child', array(
			'blog' => $blog,
			'child' => $current,
		), false));
	}

	/**
	 * Show one blog_content row.
	 *
	 * @param int|string|null $id Content id
	 */
	public function action_content($id = null)
	{
		$content = Model_Blog_Content::find($id, array(
			'related' => array('child'),
		));
		if ($content === null || $content->child === null)
		{
			throw new \HttpNotFoundException();
		}

		$data = array(
			'blog' => $this->find_blog($content->child->blog_id),
			'child' => $content->child,
			'content' => $content,
		);

		$this->template->title = 'Chi tiết nội dung';
		$this->template->set_safe('content', \View::forge('blogs/content', $data, false));
	}

	/**
	 * Remove one blog and its images.
	 *
	 * @param int|string|null $id Blog id
	 */
	public function action_delete($id = null)
	{
		$this->require_post('blogs');

		$blog = $this->find_blog($id);
		$draft = $this->draft();
		if ($draft !== null && (int) $draft['blog_id'] === (int) $blog->id)
		{
			\Session::delete('blog_draft');
		}

		$this->delete_blog($blog);
		\Session::set_flash('success', 'Đã xóa bài blog.');
		\Response::redirect('blogs');
	}

	/**
	 * Store one image from the rich-text editor and return its public URL.
	 *
	 * @return \Response
	 */
	public function action_upload()
	{
		if (\Input::method() !== 'POST' || ! \Security::check_token())
		{
			return $this->upload_response(array('error' => 'Không tải được ảnh.'), 400);
		}

		if ( ! isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)
		{
			return $this->upload_response(array('error' => 'Không tải được ảnh.'), 400);
		}

		$tmp_name = $_FILES['image']['tmp_name'];
		if ( ! is_uploaded_file($tmp_name))
		{
			return $this->upload_response(array('error' => 'Không tải được ảnh.'), 400);
		}

		if (isset($_FILES['image']['size']) && (int) $_FILES['image']['size'] > 5 * 1024 * 1024)
		{
			return $this->upload_response(array('error' => 'Ảnh tối đa 5 MB.'), 400);
		}

		$info = @getimagesize($tmp_name);
		$types = array(
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
		);
		if (defined('IMAGETYPE_WEBP'))
		{
			$types[IMAGETYPE_WEBP] = 'webp';
		}

		if ($info === false || ! isset($types[$info[2]]))
		{
			return $this->upload_response(array('error' => 'Chỉ nhận ảnh JPEG, PNG, GIF hoặc WebP.'), 400);
		}

		$directory = DOCROOT.'uploads/blogs/tmp';
		if ( ! is_dir($directory) && ! mkdir($directory, 0775, true))
		{
			return $this->upload_response(array('error' => 'Không tải được ảnh.'), 500);
		}

		$relative = 'uploads/blogs/tmp/'.bin2hex(random_bytes(16)).'.'.$types[$info[2]];
		if ( ! move_uploaded_file($tmp_name, DOCROOT.$relative))
		{
			return $this->upload_response(array('error' => 'Không tải được ảnh.'), 500);
		}

		return $this->upload_response(array(
			'url' => '/'.$relative,
			'csrf' => \Security::fetch_token(),
		), 200);
	}

	/**
	 * @param array $payload
	 * @param int $status
	 * @return \Response
	 */
	protected function upload_response(array $payload, $status)
	{
		return \Response::forge(
			json_encode($payload),
			$status,
			array('Content-Type' => 'application/json')
		);
	}


	public function action_create()
	{
		$draft = $this->draft();
		if (\Input::get('draft') && $draft)
		{
			if ( ! empty($draft['blog_id']))
			{
				\Response::redirect('blogs/edit/'.$draft['blog_id'].'?draft=1');
			}

			$this->render_form('Tạo bài blog', null, $draft['children'], $draft['article'], array(), 1);
			return;
		}

		\Session::delete('blog_draft');
		$this->render_form('Tạo bài blog', null, $this->empty_children(), $this->empty_article(), array(), 1);
	}

	/**
	 * Edit form loaded from the database, or from the confirm draft.
	 *
	 * @param int|string|null $id Blog id
	 */
	public function action_edit($id = null)
	{
		$blog = $this->find_blog($id);
		$draft = $this->draft();
		$children = $this->children_from_blog($blog);
		$article = $this->article_from_blog($blog);

		if (\Input::get('draft') && $draft && (int) $draft['blog_id'] === (int) $blog->id)
		{
			$children = $draft['children'];
			$article = $draft['article'];
		}

		$active = (int) \Input::get('child', 1);
		if ($active < 1)
		{
			$active = 1;
		}

		$this->render_form('Chỉnh sửa bài viết', (int) $blog->id, $children, $article, array(), $active);
	}

	/**
	 * Accept the form into a draft, then show the confirm screen.
	 */
	public function action_confirm()
	{
		if (\Input::method() === 'POST' && $this->accept_form())
		{
			return;
		}

		$draft = $this->draft();
		if ($draft === null)
		{
			\Response::redirect('blogs/create');
		}

		$original = null;
		$original_article = null;
		if ( ! empty($draft['blog_id']))
		{
			$stored = $this->find_blog($draft['blog_id']);
			$original = $this->children_from_blog($stored);
			$original_article = $this->article_from_blog($stored);
		}

		$data = array(
			'blog_id' => $draft['blog_id'],
			'children' => $this->child_panels($draft['children'], $original),
			'article' => $this->article_changes($draft['article'], $original_article),
			'active' => 1,
		);

		$this->template->title = $draft['blog_id'] ? 'Xác nhận chỉnh sửa' : 'Xác nhận bài blog';
		$this->template->set_safe('content', \View::forge('blogs/confirm', $data, false));
	}

	/**
	 * Persist the confirmed draft.
	 */
	public function action_store()
	{
		$this->require_post('blogs');

		$draft = $this->draft();
		if ($draft === null)
		{
			\Session::set_flash('error', 'Không lưu được. Hãy thử lại.');
			\Response::redirect('blogs');
		}

		if ( ! empty($draft['blog_id']))
		{
			$blog = $this->find_blog($draft['blog_id']);
		}
		else
		{
			$blog = Model_Blog::forge();
			$blog->user_id = $draft['user_id'];
		}

		$blog->title = $draft['article']['title'];
		$blog->category_id = $draft['article']['category_id'] === '' ? null : (int) $draft['article']['category_id'];
		$blog->save();
		$this->save_children($blog, $draft['children']);

		\Session::delete('blog_draft');
		\Response::redirect('blogs/complete');
	}

	/**
	 * Success screen after a confirmed save.
	 */
	public function action_complete()
	{
		$this->template->title = 'Lưu thành công bài blog';
		$this->template->set_safe('content', \View::forge('blogs/complete'));
	}

	/**
	 * Validate the posted tabs and keep the result in the session.
	 */
	protected function accept_form()
	{
		$this->require_post('blogs/create');

		$blog_id = \Input::post('blog_id');
		$blog = null;
		$children = $this->empty_children();

		if ($blog_id !== null && $blog_id !== '')
		{
			$blog = $this->find_blog($blog_id);
			$children = $this->children_from_blog($blog);
		}

		$request = Validate_Blog::forge($blog ? (int) $blog->id : null, $children);
		if ($request->fails())
		{
			$this->render_form(
				$blog ? 'Chỉnh sửa bài viết' : 'Tạo bài blog',
				$blog ? (int) $blog->id : null,
				$request->children(),
				$request->article(),
				$request->errors(),
				$request->first_error()
			);
			return true;
		}

		$article = $request->article();
		\Session::set('blog_draft', array(
			'blog_id' => $blog ? (int) $blog->id : null,
			'user_id' => $blog ? $blog->user_id : ($this->current_user ? (int) $this->current_user->id : null),
			'article' => array(
				'title' => $article['title'],
				'category_id' => $article['category_id'] === '' ? '' : (int) $article['category_id'],
			),
			'children' => $request->children(),
		));
		\Response::redirect('blogs/confirm');
	}

	/**
	 * @param string $title
	 * @param int|null $blog_id
	 * @param array $sections
	 * @param array $errors
	 * @param int $active
	 */
	protected function render_form($title, $blog_id, array $children, array $article, array $errors, $active)
	{
		$data = array(
			'blog_id' => $blog_id,
			'children' => $this->child_panels($children, null),
			'article' => $article,
			'categories' => Model_Category::query()->order_by('name', 'asc')->get(),
			'errors' => $errors,
			'active' => $active,
		);

		$this->template->title = $title;
		$this->template->set_safe('content', \View::forge('blogs/form', $data, false));
	}

	/**
	 * @param array $children
	 * @param array|null $original
	 * @return array
	 */
	protected function child_panels(array $children, $original)
	{
		$groups = array();
		foreach ($children as $index => $child)
		{
			$previous = null;
			if ($original !== null)
			{
				$previous = isset($original[$index]['body']) ? (string) $original[$index]['body'] : '';
			}

			$body = isset($child['body']) ? (string) $child['body'] : '';
			$groups[] = array(
				'index' => (int) $index,
				'id' => empty($child['id']) ? '' : (int) $child['id'],
				'label' => 'Blog con '.$index,
				'body' => $body,
				'old_body' => $previous,
				'body_changed' => $previous !== null && $previous !== $body,
			);
		}

		return $groups;
	}

	/**
	 * @param \Orm\Model $blog
	 * @param array $children
	 */
	protected function save_children($blog, array $children)
	{
		$kept = array();
		$position = 0;
		foreach ($children as $child_data)
		{
			$position++;
			$child = null;
			if ( ! empty($child_data['id']))
			{
				$child = Model_Blog_Child::query()
					->where('id', (int) $child_data['id'])
					->where('blog_id', (int) $blog->id)
					->get_one();
			}

			if ($child === null)
			{
				$child = Model_Blog_Child::forge(array(
					'blog_id' => (int) $blog->id,
				));
			}

			$child->position = $position;
			$child->save();
			$kept[] = (int) $child->id;
			$this->save_child_body($blog, $child, isset($child_data['body']) ? (string) $child_data['body'] : '');
		}

		$existing = Model_Blog_Child::query()->where('blog_id', (int) $blog->id)->get();
		foreach ($existing as $child)
		{
			if ( ! in_array((int) $child->id, $kept, true))
			{
				$this->delete_child($child);
			}
		}

		$blog->save();
	}

	/**
	 * Keep one content row whose HTML holds both the text and the images.
	 *
	 * @param \Orm\Model $blog
	 * @param \Orm\Model $child
	 * @param string $html
	 */
	protected function save_child_body($blog, $child, $html)
	{
		$html = $this->publish_body_images($html, (int) $blog->id, (int) $child->id);
		$content = Model_Blog_Content::query()
			->where('blog_child_id', (int) $child->id)
			->where('position', 1)
			->get_one();

		if ($content === null)
		{
			$content = Model_Blog_Content::forge(array(
				'blog_child_id' => (int) $child->id,
				'position' => 1,
			));
		}

		$content->body = $html;
		$content->image = '';
		$content->save();

		$extras = Model_Blog_Content::query()
			->where('blog_child_id', (int) $child->id)
			->where('position', '!=', 1)
			->get();
		foreach ($extras as $extra)
		{
			$extra->delete();
		}

		$this->delete_unused_child_images((int) $blog->id, (int) $child->id, Blog_Html::image_paths($html));
	}

	/**
	 * Move temporary editor uploads into the child directory and rewrite the HTML.
	 *
	 * @param string $html
	 * @param int $blog_id
	 * @param int $child_id
	 * @return string
	 */
	protected function publish_body_images($html, $blog_id, $child_id)
	{
		$directory = DOCROOT.'uploads/blogs/'.$blog_id.'/'.$child_id;
		$map = array();
		foreach (Blog_Html::image_paths($html) as $path)
		{
			if (strpos($path, 'uploads/blogs/tmp/') !== 0)
			{
				continue;
			}

			if ( ! Validate_Blog::is_managed_image($path) || ! is_file(DOCROOT.$path))
			{
				throw new \RuntimeException('Could not store the blog image.');
			}

			if ( ! is_dir($directory) && ! mkdir($directory, 0775, true))
			{
				throw new \RuntimeException('Could not store the blog image.');
			}

			$extension = pathinfo($path, PATHINFO_EXTENSION);
			$destination = 'uploads/blogs/'.$blog_id.'/'.$child_id.'/'.bin2hex(random_bytes(16)).'.'.$extension;
			if ( ! rename(DOCROOT.$path, DOCROOT.$destination))
			{
				throw new \RuntimeException('Could not store the blog image.');
			}

			$map[$path] = $destination;
		}

		return Blog_Html::rewrite_images($html, $map);
	}

	/**
	 * @param int $blog_id
	 * @param int $child_id
	 * @param array $paths
	 */
	protected function delete_unused_child_images($blog_id, $child_id, array $paths)
	{
		$directory = DOCROOT.'uploads/blogs/'.$blog_id.'/'.$child_id;
		if ( ! is_dir($directory))
		{
			return;
		}

		$kept = array();
		foreach ($paths as $path)
		{
			$kept[basename($path)] = true;
		}

		foreach (scandir($directory) as $item)
		{
			if ($item === '.' || $item === '..' || isset($kept[$item]))
			{
				continue;
			}

			$file = $directory.DIRECTORY_SEPARATOR.$item;
			if (is_file($file))
			{
				unlink($file);
			}
		}
	}

	/**
	 * Delete stored images, then the post and its sections.
	 *
	 * @param \Orm\Model $blog
	 */
	protected function delete_blog($blog)
	{
		foreach ($blog->children as $child)
		{
			$this->delete_child_files($child);
		}

		$directory = DOCROOT.'uploads/blogs/'.(int) $blog->id;
		$blog->delete();
		$this->delete_directory($directory);
	}

	/**
	 * @param \Orm\Model $child
	 */
	protected function delete_child($child)
	{
		$directory = DOCROOT.'uploads/blogs/'.(int) $child->blog_id.'/'.(int) $child->id;
		$this->delete_child_files($child);
		$child->delete();
		$this->delete_directory($directory);
	}

	/**
	 * @param \Orm\Model $child
	 */
	protected function delete_child_files($child)
	{
		foreach ($child->contents as $content)
		{
			Validate_Blog::delete_public_file((string) $content->image);
		}
	}

	/**
	 * @param string $directory
	 */
	protected function delete_directory($directory)
	{
		if ( ! is_dir($directory))
		{
			return;
		}

		foreach (scandir($directory) as $item)
		{
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			$path = $directory.DIRECTORY_SEPARATOR.$item;
			if (is_dir($path))
			{
				$this->delete_directory($path);
			}
			elseif (is_file($path))
			{
				unlink($path);
			}
		}

		rmdir($directory);
	}

	/**
	 * @param int|string $id
	 * @return \Orm\Model
	 */
	/**
	 * Blog ids whose body or id matches the keyword.
	 *
	 * @param string $keyword
	 * @return array
	 */
	protected function blog_ids_for_search($keyword)
	{
		$escaped = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $keyword);
		$rows = \DB::select('blog_children.blog_id')
			->from('blog_contents')
			->join('blog_children')
			->on('blog_contents.blog_child_id', '=', 'blog_children.id')
			->where('blog_contents.body', 'like', '%'.$escaped.'%')
			->execute()
			->as_array();

		$ids = array();
		foreach ($rows as $row)
		{
			$ids[] = (int) $row['blog_id'];
		}

		$matches = \DB::select('id')
			->from('blogs')
			->where('title', 'like', '%'.$escaped.'%')
			->execute()
			->as_array();
		foreach ($matches as $row)
		{
			$ids[] = (int) $row['id'];
		}

		if (preg_match('/^[1-9][0-9]*$/', $keyword))
		{
			$ids[] = (int) $keyword;
		}

		return array_values(array_unique($ids));
	}

	/**
	 * Page numbers with gaps where the list is long.
	 *
	 * @param int $page
	 * @param int $pages
	 * @return array
	 */
	protected function pager_items($page, $pages)
	{
		if ($pages < 2)
		{
			return array();
		}

		$wanted = array(1, $pages, $page - 1, $page, $page + 1);
		$numbers = array();
		foreach ($wanted as $number)
		{
			if ($number >= 1 && $number <= $pages)
			{
				$numbers[$number] = $number;
			}
		}
		sort($numbers);

		$items = array();
		$previous = 0;
		foreach ($numbers as $number)
		{
			if ($previous > 0 && $number - $previous > 1)
			{
				$items[] = array('type' => 'gap');
			}

			$items[] = array(
				'type' => 'page',
				'n' => $number,
				'current' => $number === $page,
			);
			$previous = $number;
		}

		return $items;
	}

	/**
	 * @param int|string $id
	 * @return \Orm\Model
	 */
	protected function find_blog($id)
	{
		$blog = Model_Blog::find($id, array(
			'related' => array(
				'children' => array(
					'related' => array('contents'),
				),
				'user',
				'category',
			),
		));
		if ($blog === null)
		{
			throw new \HttpNotFoundException();
		}

		return $blog;
	}

	/**
	 * @param \Orm\Model $blog
	 * @return array
	 */
	protected function children_from_blog($blog)
	{
		$children = array();
		$index = 0;
		foreach ($blog->children as $child)
		{
			$index++;
			$children[$index] = array(
				'id' => (int) $child->id,
				'body' => Blog_Html::from_contents($child->contents),
			);
		}

		if ($children === array())
		{
			return $this->empty_children();
		}

		return $children;
	}

	/**
	 * @return array
	 */
	protected function empty_children()
	{
		return array(
			1 => array(
				'id' => null,
				'body' => '',
			),
		);
	}

	/**
	 * @return array|null
	 */
	protected function draft()
	{
		$draft = \Session::get('blog_draft');
		if ( ! is_array($draft) || empty($draft['children']) || ! is_array($draft['children']))
		{
			return null;
		}

		$children = array();
		$index = 0;
		foreach ($draft['children'] as $child)
		{
			if ( ! is_array($child))
			{
				return null;
			}

			if (array_key_exists('body', $child))
			{
				$body = Blog_Html::sanitize((string) $child['body']);
			}
			elseif ( ! empty($child['sections']) && is_array($child['sections']))
			{
				$body = Blog_Html::from_sections($child['sections']);
			}
			else
			{
				return null;
			}

			$index++;
			$children[$index] = array(
				'id' => empty($child['id']) ? null : (int) $child['id'],
				'body' => $body,
			);
		}

		if ($children === array())
		{
			return null;
		}

		$article = $this->empty_article();
		if (isset($draft['article']) && is_array($draft['article']))
		{
			$article['title'] = isset($draft['article']['title']) ? (string) $draft['article']['title'] : '';
			$article['category_id'] = isset($draft['article']['category_id']) ? $draft['article']['category_id'] : '';
		}

		return array(
			'blog_id' => empty($draft['blog_id']) ? null : (int) $draft['blog_id'],
			'user_id' => isset($draft['user_id']) ? $draft['user_id'] : null,
			'article' => $article,
			'children' => $children,
		);
	}

	/**
	 * @return array
	 */
	protected function empty_article()
	{
		return array(
			'title' => '',
			'category_id' => '',
		);
	}

	/**
	 * @param \Orm\Model $blog
	 * @return array
	 */
	protected function article_from_blog($blog)
	{
		return array(
			'title' => (string) $blog->title,
			'category_id' => $blog->category_id ? (int) $blog->category_id : '',
		);
	}

	/**
	 * Article fields plus the previous values used by the confirm screen.
	 *
	 * @param array $article
	 * @param array|null $original
	 * @return array
	 */
	protected function article_changes(array $article, $original)
	{
		$category = $article['category_id'] === '' ? null : Model_Category::find($article['category_id']);
		$previous = null;
		if ($original !== null && $original['category_id'] !== '')
		{
			$previous = Model_Category::find($original['category_id']);
		}

		return array(
			'title' => $article['title'],
			'category_name' => $category ? (string) $category->name : '',
			'old_title' => $original ? $original['title'] : '',
			'old_category_name' => $previous ? (string) $previous->name : '',
			'title_changed' => $original !== null && $original['title'] !== $article['title'],
			'category_changed' => $original !== null && (string) $original['category_id'] !== (string) $article['category_id'],
		);
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
