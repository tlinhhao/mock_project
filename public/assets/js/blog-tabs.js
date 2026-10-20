(function () {
	function show(root, id) {
		var tabs = root.querySelectorAll('[data-tab]');
		var panels = root.querySelectorAll('[data-panel]');

		Array.prototype.forEach.call(tabs, function (tab) {
			var active = tab.getAttribute('data-tab') === id;
			tab.classList.toggle('is-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		Array.prototype.forEach.call(panels, function (panel) {
			panel.hidden = panel.getAttribute('data-panel') !== id;
		});
	}

	Array.prototype.forEach.call(document.querySelectorAll('[data-tabs]'), function (root) {
		show(root, root.getAttribute('data-active') || '1');
	});

	document.addEventListener('click', function (event) {
		var tab = event.target.closest ? event.target.closest('[data-tab]') : null;
		if ( ! tab)
		{
			return;
		}

		var root = tab.closest('[data-tabs]');
		if ( ! root)
		{
			return;
		}

		show(root, tab.getAttribute('data-tab'));
	});
}());
