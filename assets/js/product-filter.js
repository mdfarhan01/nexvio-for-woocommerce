/* Nexvio Product Filter: AJAX filtering of the product grid, no page reload. */
/* Source: https://github.com/mdfarhan01/nexvio-for-woocommerce (this file is not minified; edit it directly). */
(function () {
	'use strict';

	var controller = null;

	function cfg(root) {
		try {
			return JSON.parse(root.getAttribute('data-config') || '{}');
		} catch (e) {
			return {};
		}
	}
	function roots() {
		return document.querySelectorAll('.nexvio-pf');
	}
	function num(v, d) {
		var n = parseFloat(v);
		return isNaN(n) ? d : n;
	}
	function cleanPath(u) {
		try {
			var p = new URL(u, location.href).pathname;
			return p.replace(/\/page\/\d+\/?$/, '/').replace(/\/+$/, '') || '/';
		} catch (e) {
			return u;
		}
	}

	/* ---------------- price slider ---------------- */
	function priceEls(root) {
		var g = root.querySelector('.nexvio-pf__group--price');
		if (!g) {
			return null;
		}
		return {
			g: g,
			range: g.querySelector('.nexvio-pf__range'),
			lo: g.querySelector('.nexvio-pf__lo'),
			hi: g.querySelector('.nexvio-pf__hi'),
			min: g.querySelector('input[name="min_price"]'),
			max: g.querySelector('input[name="max_price"]')
		};
	}
	function paintRange(root) {
		var e = priceEls(root);
		if (!e || !e.range || !e.lo) {
			return;
		}
		var b = cfg(root).bounds || { min: 0, max: 100 };
		var span = b.max - b.min || 1;
		var lo = ((num(e.lo.value, b.min) - b.min) / span) * 100;
		var hi = ((num(e.hi.value, b.max) - b.min) / span) * 100;
		e.range.style.setProperty('--lo', lo + '%');
		e.range.style.setProperty('--hi', hi + '%');
		/* keep the handle that is on the right end clickable */
		e.lo.style.zIndex = lo > 90 ? 4 : 3;
	}
	function priceValues(root) {
		var e = priceEls(root);
		var b = cfg(root).bounds || { min: 0, max: 100 };
		if (!e) {
			return null;
		}
		var lo = e.min ? num(e.min.value, b.min) : e.lo ? num(e.lo.value, b.min) : b.min;
		var hi = e.max ? num(e.max.value, b.max) : e.hi ? num(e.hi.value, b.max) : b.max;
		lo = Math.max(b.min, Math.min(lo, b.max));
		hi = Math.max(b.min, Math.min(hi, b.max));
		if (lo > hi) {
			var t = lo;
			lo = hi;
			hi = t;
		}
		return { lo: lo, hi: hi, b: b };
	}
	function setPrice(root, lo, hi) {
		var e = priceEls(root);
		if (!e) {
			return;
		}
		if (e.lo) {
			e.lo.value = lo;
			e.hi.value = hi;
		}
		if (e.min) {
			e.min.value = lo;
		}
		if (e.max) {
			e.max.value = hi;
		}
		paintRange(root);
	}

	/* ---------------- build URL from the form ---------------- */
	function buildUrl(root) {
		var c = cfg(root);
		var base = c.base || c.shop || location.href;
		var cat = root.querySelector('.nexvio-pf__group--category[data-param="category"]');
		if (cat) {
			var sel = cat.querySelector('select[name="nexvio_cat"]');
			var r = sel
				? sel.options[sel.selectedIndex]
				: cat.querySelector('input[name="nexvio_cat"]:checked');
			base = r && r.getAttribute('data-url') ? r.getAttribute('data-url') : c.shop || base;
		}
		var u = new URL(base, location.href);
		/* keep the sorting chosen by the visitor (unless this filter has its own "Sort by" field) */
		var cur = new URLSearchParams(location.search);
		if (cur.get('orderby') && !root.querySelector('[data-param="orderby"]')) {
			u.searchParams.set('orderby', cur.get('orderby'));
		}

		var q = root.querySelector('input[name="nexvio_q"]');
		if (q && q.value.trim()) {
			u.searchParams.set('nexvio_q', q.value.trim());
		}

		var pv = priceValues(root);
		if (pv) {
			if (pv.lo > pv.b.min) {
				u.searchParams.set('min_price', pv.lo);
			}
			if (pv.hi < pv.b.max) {
				u.searchParams.set('max_price', pv.hi);
			}
		}

		root.querySelectorAll('.nexvio-pf__group[data-param]').forEach(function (g) {
			var name = g.getAttribute('data-param');
			var kind = g.getAttribute('data-kind');
			if (name === 'category') {
				return;
			}
			if (kind === 'toggle') {
				var cb = g.querySelector('input[type="checkbox"]');
				if (cb && cb.checked) {
					u.searchParams.set(name, '1');
				}
			} else if (kind === 'single') {
				var el = g.querySelector('select') || g.querySelector('input:checked');
				if (el && el.value) {
					u.searchParams.set(name, el.value);
				}
			} else {
				var vals = [];
				g.querySelectorAll('input:checked').forEach(function (i) {
					if (i.value) {
						vals.push(i.value);
					}
				});
				if (vals.length) {
					u.searchParams.set(name, vals.join(','));
					if (g.getAttribute('data-logic') === 'all' && vals.length > 1) {
						u.searchParams.set(name + '_all', '1');
					}
				}
			}
		});
		return u.toString();
	}

	/* ---------------- load results ---------------- */
	function jqTrigger(name, args) {
		if (window.jQuery) {
			try {
				window.jQuery(document.body).trigger(name, args || []);
			} catch (e) {}
		}
	}

	function go(root, url, push) {
		var c = cfg(root);
		var selector = c.selector || '.nexvio-pg';
		var targets = document.querySelectorAll(selector);
		if (!c.ajax || !targets.length || !window.fetch || !window.DOMParser) {
			window.location.href = url;
			return;
		}

		if (controller) {
			controller.abort();
		}
		controller = window.AbortController ? new AbortController() : null;
		targets.forEach(function (t) {
			t.classList.add('nexvio-pf-loading');
			t.setAttribute('aria-busy', 'true');
		});
		roots().forEach(function (r) {
			r.classList.add('is-busy');
		});
		jqTrigger('nexvio_filter_loading', [url]);

		fetch(url, {
			credentials: 'same-origin',
			signal: controller ? controller.signal : undefined,
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (r) {
				return r.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var fresh = doc.querySelectorAll(selector);
				if (!fresh.length) {
					window.location.href = url;
					return;
				}
				var current = document.querySelectorAll(selector);
				current.forEach(function (t, i) {
					var n = fresh[i] || fresh[fresh.length - 1];
					t.replaceWith(document.importNode(n, true));
				});
				if (doc.title) {
					document.title = doc.title;
				}
				if (push) {
					history.pushState({ nexvioFilter: true }, '', url);
				}
				roots().forEach(function (r) {
					syncFromUrl(r);
				});

				if (c.scroll) {
					var first = document.querySelector(selector);
					if (first) {
						var top = first.getBoundingClientRect().top;
						if (top < 0 || top > window.innerHeight * 0.7) {
							window.scrollTo({ top: window.pageYOffset + top - 100, behavior: 'smooth' });
						}
					}
				}
				jqTrigger('nexvio_filter_updated', [url]);
				jqTrigger('post-load');
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				window.location.href = url;
			})
			.then(function () {
				document.querySelectorAll(selector).forEach(function (t) {
					t.classList.remove('nexvio-pf-loading');
					t.removeAttribute('aria-busy');
				});
				roots().forEach(function (r) {
					r.classList.remove('is-busy');
				});
			});
	}

	/* same filters as the page already shows? (ignores the page number) */
	function stateKey(url) {
		try {
			var u = new URL(url, location.href);
			u.searchParams.delete('paged');
			var keys = [];
			u.searchParams.forEach(function (v, k) {
				keys.push(k + '=' + v);
			});
			keys.sort();
			return u.pathname.replace(/\/page\/\d+\/?$/, '/').replace(/\/+$/, '') + '?' + keys.join('&');
		} catch (e) {
			return url;
		}
	}

	function apply(root, force) {
		var c = cfg(root);
		if (!force && c.instant === false) {
			return;
		}
		var url = buildUrl(root);
		if (!force && stateKey(url) === stateKey(location.href)) {
			markActive(root);
			return;
		}
		go(root, url, true);
	}

	/* ---------------- UI state from the URL ---------------- */
	function syncFromUrl(root) {
		var c = cfg(root);
		var p = new URLSearchParams(location.search);

		var q = root.querySelector('input[name="nexvio_q"]');
		if (q && document.activeElement !== q) {
			q.value = p.get('nexvio_q') || '';
		}
		toggleClear(root);

		var b = c.bounds || { min: 0, max: 100 };
		if (priceEls(root)) {
			setPrice(
				root,
				p.has('min_price') ? Math.max(b.min, num(p.get('min_price'), b.min)) : b.min,
				p.has('max_price') ? Math.min(b.max, num(p.get('max_price'), b.max)) : b.max
			);
		}

		var cat = root.querySelector('.nexvio-pf__group--category[data-param="category"]');
		if (cat) {
			var path = cleanPath(location.href),
				matched = null,
				all = null;
			var csel = cat.querySelector('select[name="nexvio_cat"]');
			(csel
				? Array.prototype.slice.call(csel.options)
				: Array.prototype.slice.call(cat.querySelectorAll('input[name="nexvio_cat"]'))
			).forEach(function (r) {
				if (!r.value) {
					all = r;
					return;
				}
				if (r.getAttribute('data-url') && cleanPath(r.getAttribute('data-url')) === path) {
					matched = r;
				}
			});
			var pick = matched || all;
			if (pick) {
				if (csel) {
					pick.selected = true;
				} else {
					pick.checked = true;
				}
			}
		}

		root.querySelectorAll('.nexvio-pf__group[data-param]').forEach(function (g) {
			var name = g.getAttribute('data-param');
			if (name === 'category') {
				return;
			}
			var vals = (p.get(name) || '').split(',').filter(Boolean);
			var sel = g.querySelector('select');
			if (sel) {
				sel.value = vals[0] || '';
				return;
			}
			g.querySelectorAll('input').forEach(function (i) {
				if (g.getAttribute('data-kind') === 'toggle') {
					i.checked = p.get(name) === '1';
				} else if (i.value === '') {
					i.checked = !vals.length;
				} else {
					i.checked = vals.indexOf(i.value) > -1;
				}
			});
		});
		expandChecked(root);
		markActive(root);
	}

	function markActive(root) {
		var c = cfg(root);
		var u = new URL(buildUrl(root));
		var shop = cleanPath(c.shop || '');
		var active = u.search.replace(/[?&]orderby=[^&]*/, '').length > 1 || cleanPath(u.href) !== shop;
		root.classList.toggle('has-active', active);
	}

	function toggleClear(root) {
		var q = root.querySelector('input[name="nexvio_q"]');
		var x = root.querySelector('.nexvio-pf__clear');
		if (q && x) {
			x.hidden = !q.value;
		}
	}

	/* "See more": open the list if a selected option is hidden */
	function expandChecked(root) {
		root.querySelectorAll('.nexvio-pf__list').forEach(function (list) {
			var checked = list.querySelector('.is-extra input:checked');
			if (checked) {
				openList(list, true);
			}
		});
		/* open every parent category of the selected one */
		root.querySelectorAll('.nexvio-pf__sublist input:checked').forEach(function (inp) {
			var sub = inp.closest('.nexvio-pf__sublist');
			while (sub) {
				setSub(sub, true);
				sub = sub.parentNode.closest('.nexvio-pf__sublist');
			}
		});
	}
	function setSub(sub, open) {
		sub.hidden = !open;
		var row = sub.previousElementSibling;
		var btn = row ? row.querySelector('.nexvio-pf__sub-toggle') : null;
		if (btn) {
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}
	function openList(list, open) {
		list.classList.toggle('is-open', open);
		var btn = list.nextElementSibling;
		if (btn && btn.classList.contains('nexvio-pf__more')) {
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			btn.textContent = open ? btn.getAttribute('data-less') : btn.getAttribute('data-more');
		}
	}

	/* ---------------- events ---------------- */
	var timers = {};
	function debounce(key, fn, ms) {
		clearTimeout(timers[key]);
		timers[key] = setTimeout(fn, ms);
	}

	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t.closest) {
			return;
		}

		var more = t.closest('.nexvio-pf__more');
		if (more) {
			e.preventDefault();
			var list = more.previousElementSibling;
			if (list) {
				openList(list, !list.classList.contains('is-open'));
			}
			return;
		}

		var subBtn = t.closest('.nexvio-pf__sub-toggle');
		if (subBtn) {
			e.preventDefault();
			/* the list that belongs to this button: found next to it, not by id (a sidebar filter and the Filter Popup on one page repeat the same ids) */
			var subRow = subBtn.closest('.nexvio-pf__row');
			var sub =
				subRow &&
				subRow.nextElementSibling &&
				subRow.nextElementSibling.classList.contains('nexvio-pf__sublist')
					? subRow.nextElementSibling
					: document.getElementById(subBtn.getAttribute('aria-controls'));
			if (sub) {
				setSub(sub, sub.hidden);
			}
			return;
		}

		var tog = t.closest('.nexvio-pf__toggle');
		if (tog) {
			e.preventDefault();
			var g = tog.closest('.nexvio-pf__group');
			var body = g.querySelector('.nexvio-pf__gbody');
			var open = !g.classList.contains('is-open');
			g.classList.toggle('is-open', open);
			tog.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (body) {
				body.hidden = !open;
			}
			return;
		}

		var clr = t.closest('.nexvio-pf__clear');
		if (clr) {
			var root = clr.closest('.nexvio-pf');
			var q = root.querySelector('input[name="nexvio_q"]');
			q.value = '';
			toggleClear(root);
			q.focus();
			apply(root, false);
			return;
		}

		var reset = t.closest('.nexvio-pf__reset');
		if (reset) {
			var r = reset.closest('.nexvio-pf');
			var c = cfg(r);
			r.querySelectorAll('input[name="nexvio_q"]').forEach(function (i) {
				i.value = '';
			});
			var b = c.bounds || { min: 0, max: 100 };
			setPrice(r, b.min, b.max);
			r.querySelectorAll('.nexvio-pf__group input[type="checkbox"]').forEach(function (i) {
				i.checked = false;
			});
			r.querySelectorAll('.nexvio-pf__group input[type="radio"]').forEach(function (i) {
				i.checked = i.value === '';
			});
			r.querySelectorAll('.nexvio-pf__group select').forEach(function (s) {
				s.value = '';
			});
			toggleClear(r);
			/* back to the whole shop */
			var u = new URL(c.shop || location.href, location.href);
			var cur = new URLSearchParams(location.search);
			if (cur.get('orderby')) {
				u.searchParams.set('orderby', cur.get('orderby'));
			}
			go(r, u.toString(), true);
			return;
		}

		/* pagination inside the products container */
		var any = roots()[0];
		if (any && cfg(any).ajax) {
			var a = t.closest('a.page-numbers, .woocommerce-pagination a');
			if (
				a &&
				a.href &&
				a.closest(cfg(any).selector || '.nexvio-pg') &&
				!e.ctrlKey &&
				!e.metaKey &&
				!e.shiftKey &&
				e.button === 0
			) {
				e.preventDefault();
				go(any, a.href, true);
			}
		}
	});

	document.addEventListener('change', function (e) {
		var t = e.target;
		var root = t.closest ? t.closest('.nexvio-pf') : null;
		if (!root) {
			return;
		}

		if (t.classList.contains('nexvio-pf__lo') || t.classList.contains('nexvio-pf__hi')) {
			debounce(
				'price',
				function () {
					apply(root, false);
				},
				250
			);
			return;
		}
		if (t.name === 'min_price' || t.name === 'max_price') {
			var pv = priceValues(root);
			setPrice(root, pv.lo, pv.hi);
			debounce(
				'price',
				function () {
					apply(root, false);
				},
				250
			);
			return;
		}
		if (t.name === 'nexvio_q') {
			return;
		}
		markActive(root);
		apply(root, false);
	});

	document.addEventListener('input', function (e) {
		var t = e.target;
		var root = t.closest ? t.closest('.nexvio-pf') : null;
		if (!root) {
			return;
		}

		if (t.classList.contains('nexvio-pf__lo') || t.classList.contains('nexvio-pf__hi')) {
			var e2 = priceEls(root);
			var lo = num(e2.lo.value, 0),
				hi = num(e2.hi.value, 0);
			if (lo > hi) {
				if (t === e2.lo) {
					e2.lo.value = hi;
				} else {
					e2.hi.value = lo;
				}
			}
			if (e2.min) {
				e2.min.value = e2.lo.value;
			}
			if (e2.max) {
				e2.max.value = e2.hi.value;
			}
			paintRange(root);
			return;
		}

		if (t.name === 'nexvio_q') {
			toggleClear(root);
			var c = cfg(root);
			var len = t.value.trim().length;
			if (len === 0 || len >= (c.minChars || 2)) {
				debounce(
					'search',
					function () {
						apply(root, false);
					},
					c.delay || 450
				);
			}
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter') {
			return;
		}
		var t = e.target;
		if (t.name === 'nexvio_q' && t.closest('.nexvio-pf')) {
			e.preventDefault();
			clearTimeout(timers.search);
			apply(t.closest('.nexvio-pf'), true);
		}
	});

	document.addEventListener('submit', function (e) {
		var f = e.target;

		/* filter form ("Apply" button or Enter) */
		if (f.classList && f.classList.contains('nexvio-pf__form')) {
			e.preventDefault();
			apply(f.closest('.nexvio-pf'), true);
			return;
		}

		var root = roots()[0];
		if (!root || !cfg(root).ajax) {
			return;
		}

		/* WooCommerce sorting dropdown inside the products container */
		if (
			f.classList &&
			f.classList.contains('woocommerce-ordering') &&
			f.closest(cfg(root).selector || '.nexvio-pg')
		) {
			e.preventDefault();
			var u = new URL(location.href);
			new FormData(f).forEach(function (v, k) {
				if (v !== '') {
					u.searchParams.set(k, v);
				} else {
					u.searchParams.delete(k);
				}
			});
			u.pathname = u.pathname.replace(/\/page\/\d+\/?$/, '/');
			u.searchParams.delete('paged');
			go(root, u.toString(), true);
			return;
		}

		/* header search: filter the products on this page without reloading */
		if (
			f.classList &&
			f.classList.contains('nexvio-hs__form') &&
			f.getAttribute('data-mode') === 'title' &&
			document.querySelector(cfg(root).selector || '.nexvio-pg')
		) {
			e.preventDefault();
			var term = (f.querySelector('input[name="nexvio_q"]') || {}).value || '';
			var q = root.querySelector('input[name="nexvio_q"]');
			if (q) {
				q.value = term;
				toggleClear(root);
				apply(root, true);
			} else {
				var u2 = new URL(buildUrl(root));
				if (term.trim()) {
					u2.searchParams.set('nexvio_q', term.trim());
				} else {
					u2.searchParams.delete('nexvio_q');
				}
				go(root, u2.toString(), true);
			}
			var hs = f.closest('.nexvio-hs');
			var x = hs ? hs.querySelector('.nexvio-hs__close') : null;
			if (x) {
				x.click();
			}
		}
	});

	/* Sorting dropdown: WooCommerce submits it with jQuery (native submit, no event),
	   so catch the change in the capture phase before WooCommerce sees it. */
	document.addEventListener(
		'change',
		function (e) {
			var sel = e.target;
			if (!sel.matches || !sel.matches('.woocommerce-ordering select')) {
				return;
			}
			var root = roots()[0];
			if (!root || !cfg(root).ajax || !sel.closest(cfg(root).selector || '.nexvio-pg')) {
				return;
			}
			e.stopImmediatePropagation();
			e.stopPropagation();
			var u = new URL(location.href);
			new FormData(sel.form).forEach(function (v, k) {
				if (v !== '') {
					u.searchParams.set(k, v);
				} else {
					u.searchParams.delete(k);
				}
			});
			u.pathname = u.pathname.replace(/\/page\/\d+\/?$/, '/');
			u.searchParams.delete('paged');
			go(root, u.toString(), true);
		},
		true
	);

	window.addEventListener('popstate', function () {
		var root = roots()[0];
		if (!root) {
			return;
		}
		if (cfg(root).ajax) {
			go(root, location.href, false);
		} else {
			roots().forEach(syncFromUrl);
		}
	});

	function init() {
		roots().forEach(function (r) {
			syncFromUrl(r); /* also correct on cached pages */
			paintRange(r);
			expandChecked(r);
			toggleClear(r);
			markActive(r);
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/nexvio_product_filter.default',
				function () {
					init();
				}
			);
		}
	});
})();
