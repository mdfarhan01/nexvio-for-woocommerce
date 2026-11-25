/* Nexvio - admin dashboard */
(function () {
	'use strict';
	var D = window.NEXVIO_ADMIN || {};
	/* documentation: highlight the section in view */
	var toc = document.querySelectorAll('.nexvio-docs__toc a');
	if (toc.length && window.IntersectionObserver) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					toc.forEach(function (a) { a.classList.toggle('is-current', a.getAttribute('href') === '#' + en.target.id); });
				}
			});
		}, { rootMargin: '-120px 0px -60% 0px' });
		document.querySelectorAll('.nexvio-doc').forEach(function (sec) { io.observe(sec); });
	}

	/* Add to Cart page: live preview */
	(function () {
		var form = document.getElementById('nexvio-cart-form');
		var cart = document.getElementById('nexvio-preview-cart');
		if (!form || !cart) { return; }
		var fc = document.getElementById('nexvio-preview-fc');
		var app = document.getElementById('nexvio-app');

		function contrast(hex) {
			hex = (hex || '').replace('#', '');
			if (hex.length === 3) { hex = hex.replace(/(.)/g, '$1$1'); }
			if (hex.length !== 6) { return '#ffffff'; }
			var r = parseInt(hex.substr(0, 2), 16), g = parseInt(hex.substr(2, 2), 16), b = parseInt(hex.substr(4, 2), 16);
			return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.6 ? '#111111' : '#ffffff';
		}
		function setMain(hex) {
			if (!/^#([0-9a-f]{3}){1,2}$/i.test(hex || '')) { return; }
			[cart, fc].forEach(function (el) {
				if (!el) { return; }
				el.style.setProperty('--wsc-mc', hex);
				el.style.setProperty('--wsc-mc-text', contrast(hex));
			});
			if (app) { app.style.setProperty('--nexvio-main', hex); }
			form.querySelectorAll('.nexvio-swatch').forEach(function (s) { s.classList.toggle('is-active', s.getAttribute('data-colour').toLowerCase() === hex.toLowerCase()); });
		}
		function setStyle(v) {
			cart.classList.toggle('nexvio-sc--dark', v === 'dark');
			cart.classList.toggle('nexvio-sc--light', v !== 'dark');
			if (fc) { fc.classList.toggle('nexvio-fc--dark', v === 'dark'); fc.classList.toggle('nexvio-fc--light', v !== 'dark'); }
		}

		var colour = form.querySelector('input.nexvio-color');
		var $ = window.jQuery;
		if ($ && $.fn.wpColorPicker && colour) {
			$(colour).wpColorPicker({
				change: function (e, ui) { setMain(ui.color.toString()); },
				clear: function () { setMain(colour.getAttribute('data-default-color')); }
			});
		} else if (colour) {
			colour.addEventListener('input', function () { setMain(colour.value); });
		}
		form.querySelectorAll('.nexvio-swatch').forEach(function (s) {
			s.addEventListener('click', function () {
				var hex = s.getAttribute('data-colour');
				if ($ && $.fn.wpColorPicker && colour) { $(colour).wpColorPicker('color', hex); } else if (colour) { colour.value = hex; }
				setMain(hex);
			});
		});
		form.querySelectorAll('input[name="nexvio_cart[preset]"]').forEach(function (r) {
			r.addEventListener('change', function () { if (r.checked) { setStyle(r.value); } });
		});
		form.querySelectorAll('[data-preview-input]').forEach(function (inp) {
			var target = cart.querySelector('[data-preview="' + inp.getAttribute('data-preview-input') + '"]');
			if (!target) { return; }
			inp.addEventListener('input', function () { target.textContent = inp.value.trim() || inp.getAttribute('data-auto') || ''; });
		});
		form.querySelectorAll('[data-preview-toggle]').forEach(function (cb) {
			var target = cart.querySelector('[data-preview-show="' + cb.getAttribute('data-preview-toggle') + '"]');
			if (!target) { return; }
			var sync = function () { target.hidden = !cb.checked; };
			cb.addEventListener('change', sync); sync();
		});
		if (colour) { setMain(colour.value); }

		/* floating icon: the picked icon shows in the live preview */
		var iconBox = fc ? fc.querySelector('.nexvio-fc__icon') : null;
		function renderIcon() {
			if (!iconBox) { return; }
			var r = form.querySelector('input[name="nexvio_cart[fl_icon]"]:checked');
			if (!r) { return; }
			if (r.value === 'custom') {
				var u = form.querySelector('[data-nexvio-media-url]');
				var url = u ? u.value.trim() : '';
				if (url) {
					var img = document.createElement('img');
					img.src = url; img.alt = '';
					iconBox.innerHTML = ''; iconBox.appendChild(img);
					return;
				}
				r = form.querySelector('input[name="nexvio_cart[fl_icon]"][value="basket"]');
				if (!r) { return; }
			}
			var holder = r.parentNode.querySelector('.nexvio-iconpick__svg');
			if (holder) { iconBox.innerHTML = holder.innerHTML; }
		}
		form.querySelectorAll('input[name="nexvio_cart[fl_icon]"]').forEach(function (r) { r.addEventListener('change', renderIcon); });

		/* media library picker for the custom icon */
		form.querySelectorAll('[data-nexvio-media]').forEach(function (box) {
			var url = box.querySelector('[data-nexvio-media-url]');
			var thumb = box.querySelector('.nexvio-media__thumb');
			var pick = box.querySelector('[data-nexvio-media-pick]');
			var clear = box.querySelector('[data-nexvio-media-clear]');
			var label = box.querySelector('[data-nexvio-media-label]');
			var tile = form.querySelector('input[name="nexvio_cart[fl_icon]"][value="custom"]');
			var tileGlyph = tile ? tile.parentNode.querySelector('.nexvio-iconpick__svg') : null;
			var frame = null;
			function sync() {
				var v = url.value.trim();
				thumb.innerHTML = '';
				if (tileGlyph) { tileGlyph.innerHTML = ''; }
				if (v) {
					var i = document.createElement('img'); i.src = v; i.alt = ''; thumb.appendChild(i);
					if (tileGlyph) { var t = document.createElement('img'); t.src = v; t.alt = ''; tileGlyph.appendChild(t); }
				} else {
					var d = document.createElement('span'); d.className = 'dashicons dashicons-format-image'; thumb.appendChild(d);
					if (tileGlyph) { var p2 = document.createElement('span'); p2.className = 'dashicons dashicons-plus-alt2'; tileGlyph.appendChild(p2); }
				}
				if (label) { label.textContent = pick.getAttribute(v ? 'data-label-change' : 'data-label-select'); }
				if (clear) { clear.hidden = !v; }
				renderIcon();
			}
			/* choosing "Custom icon" with nothing picked yet opens the Media Library straight away */
			if (tile) { tile.addEventListener('change', function () { if (tile.checked && !url.value.trim()) { pick.click(); } }); }
			url.addEventListener('input', sync);
			clear.addEventListener('click', function () { url.value = ''; sync(); });
			pick.addEventListener('click', function (e) {
				e.preventDefault();
				if (!window.wp || !window.wp.media) { var dt = box.querySelector('details'); if (dt) { dt.open = true; } url.focus(); return; }
				if (!frame) {
					frame = window.wp.media({ title: pick.getAttribute('data-title'), button: { text: pick.getAttribute('data-button') }, library: { type: 'image' }, multiple: false });
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						url.value = a.url || '';
						sync();
					});
				}
				frame.open();
			});
		});

		/* pulse animation on the preview button */
		var pulse = form.querySelector('input[name="nexvio_cart[fl_pulse]"]');
		if (pulse && fc) { pulse.addEventListener('change', function () { fc.classList.toggle('nexvio-fc--pulse', pulse.checked); }); }

		/* rows that depend on another field (custom image, custom fonts) */
		function syncShowIf() {
			form.querySelectorAll('[data-show-if]').forEach(function (row) {
				var p = row.getAttribute('data-show-if').split('=');
				var el = form.querySelector('input[name="nexvio_cart[' + p[0] + ']"]:checked') || form.querySelector('select[name="nexvio_cart[' + p[0] + ']"]');
				row.hidden = !el || el.value !== p[1];
			});
		}
		form.addEventListener('change', syncShowIf);
		syncShowIf();

		/* custom fonts: preview them straight away (site fonts only exist on the front end) */
		function fontPreview() {
			[['heading', '--wsc-hf', 'Georgia,serif'], ['body', '--wsc-bf', 'sans-serif']].forEach(function (f) {
				var sel = form.querySelector('select[name="nexvio_cart[font_' + f[0] + ']"]');
				var inp = form.querySelector('input[name="nexvio_cart[font_' + f[0] + '_custom]"]');
				if (!sel || !inp) { return; }
				var names = inp.value.split(',').map(function (n) { return n.replace(/[^\w \-]/g, '').trim(); }).filter(Boolean).map(function (n) { return '"' + n + '"'; });
				if (sel.value === 'custom' && names.length) { cart.style.setProperty(f[1], names.join(',') + ',' + f[2]); }
				else { cart.style.removeProperty(f[1]); }
			});
		}
		form.querySelectorAll('[data-nexvio-font], select[name^="nexvio_cart[font_"]').forEach(function (el) {
			el.addEventListener('input', fontPreview); el.addEventListener('change', fontPreview);
		});
	})();

	var grid = document.getElementById('nexvio-grid');
	if (!grid) { return; }

	var search = document.getElementById('nexvio-search');
	var empty = document.querySelector('.nexvio-empty');
	var toastEl = document.querySelector('.nexvio-toast');
	var filter = 'all';

	function toast(msg, isError) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		toastEl.classList.toggle('is-error', !!isError);
		toastEl.classList.add('is-on');
		clearTimeout(toastEl._t);
		toastEl._t = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
	}

	function apply() {
		var q = (search && search.value || '').trim().toLowerCase();
		var shown = 0;
		grid.querySelectorAll('.nexvio-card').forEach(function (c) {
			var okStatus = filter === 'all' || c.getAttribute('data-status') === filter;
			var okText = !q || c.getAttribute('data-search').indexOf(q) > -1;
			c.hidden = !(okStatus && okText);
			if (!c.hidden) { shown++; }
		});
		if (empty) { empty.hidden = shown > 0; }
	}

	function setCounts(counts) {
		if (!counts) { return; }
		Object.keys(counts).forEach(function (k) {
			var el = document.querySelector('[data-count="' + k + '"]');
			if (el) { el.textContent = counts[k]; }
		});
		var pill = document.getElementById('nexvio-enabled');
		if (pill && D.i18n.of) { pill.textContent = D.i18n.of.replace('%1$s', counts.active).replace('%2$s', counts.all); }
	}

	function setCard(card, on) {
		card.classList.toggle('is-off', !on);
		card.setAttribute('data-status', on ? 'active' : 'inactive');
		var input = card.querySelector('.nexvio-switch input');
		if (input) { input.checked = on; }
	}

	function post(data) {
		var body = new FormData();
		body.append('nonce', D.nonce);
		Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) { return r.json(); });
	}

	if (search) {
		search.addEventListener('input', apply);
		/* press "/" to jump to the search */
		document.addEventListener('keydown', function (e) {
			if (e.key === '/' && document.activeElement !== search && !/input|textarea|select/i.test(document.activeElement.tagName)) {
				e.preventDefault();
				search.focus();
			}
			if (e.key === 'Escape' && document.activeElement === search) { search.value = ''; apply(); }
		});
	}

	document.querySelectorAll('.nexvio-stat').forEach(function (b) {
		b.addEventListener('click', function () {
			filter = b.getAttribute('data-filter');
			document.querySelectorAll('.nexvio-stat').forEach(function (x) {
				x.classList.toggle('is-active', x === b);
				x.setAttribute('aria-pressed', x === b ? 'true' : 'false');
			});
			apply();
		});
	});

	grid.addEventListener('change', function (e) {
		var input = e.target;
		if (!input.matches('.nexvio-switch input')) { return; }
		var card = input.closest('.nexvio-card');
		var on = input.checked;
		var title = card.querySelector('.nexvio-card__title').textContent;
		card.classList.add('is-saving');
		post({ action: 'nexvio_toggle_widget', key: input.getAttribute('data-key'), enabled: on ? '1' : '0' })
			.then(function (res) {
				if (!res || !res.success) { throw new Error('fail'); }
				setCard(card, on);
				setCounts(res.data.counts);
				apply();
				toast(title + ' ' + (on ? D.i18n.enabled : D.i18n.disabled));
			})
			.catch(function () { input.checked = !on; toast(D.i18n.error, true); })
			.then(function () { card.classList.remove('is-saving'); });
	});

	document.querySelectorAll('[data-bulk]').forEach(function (b) {
		b.addEventListener('click', function () {
			var mode = b.getAttribute('data-bulk');
			b.disabled = true;
			post({ action: 'nexvio_bulk_widgets', mode: mode })
				.then(function (res) {
					if (!res || !res.success) { throw new Error('fail'); }
					grid.querySelectorAll('.nexvio-card').forEach(function (c) { setCard(c, res.data.enabled); });
					setCounts(res.data.counts);
					apply();
					toast(D.i18n.saved);
				})
				.catch(function () { toast(D.i18n.error, true); })
				.then(function () { b.disabled = false; });
		});
	});
})();
