<header class="blog-head">
	<p class="eyebrow">Training</p>
	<h1>Bài blog</h1>
	<a class="button-link" href="<?php echo Uri::create('blogs/create'); ?>">Tạo bài blog</a>
</header>

<form class="search-form" method="get" action="<?php echo Uri::create('blogs'); ?>">
	<input type="search" name="q" value="<?php echo \Security::htmlentities($keyword); ?>" placeholder="Tìm theo mã hoặc nội dung">
	<button type="submit">Tìm</button>
</form>

<?php if (count($blogs) === 0): ?>
	<p class="status"><?php echo $keyword === '' ? 'Chưa có bài blog.' : 'Không tìm thấy bài blog.'; ?></p>
<?php else: ?>
	<div class="table-wrap">
		<table class="blog-table">
			<thead>
				<tr>
					<th>Tiêu đề</th>
					<th>Mục trong bài</th>
					<th>Thao tác</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($blogs as $blog): ?>
					<tr>
						<td>
							<a href="<?php echo Uri::create('blogs/read/'.$blog->id); ?>"><?php echo \Security::htmlentities($blog->title !== '' ? $blog->title : 'Bài blog #'.$blog->id); ?></a>
						</td>
						<td>
							<?php if (count($blog->children) === 0): ?>
								<span class="hint">Không có</span>
							<?php else: ?>
								<ul class="child-names">
									<?php foreach ($blog->children as $child): ?>
										<?php $child_label = Blog_Html::heading(Blog_Html::from_contents($child->contents)); ?>
										<li>
											<a href="<?php echo Uri::create('blogs/child/'.$child->id); ?>"><?php echo \Security::htmlentities($child_label !== '' ? $child_label : 'Chưa có tiêu đề'); ?></a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</td>
						<td>
							<div class="list-actions">
								<a class="button-link secondary" href="<?php echo Uri::create('blogs/edit/'.$blog->id); ?>">Chỉnh sửa</a>
								<?php echo Form::open(array('action' => 'blogs/delete/'.$blog->id)); ?>
									<?php echo Form::csrf(); ?>
									<?php echo Form::submit('submit', 'Xóa', array(
										'class' => 'danger',
										'onclick' => "return confirm('Xóa bài blog này?');",
									)); ?>
								<?php echo Form::close(); ?>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php if ($pages > 1): ?>
		<?php
			$query = array();
			if ($keyword !== '')
			{
				$query['q'] = $keyword;
			}
		?>
		<nav class="pager" aria-label="Phân trang">
			<?php if ($page > 1): ?>
				<?php $query['page'] = $page - 1; ?>
				<a href="<?php echo Uri::create('blogs').'?'.http_build_query($query); ?>">Trước</a>
			<?php endif; ?>
			<?php foreach ($pager as $item): ?>
				<?php if ($item['type'] === 'gap'): ?>
					<span class="gap">...</span>
				<?php elseif ($item['current']): ?>
					<span class="is-current" aria-current="page"><?php echo (int) $item['n']; ?></span>
				<?php else: ?>
					<?php $query['page'] = $item['n']; ?>
					<a href="<?php echo Uri::create('blogs').'?'.http_build_query($query); ?>"><?php echo (int) $item['n']; ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ($page < $pages): ?>
				<?php $query['page'] = $page + 1; ?>
				<a href="<?php echo Uri::create('blogs').'?'.http_build_query($query); ?>">Sau</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
<?php endif; ?>
