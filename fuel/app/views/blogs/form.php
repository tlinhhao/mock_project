<header class="blog-head">
	<p class="eyebrow">Training</p>
	<h1><?php echo $blog_id ? 'Chỉnh sửa bài viết' : 'Tạo bài blog'; ?></h1>
</header>

<?php echo Form::open(array(
	'action' => 'blogs/confirm',
	'class' => 'blog-form',
	'enctype' => 'multipart/form-data',
	'data-upload-url' => Uri::create('blogs/upload'),
)); ?>
	<?php echo Form::csrf(); ?>
	<?php if ($blog_id): ?>
		<?php echo Form::hidden('blog_id', $blog_id); ?>
	<?php endif; ?>

	<label for="title">Tiêu đề</label>
	<?php echo Form::input('title', $article['title'], array('id' => 'title', 'maxlength' => 200)); ?>
	<?php if ( ! empty($errors['title'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['title']); ?></p>
	<?php endif; ?>

	<label for="category_id">Chuyên mục</label>
	<select id="category_id" name="category_id">
		<option value="">Chọn chuyên mục</option>
		<?php foreach ($categories as $category): ?>
			<option value="<?php echo (int) $category->id; ?>"<?php echo (string) $article['category_id'] === (string) $category->id ? ' selected' : ''; ?>>
				<?php echo \Security::htmlentities($category->name); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php if ( ! empty($errors['category_id'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['category_id']); ?></p>
	<?php endif; ?>

	<?php if ( ! empty($errors['children_form'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['children_form']); ?></p>
	<?php endif; ?>

	<div class="child-list" data-child-list>
		<?php foreach ($children as $child): ?>
			<?php $index = (int) $child['index']; ?>
			<section class="child-blog" data-child id="child-<?php echo $index; ?>">
				<input type="hidden" name="children[<?php echo $index; ?>][id]" value="<?php echo $child['id'] === '' ? '' : (int) $child['id']; ?>">
				<div class="child-head">
					<button type="button" class="danger" data-remove-child>Xóa blog con</button>
				</div>

				<div class="editor" data-editor>
					<div class="editor-bar" role="toolbar" aria-label="Soạn nội dung">
						<button type="button" data-command="bold"><b>B</b></button>
						<button type="button" data-command="italic"><i>I</i></button>
						<button type="button" data-command="insertUnorderedList">Danh sách</button>
						<button type="button" data-image>Ảnh</button>
						<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-image-input>
					</div>
					<div
						id="child_<?php echo $index; ?>_body"
						class="editor-body"
						contenteditable="true"
						role="textbox"
						aria-multiline="true"
						data-editor-body
					><?php echo $child['body']; ?></div>
					<textarea name="children[<?php echo $index; ?>][body]" data-editor-input hidden><?php echo \Security::htmlentities($child['body']); ?></textarea>
					<p class="editor-error" data-editor-error hidden></p>
				</div>
				<?php if ( ! empty($errors['children'][$index]['body'])): ?>
					<p class="field-error"><?php echo \Security::htmlentities($errors['children'][$index]['body']); ?></p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>

	<button type="button" class="button-link" data-add-child>Thêm blog con</button>

	<div class="form-actions">
		<?php echo Form::submit('submit', 'Submit'); ?>
		<a class="button-link secondary" href="<?php echo Uri::create('blogs'); ?>">Danh sách</a>
	</div>
<?php echo Form::close(); ?>

<?php echo Asset::js(array('blog-editor.js', 'blog-children.js')); ?>
