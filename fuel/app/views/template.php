<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $title; ?></title>
	<?php echo Asset::css(array('todo.css', 'blog.css')); ?>
</head>
<body>
	<nav class="app-nav">
		<?php if ( ! empty($current_user)): ?>
			<a href="<?php echo Uri::create('/'); ?>">Việc cần làm</a>
			<a href="<?php echo Uri::create('blogs'); ?>">Blog</a>
			<span class="nav-user"><?php echo \Security::htmlentities($current_user->name); ?></span>
			<?php echo Form::open(array('action' => 'logout', 'class' => 'nav-logout')); ?>
				<?php echo Form::csrf(); ?>
				<?php echo Form::submit('submit', 'Đăng xuất'); ?>
			<?php echo Form::close(); ?>
		<?php else: ?>
			<a href="<?php echo Uri::create('login'); ?>">Đăng nhập</a>
			<a href="<?php echo Uri::create('register'); ?>">Đăng ký</a>
		<?php endif; ?>
	</nav>
	<main class="app">
		<?php if ($message = Session::get_flash('success')): ?>
			<p class="message success"><?php echo $message; ?></p>
		<?php endif; ?>
		<?php if ($message = Session::get_flash('error')): ?>
			<p class="message error"><?php echo $message; ?></p>
		<?php endif; ?>
		<?php echo $content; ?>
	</main>
	<script>
		document.addEventListener('submit', function (event) {
			var match = document.cookie.match(/(?:^|;\s*)fuel_csrf_token=([^;]*)/);
			var form = event.target;
			if ( ! match || ! form || ! form.querySelectorAll)
			{
				return;
			}

			var token = decodeURIComponent(match[1]);
			Array.prototype.forEach.call(form.querySelectorAll('input[name="fuel_csrf_token"]'), function (field) {
				field.value = token;
			});
		}, true);
	</script>
</body>
</html>
