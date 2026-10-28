/* Nexvio: Grid / List buttons of the Products Archive widget. */
(function () {
	'use strict';
	var KEY = 'nexvio_view';
	var chosen = null; /* choice made on this page, kept when the AJAX filter reloads the products */

	function stored() {
		try { return window.localStorage.getItem(KEY); } catch (e) { return null; }
	}

	function apply(root, view) {
		root.classList.toggle('nexvio-pg--list', view === 'list');
		root.setAttribute('data-view-current', view);
		root.querySelectorAll('.nexvio-pg__view button[data-view]').forEach(function (b) {
			b.setAttribute('aria-pressed', b.getAttribute('data-view') === view ? 'true' : 'false');
		});
	}

	function init(scope) {
		(scope || document).querySelectorAll('.nexvio-pg[data-view-switch="1"]').forEach(function (root) {
			if (root.getAttribute('data-view-ready') === '1') { return; }
			root.setAttribute('data-view-ready', '1');
			var remember = root.getAttribute('data-view-remember') === '1';
			var v = chosen || (remember ? stored() : null);
			if (v === 'grid' || v === 'list') { apply(root, v); }
		});
	}

	document.addEventListener('click', function (e) {
		var b = e.target.closest ? e.target.closest('.nexvio-pg__view button[data-view]') : null;
		if (!b) { return; }
		var root = b.closest('.nexvio-pg');
		if (!root) { return; }
		var v = b.getAttribute('data-view') === 'list' ? 'list' : 'grid';
		chosen = v;
		apply(root, v);
		if (root.getAttribute('data-view-remember') === '1') {
			try { window.localStorage.setItem(KEY, v); } catch (err) { /* private mode */ }
		}
	});

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }

	/* the AJAX filter replaces the products area: apply the view again */
	if (window.MutationObserver) {
		var pending = false;
		new MutationObserver(function () {
			if (pending) { return; }
			pending = true;
			window.requestAnimationFrame(function () { pending = false; init(); });
		}).observe(document.documentElement, { childList: true, subtree: true });
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_grid.default', function ($s) { if ($s && $s[0]) { init($s[0]); } });
		}
	});
})();
