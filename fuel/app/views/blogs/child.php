<?php
$title = trim((string) $blog->title);
$category = $blog->category ? (string) $blog->category->name : '';
$author = $blog->user ? (string) $blog->user->name : '';
?>
<article class="article">
	<p class="eyebrow"><?php echo $category !== '' ? \Security::htmlentities($category) : 'Bài viết'; ?></p>

	<p class="article-byline">
		<a href="<?php echo Uri::create('blogs/read/'.$blog->id); ?>"><?php echo \Security::htmlentities($title !== '' ? $title : 'Bài blog #'.$blog->id); ?></a>
		<?php if ($author !== ''): ?>
			<span><?php echo \Security::htmlentities($author); ?></span>
		<?php endif; ?>
		<time datetime="<?php echo \Security::htmlentities($blog->created); ?>"><?php echo \Security::htmlentities($blog->created); ?></time>
	</p>

	<?php $child_html = Blog_Html::from_contents($child->contents); ?>
	<?php if ($child_html === ''): ?>
		<p class="hint">Blog con này chưa có nội dung.</p>
	<?php else: ?>
		<div class="rich-text"><?php echo $child_html; ?></div>
	<?php endif; ?>
</article>

<div class="list-actions">
	<a class="button-link secondary" href="<?php echo Uri::create('blogs/edit/'.$blog->id); ?>">Chỉnh sửa</a>
	<a class="button-link secondary" href="<?php echo Uri::create('blogs'); ?>">Danh sách</a>
</div>
