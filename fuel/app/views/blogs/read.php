<?php
$title = trim((string) $blog->title);
$category = $blog->category ? (string) $blog->category->name : '';
$author = $blog->user ? (string) $blog->user->name : '';
?>
<article class="article">
	<p class="eyebrow"><?php echo $category !== '' ? \Security::htmlentities($category) : 'Bài viết'; ?></p>
	<h1><?php echo \Security::htmlentities($title !== '' ? $title : 'Bài blog #'.$blog->id); ?></h1>
	<p class="article-byline">
		<?php if ($author !== ''): ?>
			<span class="byline-author">Tác giả: <strong><?php echo \Security::htmlentities($author); ?></strong></span>
		<?php endif; ?>
		<time datetime="<?php echo \Security::htmlentities($blog->created); ?>">
			<?php echo ! empty($blog->created) ? date('d/m/Y - H:i', strtotime($blog->created)) : ''; ?>
		</time>
	</p>

	<?php foreach ($blog->children as $child): ?>
		<section class="child-blog">
			<div class="rich-text"><?php echo Blog_Html::from_contents($child->contents); ?></div>
		</section>
	<?php endforeach; ?>
</article>

<div class="list-actions">
	<a class="button-link secondary" href="<?php echo Uri::create('blogs/edit/'.$blog->id); ?>">Chỉnh sửa</a>
	<a class="button-link secondary" href="<?php echo Uri::create('blogs'); ?>">← Danh sách bài viết</a>
</div>
