/* Nexvio Wishlist Icon + Wishlist Grid: live count and live removal. */
(function () {
	'use strict';

	var DATA = window.NEXVIO || {};

	function setCount(n) {
		n = parseInt(n, 10) || 0;
		document.querySelectorAll('.nexvio-wli').forEach(function (a) {
			var c = a.querySelector('.nexvio-wli__count');
			a.classList.toggle('has-items', n > 0);
			if (!c) { return; }
			var old = c.textContent;
			c.textContent = n;
			c.classList.toggle('is-hidden', a.getAttribute('data-hide-zero') === '1' && n < 1);
			if (String(old) !== String(n) && n > 0) {
				c.classList.remove('is-bump');
				void c.offsetWidth;
				c.classList.add('is-bump');
			}
		});
	}

	var fetching = false;
	function refreshCount() {
		if (!DATA.ajax || fetching || !document.querySelector('.nexvio-wli')) { return; }
		fetching = true;
		var body = new FormData();
		body.append('action', 'nexvio_wishlist_count');
		fetch(DATA.ajax, { method: 'POST', credentials: 'same-origin', body: body, cache: 'no-store' })
			.then(function (r) { return r.json(); })
			.then(function (res) { if (res && res.success) { setCount(res.data.count); } })
			.catch(function () {})
			.then(function () { fetching = false; });
	}

	/* favorites grid: remove a card when its heart is turned off */
	function removeCard(id) {
		document.querySelectorAll('.nexvio-wlg--live').forEach(function (grid) {
			grid.querySelectorAll('.nexvio-ps__wish[data-id="' + id + '"]').forEach(function (heart) {
				var li = heart.closest('li');
				if (!li) { return; }
				li.classList.add('is-leaving');
				setTimeout(function () {
					li.remove();
					var list = grid.querySelector('ul.nexvio-pg__grid');
					var left = list ? list.querySelectorAll('li').length : 0;
					var count = grid.querySelector('.nexvio-wlg__count');
					if (count) { count.textContent = (grid.getAttribute('data-count-text') || '{n}').replace('{n}', left); }
					if (!left) {
						if (list) { list.hidden = true; }
						var empty = grid.querySelector('.nexvio-wlg__empty');
						if (empty) { empty.hidden = false; }
					}
				}, 260);
			});
		});
	}

	function bind() {
		var $ = window.jQuery;
		if (!$) { return; }
		var $b = $(document.body);
		/* Nexvio hearts (cards, add-to-cart widget) */
		$b.on('nexvio_added_to_wishlist', function (e, id, data) {
			if (data && typeof data.count !== 'undefined') { setCount(data.count); } else { refreshCount(); }
		});
		$b.on('nexvio_removed_from_wishlist', function (e, id, data) {
			if (data && typeof data.count !== 'undefined') { setCount(data.count); } else { refreshCount(); }
			removeCard(id);
		});

	}

	function start() {
		bind();
		var needs = document.querySelector('.nexvio-wli[data-refresh="1"]');
		if (needs) { refreshCount(); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}

	/* back/forward cache: page restored from history, count may be stale */
	window.addEventListener('pageshow', function (e) { if (e.persisted) { refreshCount(); } });
})();
