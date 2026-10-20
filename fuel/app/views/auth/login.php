<header>
	<p class="eyebrow">Training</p>
	<h1>Đăng nhập</h1>
</header>

<?php echo Form::open(array('action' => 'login', 'class' => 'auth-form')); ?>
	<?php echo Form::csrf(); ?>
	<?php if ( ! empty($errors['form'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['form']); ?></p>
	<?php endif; ?>

	<label for="email">Email</label>
	<?php echo Form::input('email', $email, array('id' => 'email', 'type' => 'email')); ?>
	<?php if ( ! empty($errors['email'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['email']); ?></p>
	<?php endif; ?>

	<label for="password">Mật khẩu</label>
	<?php echo Form::password('password', '', array('id' => 'password')); ?>
	<?php if ( ! empty($errors['password'])): ?>
		<p class="field-error"><?php echo \Security::htmlentities($errors['password']); ?></p>
	<?php endif; ?>

	<div class="form-actions">
		<?php echo Form::submit('submit', 'Đăng nhập'); ?>
		<a class="button-link secondary" href="<?php echo Uri::create('register'); ?>">Đăng ký</a>
	</div>
<?php echo Form::close(); ?>
