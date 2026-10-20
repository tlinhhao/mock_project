(function () {
	var list = document.querySelector('[data-child-list]');
	var addButton = document.querySelector('[data-add-child]');
	if ( ! list || ! addButton)
	{
		return;
	}

	var maxChildren = 20;

	function cards() {
		return list.querySelectorAll('[data-child]');
	}

	function reindex() {
		var items = cards();
		Array.prototype.forEach.call(items, function (card, offset) {
			var number = String(offset + 1);

			var remove = card.querySelector('[data-remove-child]');
			if (remove)
			{
				remove.hidden = items.length === 1;
			}

			Array.prototype.forEach.call(card.querySelectorAll('[name]'), function (field) {
				field.name = field.name.replace(/children\[\d+\]/, 'children[' + number + ']');
			});

			Array.prototype.forEach.call(card.querySelectorAll('[id]'), function (field) {
				field.id = field.id.replace(/child_\d+_/, 'child_' + number + '_');
			});

			Array.prototype.forEach.call(card.querySelectorAll('label[for]'), function (label) {
				label.htmlFor = label.htmlFor.replace(/child_\d+_/, 'child_' + number + '_');
			});
		});

		addButton.hidden = items.length >= maxChildren;
	}

	function clearCard(card) {
		var idField = card.querySelector('input[name$="[id]"]');
		if (idField)
		{
			idField.value = '';
		}

		var body = card.querySelector('[data-editor-body]');
		if (body)
		{
			body.innerHTML = '';
		}

		var stored = card.querySelector('[data-editor-input]');
		if (stored)
		{
			stored.value = '';
		}

		var error = card.querySelector('[data-editor-error]');
		if (error)
		{
			error.hidden = true;
			error.textContent = '';
		}

		Array.prototype.forEach.call(card.querySelectorAll('.field-error'), function (node) {
			node.parentNode.removeChild(node);
		});
	}

	addButton.addEventListener('click', function () {
		if (cards().length >= maxChildren)
		{
			return;
		}

		var source = list.querySelector('[data-child]');
		var copy = source.cloneNode(true);
		clearCard(copy);
		list.appendChild(copy);
		reindex();
		var body = copy.querySelector('[data-editor-body]');
		if (body)
		{
			body.focus();
		}
	});

	list.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('[data-remove-child]') : null;
		if ( ! button || cards().length < 2)
		{
			return;
		}

		var card = button.closest('[data-child]');
		if (card)
		{
			card.parentNode.removeChild(card);
			reindex();
		}
	});

	var form = list.closest('form');
	if (form)
	{
		form.addEventListener('submit', reindex);
	}

	reindex();
}());
