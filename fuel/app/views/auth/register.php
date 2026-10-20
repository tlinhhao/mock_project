<header>
	<p class="eyebrow">Training</p>
	<h1>Đăng ký</h1>
</header>

<?php echo Form::open(array('action' => 'register', 'class' => 'auth-form')); ?>
	<?php echo Form::csrf(); ?>

	<label for="name">Tên</label>
	<?php echo Form::input('name', $values['name'], array('id' => 'name')); ?>
	<?php if ( ! empty($errors['name'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['name']); ?></p>
	<?php endif; ?>

	<label for="email">Email</label>
	<?php echo Form::input('email', $values['email'], array('id' => 'email', 'type' => 'email')); ?>
	<?php if ( ! empty($errors['email'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['email']); ?></p>
	<?php endif; ?>

	<label for="password">Mật khẩu</label>
	<?php echo Form::password('password', '', array('id' => 'password')); ?>
	<?php if ( ! empty($errors['password'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['password']); ?></p>
	<?php endif; ?>

	<label for="password_confirm">Nhập lại mật khẩu</label>
	<?php echo Form::password('password_confirm', '', array('id' => 'password_confirm')); ?>
	<?php if ( ! empty($errors['password_confirm'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['password_confirm']); ?></p>
	<?php endif; ?>

	<div class="form-actions">
		<?php echo Form::submit('submit', 'Đăng ký'); ?>
		<a class="button-link secondary" href="<?php echo Uri::create('login'); ?>">Đăng nhập</a>
	</div>
<?php echo Form::close(); ?>
