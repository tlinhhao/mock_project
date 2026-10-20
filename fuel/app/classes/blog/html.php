<?php

/**
 * Turn one child blog into safe HTML that can hold text and images together.
 */
class Blog_Html
{
	/**
	 * @param string $html
	 * @return string
	 */
	public static function sanitize($html)
	{
		$html = (string) $html;
		if (trim($html) === '')
		{
			return '';
		}

		$previous = libxml_use_internal_errors(true);
		$document = new \DOMDocument('1.0', 'UTF-8');
		$flags = 0;
		if (defined('LIBXML_HTML_NOIMPLIED'))
		{
			$flags |= LIBXML_HTML_NOIMPLIED;
		}
		if (defined('LIBXML_HTML_NODEFDTD'))
		{
			$flags |= LIBXML_HTML_NODEFDTD;
		}

		$document->loadHTML('<?xml encoding="UTF-8"><div id="blog-root">'.$html.'</div>', $flags);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$root = null;
		foreach ($document->getElementsByTagName('div') as $div)
		{
			if ($div->getAttribute('id') === 'blog-root')
			{
				$root = $div;
				break;
			}
		}

		if ($root === null)
		{
			return '';
		}

		static::clean_children($root);

		$result = '';
		foreach ($root->childNodes as $child)
		{
			$result .= $document->saveHTML($child);
		}

		return trim($result);
	}

	/**
	 * Drop images the callback rejects.
	 *
	 * @param string $html
	 * @param callable $allows
	 * @return string
	 */
	public static function filter_images($html, $allows)
	{
		return preg_replace_callback('/<img\b[^>]*>/i', function ($match) use ($allows) {
			$path = static::src_path($match[0]);
			if ($path === null || ! $allows($path))
			{
				return '';
			}

			return '<img src="/'.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'" alt="">';
		}, (string) $html);
	}

	/**
	 * @param string $html
	 * @param array $map Relative path => relative path
	 * @return string
	 */
	public static function rewrite_images($html, array $map)
	{
		if ($map === array())
		{
			return (string) $html;
		}

		return preg_replace_callback('/<img\b[^>]*>/i', function ($match) use ($map) {
			$path = static::src_path($match[0]);
			if ($path !== null && isset($map[$path]))
			{
				$path = $map[$path];
			}
			elseif ($path === null)
			{
				return '';
			}

			return '<img src="/'.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'" alt="">';
		}, (string) $html);
	}

	/**
	 * @param string $html
	 * @return array
	 */
	public static function image_paths($html)
	{
		$paths = array();
		if ( ! preg_match_all('/<img\b[^>]*>/i', (string) $html, $matches))
		{
			return $paths;
		}

		foreach ($matches[0] as $tag)
		{
			$path = static::src_path($tag);
			if ($path !== null)
			{
				$paths[] = $path;
			}
		}

		return $paths;
	}

	/**
	 * @param string $html
	 * @return bool
	 */
	public static function is_empty($html)
	{
		return static::plain_text($html) === '' && static::image_paths($html) === array();
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public static function plain_text($html)
	{
		$text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim((string) $text);
	}

	/**
	 * First heading in the child article, or a short excerpt of its text.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function heading($html)
	{
		$text = '';
		if (preg_match('/<h[23][^>]*>(.*?)<\/h[23]>/is', (string) $html, $match))
		{
			$text = static::plain_text($match[1]);
		}

		if ($text === '')
		{
			$text = static::plain_text($html);
		}

		return static::shorten($text, 90);
	}

	/**
	 * @param string $text
	 * @param int $length
	 * @return string
	 */
	protected static function shorten($text, $length)
	{
		$text = trim((string) $text);
		if ($text === '')
		{
			return '';
		}

		$size = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
		if ($size <= $length)
		{
			return $text;
		}

		$cut = function_exists('mb_substr') ? mb_substr($text, 0, $length, 'UTF-8') : substr($text, 0, $length);

		return rtrim($cut).'…';
	}

	/**
	 * Build editor HTML from the rows saved for one child blog.
	 *
	 * @param array $contents
	 * @return string
	 */
	public static function from_contents($contents)
	{
		$pieces = array();
		foreach ($contents as $content)
		{
			$pieces[] = static::piece((string) $content->body, (string) $content->image);
		}

		return static::sanitize(implode('', $pieces));
	}

	/**
	 * Build editor HTML from the older three-part draft shape.
	 *
	 * @param array $sections
	 * @return string
	 */
	public static function from_sections(array $sections)
	{
		$pieces = array();
		foreach ($sections as $section)
		{
			if ( ! is_array($section))
			{
				continue;
			}

			$body = isset($section['body']) ? (string) $section['body'] : '';
			$image = isset($section['image']) ? (string) $section['image'] : '';
			$pieces[] = static::piece($body, $image);
		}

		return static::sanitize(implode('', $pieces));
	}

	/**
	 * @param string $body
	 * @param string $image
	 * @return string
	 */
	protected static function piece($body, $image)
	{
		$body = trim($body);
		$image = trim($image);
		$html = '';

		if (static::looks_like_markup($body))
		{
			$html .= $body;
			if ($image !== '' && strpos($body, $image) === false)
			{
				$html .= static::image_tag($image);
			}

			return $html;
		}

		if ($image !== '')
		{
			$html .= static::image_tag($image);
		}

		if ($body !== '')
		{
			$html .= '<p>'.nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'), false).'</p>';
		}

		return $html;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected static function image_tag($path)
	{
		if ( ! Validate_Blog::is_managed_image($path))
		{
			return '';
		}

		return '<p><img src="/'.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'" alt=""></p>';
	}

	/**
	 * @param string $body
	 * @return bool
	 */
	protected static function looks_like_markup($body)
	{
		return (bool) preg_match('/<(p|img|h2|h3|ul|ol|blockquote|div|br)\b/i', $body);
	}

	/**
	 * @param string $tag
	 * @return string|null
	 */
	protected static function src_path($tag)
	{
		if ( ! preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/i', $tag, $match))
		{
			return null;
		}

		return static::normalize_src($match[2]);
	}

	/**
	 * @param string $src
	 * @return string|null
	 */
	protected static function normalize_src($src)
	{
		$src = trim(html_entity_decode((string) $src, ENT_QUOTES, 'UTF-8'));
		if ($src === '' || preg_match('/^(javascript|data):/i', $src))
		{
			return null;
		}

		$parts = parse_url($src);
		$path = isset($parts['path']) ? $parts['path'] : $src;
		$path = rawurldecode($path);
		$path = ltrim(str_replace('\\', '/', $path), '/');
		if ($path === '' || strpos($path, '..') !== false)
		{
			return null;
		}

		return $path;
	}

	/**
	 * @param \DOMNode $node
	 */
	protected static function clean_children(\DOMNode $node)
	{
		$allowed = array('p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote', 'img', 'div');
		$drop = array('script', 'style', 'iframe', 'object', 'embed', 'link', 'meta');
		$pending = array();
		foreach ($node->childNodes as $child)
		{
			$pending[] = $child;
		}

		foreach ($pending as $child)
		{
			if ($child->parentNode !== $node)
			{
				continue;
			}

			if ($child->nodeType === XML_TEXT_NODE)
			{
				continue;
			}

			if ($child->nodeType !== XML_ELEMENT_NODE)
			{
				$node->removeChild($child);
				continue;
			}

			$tag = strtolower($child->nodeName);
			if (in_array($tag, $drop, true))
			{
				$node->removeChild($child);
				continue;
			}

			if ( ! in_array($tag, $allowed, true))
			{
				while ($child->firstChild)
				{
					$node->insertBefore($child->firstChild, $child);
				}
				$node->removeChild($child);
				static::clean_children($node);

				return;
			}

			if ($tag === 'img')
			{
				$src = static::normalize_src($child->getAttribute('src'));
				static::strip_attributes($child);
				if ($src === null || ! Validate_Blog::is_managed_image($src))
				{
					$node->removeChild($child);
					continue;
				}

				$child->setAttribute('src', '/'.$src);
				$child->setAttribute('alt', '');
				continue;
			}

			static::strip_attributes($child);
			static::clean_children($child);
		}
	}

	/**
	 * @param \DOMElement $element
	 */
	protected static function strip_attributes(\DOMElement $element)
	{
		$names = array();
		foreach ($element->attributes as $attribute)
		{
			$names[] = $attribute->name;
		}

		foreach ($names as $name)
		{
			$element->removeAttribute($name);
		}
	}
}
