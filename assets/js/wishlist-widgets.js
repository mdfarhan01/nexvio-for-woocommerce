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

	/* The favorites page can come from a page cache (plugin, host or CDN) and show an old list. When it
	   differs from the real list, ask the server for a fresh copy of the grid itself (that request is
	   never cached) and put it in place of the old one. Loading the page again is only the last resort. */
	var refreshing = false, lastSig = '';

	function reloadOnce() {
		var tries = 0;
		try { tries = parseInt(window.sessionStorage.getItem('nexvio_wl_tries') || '0', 10) || 0; } catch (e) {}
		if (tries >= 2) { return; }
		try { window.sessionStorage.setItem('nexvio_wl_tries', String(tries + 1)); } catch (e) {}
		var url = new URL(window.location.href);
		url.searchParams.set('nexvio_wl', String(Date.now()));
		window.location.replace(url.toString());
	}

	function refreshGrids() {
		var grids = Array.prototype.slice.call(document.querySelectorAll('.nexvio-wlg[data-nexvio-post][data-nexvio-el]'));
		if (!grids.length || !DATA.ajax || !window.DOMParser) { reloadOnce(); return; }
		if (refreshing) { return; }
		refreshing = true;
		Promise.all(grids.map(function (grid) {
			var body = new FormData();
			body.append('action', 'nexvio_wishlist_grid');
			body.append('post', grid.getAttribute('data-nexvio-post'));
			body.append('el', grid.getAttribute('data-nexvio-el'));
			return fetch(DATA.ajax, { method: 'POST', credentials: 'same-origin', body: body, cache: 'no-store' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success || !res.data || !res.data.html) { throw new Error('grid'); }
					var doc = new DOMParser().parseFromString(res.data.html, 'text/html');
					var fresh = doc.querySelector('.nexvio-wlg');
					if (!fresh) { throw new Error('grid'); }
					grid.replaceWith(document.importNode(fresh, true));
				});
		})).then(function () {
			try { window.sessionStorage.removeItem('nexvio_wl_tries'); } catch (e) {}
		}).catch(function () {
			reloadOnce();
		}).then(function () { refreshing = false; });
	}

	function checkGrid(ids) {
		var grids = document.querySelectorAll('.nexvio-wlg');
		if (!grids.length || !Array.isArray(ids)) { return; }
		var shown = [], limited = false;
		grids.forEach(function (grid) {
			if (parseInt(grid.getAttribute('data-limit'), 10) > 0) { limited = true; }
			grid.querySelectorAll('.nexvio-ps__wish[data-id]').forEach(function (h) {
				if (h.closest('.is-leaving')) { return; } /* a card being removed right now */
				shown.push(String(h.getAttribute('data-id')));
			});
		});
		var real = ids.map(String);
		var extra = shown.some(function (i) { return real.indexOf(i) === -1; });
		var missing = !limited && real.some(function (i) { return shown.indexOf(i) === -1; });
		if (!extra && !missing) {
			try { window.sessionStorage.removeItem('nexvio_wl_tries'); } catch (e) {}
			return;
		}
		/* a product the grid hides on purpose (for example out of stock) must not trigger this on every load */
		var sig = real.slice().sort().join(',');
		if (sig === lastSig) { return; }
		lastSig = sig;
		refreshGrids();
	}

	document.addEventListener('nexvio:wishlist-synced', function (e) {
		var d = e.detail || {};
		if (typeof d.count !== 'undefined') { setCount(d.count); }
		checkGrid(d.ids);
	});

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
