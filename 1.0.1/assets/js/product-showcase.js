/* Nexvio Product Showcase: pick a product in the list and the whole view (image, title, text, price, stock, payments, button) switches.
   - The switch is a cross-fade with an animated height, so nothing jumps and the page never scrolls by itself.
   - Payment boxes that come from the Tabby / Tamara plugins are started here when their panel is shown.
   Delegated events, so it also works inside the Elementor editor after every re-render. Add to cart is handled by add-to-cart.js. */
(function () {
	'use strict';

	var DUR = { fade: 340, slide: 440 };
	var EASE = 'cubic-bezier(.22,.61,.36,1)';

	function items(root) {
		return Array.prototype.slice.call(root.querySelectorAll('.nexvio-shw__list > .nexvio-shw__item'));
	}

	function reduced() {
		return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	/* ---------------------------------------------------------------
	   Scroll lock: remember where the page (and any clipped wrapper) is
	   and put it back, so a product switch can never move the page.
	   --------------------------------------------------------------- */
	function snapshot(el) {
		var s = { x: window.pageXOffset, y: window.pageYOffset, anc: [], user: false };
		for (var n = el.parentNode; n && n.nodeType === 1 && n !== document.documentElement; n = n.parentNode) {
			var cs = window.getComputedStyle(n);
			if (cs.overflowX !== 'visible' || cs.overflowY !== 'visible') {
				s.anc.push([n, n.scrollLeft, n.scrollTop]);
			}
		}
		return s;
	}

	function restore(s) {
		if (!s) { return; }
		s.anc.forEach(function (a) {
			if (a[0].scrollLeft !== a[1]) { a[0].scrollLeft = a[1]; }
			if (a[0].scrollTop !== a[2]) { a[0].scrollTop = a[2]; }
		});
		if (!s.user && (Math.abs(window.pageYOffset - s.y) > 1 || Math.abs(window.pageXOffset - s.x) > 1)) {
			try { window.scrollTo({ left: s.x, top: s.y, behavior: 'instant' }); } catch (e) { window.scrollTo(s.x, s.y); }
		}
	}

	function noAnchor(on) {
		var se = document.scrollingElement || document.documentElement;
		se.style.overflowAnchor = on ? 'none' : '';
	}

	/* ---------------------------------------------------------------
	   Images: load them before the switch so nothing pops in half way.
	   --------------------------------------------------------------- */
	function eagerImages(panel) {
		Array.prototype.forEach.call(panel.querySelectorAll('img[loading="lazy"]'), function (img) {
			img.loading = 'eager';
		});
	}

	function whenImages(panel, max, cb) {
		var imgs = Array.prototype.slice.call(panel.querySelectorAll('.nexvio-shw__media img')).filter(function (i) { return !i.complete; });
		if (!imgs.length) { cb(); return; }
		var done = false;
		function go() { if (!done) { done = true; cb(); } }
		var left = imgs.length;
		imgs.forEach(function (i) {
			var one = function () { left--; if (left <= 0) { go(); } };
			i.addEventListener('load', one, { once: true });
			i.addEventListener('error', one, { once: true });
		});
		setTimeout(go, max);
	}

	function preloadAll() {
		Array.prototype.forEach.call(document.querySelectorAll('.nexvio-shw__panel[hidden] img[loading="lazy"]'), function (img) {
			img.loading = 'eager';
		});
	}

	/* ---------------------------------------------------------------
	   Payment boxes drawn by the Tabby / Tamara plugins
	   --------------------------------------------------------------- */
	var tabbyTimer = null;
	var tabbyTries = 0;

	function initTabby(panel) {
		var holders = panel.querySelectorAll('.nexvio-shw__ext--tabby:not([data-ready])');
		if (!holders.length) { return; }
		var root = panel.closest('.nexvio-shw');
		var conf = null;
		try { conf = JSON.parse(root.getAttribute('data-tabby') || 'null'); } catch (e) { conf = null; }
		if (!conf) { return; }
		if (typeof window.TabbyPromo === 'undefined') {
			/* tabby-promo.js is still loading: try again shortly */
			if (!tabbyTimer && tabbyTries < 60) {
				tabbyTimer = setTimeout(function () {
					tabbyTimer = null; tabbyTries++;
					initVisible();
				}, 250);
			}
			return;
		}
		Array.prototype.forEach.call(holders, function (h) {
			var c = {};
			for (var k in conf) { if (Object.prototype.hasOwnProperty.call(conf, k)) { c[k] = conf[k]; } }
			c.selector = '#' + h.id;
			c.price = h.getAttribute('data-tabby-price') || c.price;
			c.currency = h.getAttribute('data-tabby-currency') || c.currency;
			if (c.localeSource === 'html') { c.lang = document.documentElement.lang; }
			try { new window.TabbyPromo(c); h.setAttribute('data-ready', '1'); } catch (e) { /* ignore */ }
		});
	}

	function refreshTamara(panel) {
		if (panel.getAttribute('data-tamara-ready') || !panel.querySelector('tamara-widget')) { return; }
		panel.setAttribute('data-tamara-ready', '1');
		try {
			if (window.TamaraWidgetV2 && typeof window.TamaraWidgetV2.refresh === 'function') { window.TamaraWidgetV2.refresh(); }
		} catch (e) { /* ignore */ }
	}

	function afterShow(panel) {
		initTabby(panel);
		refreshTamara(panel);
	}

	function initVisible() {
		Array.prototype.forEach.call(document.querySelectorAll('.nexvio-shw__panel.is-active'), afterShow);
	}

	/* ---------------------------------------------------------------
	   Switching
	   --------------------------------------------------------------- */
	function finish(root) {
		if (root.__nx && root.__nx.end) { root.__nx.end(); }
	}

	function swap(root, oldP, newP, done) {
		var stage = newP.parentNode;
		var mode = root.classList.contains('nexvio-shw--anim-slide') ? 'slide' : (root.classList.contains('nexvio-shw--anim-fade') ? 'fade' : 'none');
		var canAnim = mode !== 'none' && oldP && oldP !== newP && typeof newP.animate === 'function' && !reduced();
		var snap = snapshot(stage);
		var st = { };
		var anims = [];
		var ended = false;
		var onUser = function () { snap.user = true; };

		root.__nx = st;
		eagerImages(newP);

		function cleanup() {
			if (oldP && oldP !== newP) {
				oldP.classList.remove('is-active', 'is-leaving');
				oldP.setAttribute('hidden', '');
			}
			newP.classList.remove('is-entering');
			newP.style.opacity = '';
			stage.style.height = '';
			stage.classList.remove('is-moving');
			noAnchor(false);
			window.removeEventListener('wheel', onUser);
			window.removeEventListener('touchmove', onUser);
			window.removeEventListener('keydown', onUser);
			restore(snap);
			if (window.requestAnimationFrame) { window.requestAnimationFrame(function () { restore(snap); }); }
			root.__nx = null;
			afterShow(newP);
			done();
		}

		st.end = function () {
			if (ended) { return; }
			ended = true;
			anims.forEach(function (a) { try { a.cancel(); } catch (e) { /* ignore */ } });
			cleanup();
		};

		if (!canAnim) {
			if (oldP && oldP !== newP) {
				oldP.classList.remove('is-active');
				oldP.setAttribute('hidden', '');
			}
			newP.removeAttribute('hidden');
			newP.classList.add('is-active');
			ended = true;
			noAnchor(false);
			restore(snap);
			if (window.requestAnimationFrame) { window.requestAnimationFrame(function () { restore(snap); }); }
			root.__nx = null;
			afterShow(newP);
			done();
			return;
		}

		window.addEventListener('wheel', onUser, { passive: true });
		window.addEventListener('touchmove', onUser, { passive: true });
		window.addEventListener('keydown', onUser, { passive: true });
		noAnchor(true);

		/* freeze the stage at the old height, old panel leaves the flow, new panel is laid out invisibly */
		var oldH = oldP.getBoundingClientRect().height;
		stage.style.height = oldH + 'px';
		stage.classList.add('is-moving');
		oldP.classList.add('is-leaving');
		newP.removeAttribute('hidden');
		newP.classList.add('is-active', 'is-entering');
		newP.style.opacity = '0';

		whenImages(newP, 400, function () {
			if (ended) { return; }
			var newH = newP.getBoundingClientRect().height;
			var dur = DUR[mode] || 340;

			anims.push(oldP.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 170, easing: 'ease-out', fill: 'forwards' }));

			var kf = mode === 'slide'
				? [{ opacity: 0, transform: 'translateY(18px)' }, { opacity: 1, transform: 'translateY(0)' }]
				: [{ opacity: 0 }, { opacity: 1 }];
			var aIn = newP.animate(kf, { duration: dur, delay: 110, easing: EASE, fill: 'both' });
			newP.style.opacity = '';
			anims.push(aIn);

			stage.style.height = newH + 'px';
			anims.push(stage.animate([{ height: oldH + 'px' }, { height: newH + 'px' }], { duration: dur + 110, easing: EASE }));

			aIn.onfinish = function () { st.end(); };
			/* safety net in case the browser never fires onfinish (background tab) */
			setTimeout(function () { st.end(); }, dur + 110 + 400);
		});
	}

	function keepInView(list, item) {
		/* one-row scroll mode: keep the selected item in view, without moving the page */
		if (list.scrollWidth > list.clientWidth + 1) {
			var left = item.offsetLeft - (list.clientWidth - item.offsetWidth) / 2;
			if (list.scrollTo) { list.scrollTo({ left: left, behavior: 'smooth' }); } else { list.scrollLeft = left; }
		}
	}

	function focusItem(item) {
		try { item.focus({ preventScroll: true }); } catch (e) { item.focus(); }
	}

	function activate(item, focus) {
		var root = item.closest('.nexvio-shw');
		if (!root || item.classList.contains('is-active')) {
			if (focus) { focusItem(item); }
			return;
		}
		finish(root);

		var list = item.parentNode;
		var newP = document.getElementById(item.getAttribute('aria-controls'));
		var oldP = root.querySelector('.nexvio-shw__panel.is-active');

		items(root).forEach(function (el) {
			var on = el === item;
			el.classList.toggle('is-active', on);
			el.setAttribute('aria-selected', on ? 'true' : 'false');
			el.setAttribute('tabindex', on ? '0' : '-1');
		});

		keepInView(list, item);
		if (focus) { focusItem(item); }
		if (!newP) { return; }

		swap(root, oldP, newP, function () {
			root.dispatchEvent(new CustomEvent('nexvio:showcase:change', {
				bubbles: true,
				detail: { index: parseInt(item.getAttribute('data-index'), 10) || 0, product: newP.getAttribute('data-product') || '' }
			}));
		});
	}

	document.addEventListener('click', function (e) {
		var item = e.target.closest ? e.target.closest('.nexvio-shw__item') : null;
		if (item) { activate(item, false); }
	});

	/* a mouse press must not focus the button: focusing can make the browser scroll the page or a clipped wrapper */
	document.addEventListener('mousedown', function (e) {
		var item = e.target.closest ? e.target.closest('.nexvio-shw__item') : null;
		if (item && e.button === 0) { e.preventDefault(); }
	});

	/* "Hover" mode: real mouse only. Touch devices keep tap. */
	document.addEventListener('mouseover', function (e) {
		var item = e.target.closest ? e.target.closest('.nexvio-shw__item') : null;
		if (!item) { return; }
		var root = item.closest('.nexvio-shw');
		if (!root || root.getAttribute('data-trigger') !== 'hover') { return; }
		if (window.matchMedia && !window.matchMedia('(hover: hover) and (pointer: fine)').matches) { return; }
		activate(item, false);
	});

	/* keyboard: arrows, Home, End */
	document.addEventListener('keydown', function (e) {
		var item = e.target.closest ? e.target.closest('.nexvio-shw__item') : null;
		if (!item) { return; }
		var root = item.closest('.nexvio-shw');
		var all = items(root);
		var i = all.indexOf(item);
		var next = -1;
		switch (e.key) {
			case 'ArrowRight':
			case 'ArrowDown': next = (i + 1) % all.length; break;
			case 'ArrowLeft':
			case 'ArrowUp': next = (i - 1 + all.length) % all.length; break;
			case 'Home': next = 0; break;
			case 'End': next = all.length - 1; break;
			default: return;
		}
		e.preventDefault();
		activate(all[next], true);
	});


	/* ---------------------------------------------------------------
	   Variations (colour, size…): choose a value -> find the variation ->
	   change image, price and Add to cart state. No request needed:
	   every variation is in the page as JSON (data-variations).
	   --------------------------------------------------------------- */
	function low(x) { return String(x == null ? '' : x).toLowerCase(); }

	function vdata(v) {
		if (!v.__vd) {
			try { v.__vd = JSON.parse(v.getAttribute('data-variations') || '[]'); } catch (e) { v.__vd = []; }
		}
		return v.__vd;
	}

	function vgroups(v) {
		return Array.prototype.slice.call(v.querySelectorAll('.nexvio-shw__var'));
	}

	function selection(v) {
		var o = {};
		vgroups(v).forEach(function (g) { o[g.getAttribute('data-attr')] = g.getAttribute('data-value') || ''; });
		return o;
	}

	/* does this variation fit the chosen values? (an empty choice or an "any" attribute fits everything) */
	function fits(d, sel) {
		for (var k in sel) {
			if (!Object.prototype.hasOwnProperty.call(sel, k) || sel[k] === '') { continue; }
			var a = d.attrs ? d.attrs[k] : undefined;
			if (a === undefined || a === '' || a === null) { continue; }
			if (low(a) !== low(sel[k])) { return false; }
		}
		return true;
	}

	function exactMatch(data, sel) {
		for (var k in sel) {
			if (Object.prototype.hasOwnProperty.call(sel, k) && sel[k] === '') { return null; }
		}
		for (var i = 0; i < data.length; i++) {
			if (fits(data[i], sel)) { return data[i]; }
		}
		return null;
	}

	/* image of the chosen colour even before every attribute is chosen: only when all fitting variations share one image */
	function sharedImage(cands) {
		var src = null, pick = null;
		for (var i = 0; i < cands.length; i++) {
			var im = cands[i].img;
			if (!im) { continue; }
			if (src === null) { src = im.src; pick = im; } else if (im.src !== src) { return null; }
		}
		return pick;
	}

	function setImage(panel, d) {
		var img = panel.querySelector('.nexvio-shw__media img');
		if (!img) { return; }
		if (!img.__orig) {
			img.__orig = { src: img.getAttribute('src') || '', srcset: img.getAttribute('srcset') || '', sizes: img.getAttribute('sizes') || '' };
		}
		var t = d || img.__orig;
		if ((img.getAttribute('src') || '') === t.src && (img.getAttribute('srcset') || '') === (t.srcset || '')) { return; }

		var token = (img.__tok || 0) + 1;
		img.__tok = token;
		var applied = false;
		function apply() {
			if (applied || img.__tok !== token) { return; }
			applied = true;
			img.style.transition = 'opacity .16s ease';
			img.style.opacity = '0';
			setTimeout(function () {
				if (img.__tok !== token) { return; }
				img.removeAttribute('loading');
				if (t.srcset) { img.setAttribute('srcset', t.srcset); } else { img.removeAttribute('srcset'); }
				if (t.sizes) { img.setAttribute('sizes', t.sizes); } else { img.removeAttribute('sizes'); }
				img.setAttribute('src', t.src);
				img.style.opacity = '';
			}, 160);
		}
		var pre = new Image();
		pre.onload = apply;
		pre.onerror = apply;
		pre.src = t.src;
		setTimeout(apply, 700);
	}

	function setGroup(g, val) {
		g.setAttribute('data-value', val);
		if (val) { g.classList.remove('is-missing'); }
		var want = low(val);
		Array.prototype.forEach.call(g.querySelectorAll('.nexvio-shw__opt'), function (el) {
			var on = want !== '' && low(el.getAttribute('data-value')) === want;
			el.classList.toggle('is-selected', on);
			if (el.tagName === 'BUTTON') { el.setAttribute('aria-pressed', on ? 'true' : 'false'); }
			var r = el.querySelector('input[type="radio"]');
			if (r) { r.checked = on; }
		});
		var sel = g.querySelector('select.nexvio-shw__select');
		if (sel) {
			var found = '';
			Array.prototype.forEach.call(sel.options, function (o) { if (want !== '' && low(o.value) === want) { found = o.value; } });
			sel.value = found;
		}
		var hid = g.querySelector('.nexvio-shw__var-input');
		if (hid) { hid.value = val; }
	}

	function refresh(v) {
		var data = vdata(v);
		var sel = selection(v);
		var panel = v.closest('.nexvio-shw__panel');
		var form = document.getElementById(v.getAttribute('data-form') || '');
		var match = exactMatch(data, sel);
		var chosen = Object.keys(sel).some(function (k) { return sel[k] !== ''; });
		var allChosen = Object.keys(sel).every(function (k) { return sel[k] !== ''; });
		var cands = data.filter(function (d) { return fits(d, sel); });

		/* which values can still be picked */
		vgroups(v).forEach(function (g) {
			var key = g.getAttribute('data-attr');
			Array.prototype.forEach.call(g.querySelectorAll('.nexvio-shw__opt, option'), function (el) {
				var val = el.tagName === 'OPTION' ? el.value : el.getAttribute('data-value');
				if (val === '' || val === null) { return; }
				var s2 = {};
				for (var k in sel) { if (Object.prototype.hasOwnProperty.call(sel, k)) { s2[k] = sel[k]; } }
				s2[key] = val;
				var ok = data.some(function (d) { return d.stock && fits(d, s2); });
				el.classList.toggle('is-unavailable', !ok);
				if (el.tagName === 'OPTION') { el.hidden = !ok && v.getAttribute('data-unav') === 'hide'; }
			});
		});

		if (form && form.elements['variation_id']) { form.elements['variation_id'].value = match ? match.id : 0; }

		if (panel && v.getAttribute('data-img') === '1') {
			var im = match && match.img ? match.img : (chosen ? sharedImage(cands) : null);
			setImage(panel, im);
		}

		if (panel && v.getAttribute('data-price') === '1') {
			var pe = panel.querySelector('.nexvio-shw__price');
			if (pe) {
				if (pe.__orig === undefined) { pe.__orig = pe.innerHTML; }
				pe.innerHTML = match && match.price ? match.price : pe.__orig;
			}
		}

		if (form && form.getAttribute('data-avail') !== '0') {
			var btn = form.querySelector('.nexvio-atc__btn');
			var label = btn ? btn.querySelector('.nexvio-atc__label') : null;
			var texts = {};
			try { texts = JSON.parse(form.getAttribute('data-texts') || '{}'); } catch (e) { texts = {}; }
			if (btn && !btn.classList.contains('is-loading')) {
				var bad = false, txt = texts.label || '';
				if (allChosen) {
					if (!match) { bad = true; txt = texts.unavail || texts.out || txt; }
					else if (!match.stock) { bad = true; txt = texts.out || txt; }
				}
				btn.disabled = bad;
				btn.classList.toggle('is-disabled', bad);
				if (bad) { btn.setAttribute('aria-disabled', 'true'); } else { btn.removeAttribute('aria-disabled'); }
				if (label && txt) { label.textContent = txt; }
			}
			var q = form.querySelector('input.nexvio-atc__input');
			if (q && match) {
				q.setAttribute('min', match.min > 0 ? match.min : 1);
				if (match.max > 0) { q.setAttribute('max', match.max); } else { q.removeAttribute('max'); }
				q.dispatchEvent(new Event('change', { bubbles: true }));
			}
		}

		v.dispatchEvent(new CustomEvent('nexvio:showcase:variation', { bubbles: true, detail: { variation: match ? match.id : 0, product: panel ? panel.getAttribute('data-product') : '' } }));
	}

	function choose(v, g, val) {
		if (!v || !g) { return; }
		setGroup(g, val);
		refresh(v);
	}

	function initVars(scope) {
		Array.prototype.forEach.call((scope || document).querySelectorAll('.nexvio-shw__vars:not([data-ready])'), function (v) {
			v.setAttribute('data-ready', '1');
			vgroups(v).forEach(function (g) { setGroup(g, g.getAttribute('data-value') || ''); });
			refresh(v);
		});
	}

	document.addEventListener('click', function (e) {
		var o = e.target.closest ? e.target.closest('.nexvio-shw__vars .nexvio-shw__opt--btn, .nexvio-shw__vars .nexvio-shw__opt--sw') : null;
		if (!o) { return; }
		e.preventDefault();
		choose(o.closest('.nexvio-shw__vars'), o.closest('.nexvio-shw__var'), o.getAttribute('data-value') || '');
	});

	document.addEventListener('change', function (e) {
		var t = e.target;
		if (!t || !t.closest) { return; }
		var v = t.closest('.nexvio-shw__vars');
		if (!v) { return; }
		if (t.classList.contains('nexvio-shw__select') || (t.type === 'radio' && t.closest('.nexvio-shw__opt--check'))) {
			choose(v, t.closest('.nexvio-shw__var'), t.value);
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initVars(); });
	} else {
		initVars();
	}

	/* start the payment widgets of the first panel, and warm up the other images */
	function boot() {
		initVisible();
		var idle = window.requestIdleCallback || function (fn) { return setTimeout(fn, 1200); };
		idle(preloadAll);
	}
	if (document.readyState === 'complete') { boot(); } else { window.addEventListener('load', boot); }

	/* Elementor editor: the widget is drawn again after every change */
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_showcase.default', function ($el) {
				var el = $el && $el[0] ? $el[0] : $el;
				if (el && el.querySelectorAll) {
					Array.prototype.forEach.call(el.querySelectorAll('.nexvio-shw__panel.is-active'), afterShow);
					initVars(el);
				}
			});
		}
	});
})();
