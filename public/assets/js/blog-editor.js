(function () {
	var form = document.querySelector('.blog-form');
	if ( ! form)
	{
		return;
	}

	var uploadUrl = form.getAttribute('data-upload-url');

	function editorFrom(node) {
		return node.closest ? node.closest('[data-editor]') : null;
	}

	function sync(editor) {
		var body = editor.querySelector('[data-editor-body]');
		var input = editor.querySelector('[data-editor-input]');
		if (body && input)
		{
			input.value = body.innerHTML;
		}
	}

	function showError(editor, message) {
		var error = editor.querySelector('[data-editor-error]');
		if ( ! error)
		{
			return;
		}

		error.hidden = message === '';
		error.textContent = message;
	}

	function insertImage(editor, url) {
		var body = editor.querySelector('[data-editor-body]');
		if ( ! body)
		{
			return;
		}

		body.focus();
		var html = '<p><img src="' + url.replace(/"/g, '') + '" alt=""></p>';
		if ( ! document.execCommand('insertHTML', false, html))
		{
			body.insertAdjacentHTML('beforeend', html);
		}

		sync(editor);
	}

	document.addEventListener('mousedown', function (event) {
		var button = event.target.closest ? event.target.closest('[data-editor] button') : null;
		if (button)
		{
			event.preventDefault();
		}
	});

	document.addEventListener('click', function (event) {
		var command = event.target.closest ? event.target.closest('[data-command]') : null;
		if (command)
		{
			var editor = editorFrom(command);
			var body = editor ? editor.querySelector('[data-editor-body]') : null;
			if (body)
			{
				body.focus();
				document.execCommand(command.getAttribute('data-command'), false, null);
				sync(editor);
			}

			return;
		}

		var imageButton = event.target.closest ? event.target.closest('[data-image]') : null;
		if ( ! imageButton)
		{
			return;
		}

		var imageEditor = editorFrom(imageButton);
		var fileInput = imageEditor ? imageEditor.querySelector('[data-image-input]') : null;
		if (fileInput)
		{
			fileInput.click();
		}
	});

	document.addEventListener('input', function (event) {
		var editor = editorFrom(event.target);
		if (editor && event.target.hasAttribute('data-editor-body'))
		{
			sync(editor);
		}
	});

	document.addEventListener('change', function (event) {
		var input = event.target;
		if ( ! input.hasAttribute || ! input.hasAttribute('data-image-input'))
		{
			return;
		}

		var editor = editorFrom(input);
		var file = input.files && input.files[0];
		input.value = '';
		if ( ! editor || ! file || ! uploadUrl)
		{
			return;
		}

		var payload = new FormData();
		payload.append('image', file);
		payload.append('fuel_csrf_token', currentCsrfToken(form));

		showError(editor, '');
		fetch(uploadUrl, {
			method: 'POST',
			body: payload,
			credentials: 'same-origin',
		}).then(function (response) {
			return response.json().then(function (data) {
				if (data.csrf)
				{
					Array.prototype.forEach.call(document.querySelectorAll('input[name="fuel_csrf_token"]'), function (field) {
						field.value = data.csrf;
					});
				}

				if ( ! response.ok || ! data.url)
				{
					throw new Error(data.error || 'Không tải được ảnh.');
				}

				insertImage(editor, data.url);
			});
		}).catch(function (error) {
			showError(editor, error.message || 'Không tải được ảnh.');
		});
	});

	form.addEventListener('submit', function () {
		var token = currentCsrfToken(form);
		Array.prototype.forEach.call(form.querySelectorAll('input[name="fuel_csrf_token"]'), function (field) {
			field.value = token;
		});
		Array.prototype.forEach.call(form.querySelectorAll('[data-editor]'), sync);
	});

	// Fuel rotates the CSRF cookie on every page. A form left open in another
	// tab still has the previous hidden token, so read the cookie at send time.
	function currentCsrfToken(scope) {
		var match = document.cookie.match(/(?:^|;\s*)fuel_csrf_token=([^;]*)/);
		if (match)
		{
			return decodeURIComponent(match[1]);
		}

		var field = scope.querySelector('input[name="fuel_csrf_token"]');
		return field ? field.value : '';
	}
}());
