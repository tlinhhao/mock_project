<?php
$remaining = 0;
foreach ($todos as $item)
{
	if ( ! $item->completed)
	{
		$remaining++;
	}
}
?>
<header>
	<p class="eyebrow">Training</p>
	<h1>Việc cần làm</h1>
</header>

<?php echo Form::open(array('action' => 'todos/add', 'class' => 'todo-form')); ?>
	<?php echo Form::csrf(); ?>
	<?php echo Form::input('title', '', array(
		'placeholder' => 'Thêm một việc...',
		'maxlength' => 200,
		'required' => 'required',
	)); ?>
	<?php echo Form::submit('submit', 'Thêm'); ?>
<?php echo Form::close(); ?>

<p class="status">
	<?php if (count($todos) === 0): ?>
		Chưa có việc nào.
	<?php else: ?>
		<?php echo $remaining; ?> việc chưa xong
	<?php endif; ?>
</p>

<ul class="todo-list">
	<?php foreach ($todos as $item): ?>
		<li class="todo<?php echo $item->completed ? ' completed' : ''; ?>">
			<?php echo Form::open(array('action' => 'todos/toggle/'.$item->id, 'class' => 'toggle-form')); ?>
				<?php echo Form::csrf(); ?>
				<button
					type="submit"
					class="check"
					aria-label="<?php echo ($item->completed ? 'Đánh dấu chưa xong' : 'Đánh dấu hoàn thành').': '.$item->title; ?>"
				><?php echo $item->completed ? '✓' : ''; ?></button>
			<?php echo Form::close(); ?>
			<span><?php echo $item->title; ?></span>
			<?php echo Form::open(array('action' => 'todos/delete/'.$item->id, 'class' => 'delete-form', 'onsubmit' => "return confirm('Xóa việc này?');")); ?>
				<?php echo Form::csrf(); ?>
				<?php echo Form::submit('delete', 'Xóa', array('class' => 'delete')); ?>
			<?php echo Form::close(); ?>
		</li>
	<?php endforeach; ?>
</ul>
