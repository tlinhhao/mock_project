<header class="blog-head">
	<p class="eyebrow">Training</p>
	<h1><?php echo $blog_id ? 'Xác nhận chỉnh sửa' : 'Xác nhận bài blog'; ?></h1>
</header>

<?php if ($blog_id): ?>
	<p class="hint">Mọi mục đều được liệt kê. Phần không sửa giữ màu đen, phần đã sửa màu đỏ.</p>
	<table class="compare-table">
		<thead>
			<tr>
				<th>Mục</th>
				<th>Cũ</th>
				<th>Mới</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Tiêu đề</th>
				<td class="is-same"><?php echo \Security::htmlentities($article['old_title']); ?></td>
				<td class="<?php echo $article['title_changed'] ? 'is-changed' : 'is-same'; ?>"><?php echo \Security::htmlentities($article['title']); ?></td>
			</tr>
			<tr>
				<th>Chuyên mục</th>
				<td class="is-same"><?php echo \Security::htmlentities($article['old_category_name']); ?></td>
				<td class="<?php echo $article['category_changed'] ? 'is-changed' : 'is-same'; ?>"><?php echo \Security::htmlentities($article['category_name']); ?></td>
			</tr>
			<?php foreach ($children as $child): ?>
				<tr>
					<th>Nội dung <?php echo \Security::htmlentities($child['label']); ?></th>
					<td class="is-same"><div class="rich-text"><?php echo $child['old_body']; ?></div></td>
					<td class="<?php echo $child['body_changed'] ? 'is-changed' : 'is-same'; ?>"><div class="rich-text"><?php echo $child['body']; ?></div></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
<header class="article-preview">
	<p class="eyebrow"><?php echo \Security::htmlentities($article['category_name']); ?></p>
	<h2><?php echo \Security::htmlentities($article['title']); ?></h2>
</header>
<?php foreach ($children as $child): ?>
	<section class="child-blog">
		<h2><?php echo \Security::htmlentities($child['label']); ?></h2>
		<div class="rich-text"><?php echo $child['body']; ?></div>
	</section>
<?php endforeach; ?>
<?php endif; ?>

<?php echo Form::open(array('action' => 'blogs/store', 'class' => 'blog-form')); ?>
	<?php echo Form::csrf(); ?>
	<div class="form-actions">
		<?php echo Form::submit('submit', 'Submit'); ?>
		<a class="button-link secondary" href="<?php echo Uri::create($blog_id ? 'blogs/edit/'.$blog_id.'?draft=1' : 'blogs/create?draft=1'); ?>">Quay lại</a>
	</div>
<?php echo Form::close(); ?>
