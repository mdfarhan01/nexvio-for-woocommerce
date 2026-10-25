/* Nexvio: Filter Popup - the Product Filter in a side panel. */
/* Source: https://github.com/mdfarhan01/nexvio-for-woocommerce (this file is not minified; edit it directly). */
(function () {
	'use strict';
	var opener = null;

	function drawerOf(btn) {
		return document.getElementById(btn.getAttribute('aria-controls'));
	}
	function buttonsOf(drawer) {
		return document.querySelectorAll('[data-nexvio-fd-open][aria-controls="' + drawer.id + '"]');
	}

	/* Move the panel to the end of the page (fixed panels break inside transformed sections),
	   inside copies of the Elementor wrappers so the widget's Style settings still apply. */
	function portal(drawer) {
		if (drawer.getAttribute('data-portal') === '1') {
			return;
		}
		drawer.setAttribute('data-portal', '1');
		if (document.body.classList.contains('elementor-editor-active')) {
			return;
		}
		var el = drawer.closest('.elementor-element');
		var doc = drawer.closest('[data-elementor-id]');
		var outer = document.createElement('div');
		outer.className =
			'nexvio-fd-portal' + (doc ? ' elementor elementor-' + doc.getAttribute('data-elementor-id') : '');
		var inner = document.createElement('div');
		inner.className = el ? el.className : '';
		outer.appendChild(inner);
		document.body.appendChild(outer);
		inner.appendChild(drawer);
	}

	function open(drawer, btn) {
		opener = btn || document.activeElement;
		drawer.removeAttribute('inert');
		drawer.setAttribute('aria-hidden', 'false');
		var sb = window.innerWidth - document.documentElement.clientWidth;
		document.documentElement.style.setProperty('--nexvio-fd-sb', sb + 'px');
		document.documentElement.classList.add('nexvio-fd-lock');
		drawer.classList.add('is-open');
		buttonsOf(drawer).forEach(function (b) {
			b.setAttribute('aria-expanded', 'true');
		});
		var panel = drawer.querySelector('.nexvio-fd__panel');
		window.setTimeout(function () {
			if (panel) {
				panel.focus({ preventScroll: true });
			}
		}, 40);
	}

	function close(drawer) {
		if (!drawer || !drawer.classList.contains('is-open')) {
			return;
		}
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		drawer.setAttribute('inert', '');
		document.documentElement.classList.remove('nexvio-fd-lock');
		buttonsOf(drawer).forEach(function (b) {
			b.setAttribute('aria-expanded', 'false');
		});
		if (opener && opener.focus) {
			opener.focus({ preventScroll: true });
		}
	}

	function openDrawer() {
		return document.querySelector('.nexvio-fd.is-open');
	}

	/* number of active filters, shown on the button */
	function count(drawer) {
		var root = drawer.querySelector('.nexvio-pf');
		if (!root) {
			return 0;
		}
		var n = 0;
		root.querySelectorAll('.nexvio-pf__group').forEach(function (g) {
			var param = g.getAttribute('data-param');
			if (param === 'orderby' || param === 'nexvio_pp') {
				return;
			}
			if (g.classList.contains('nexvio-pf__group--search')) {
				var q = g.querySelector('input[name="nexvio_q"]');
				if (q && q.value.trim()) {
					n++;
				}
				return;
			}
			if (g.classList.contains('nexvio-pf__group--price')) {
				var cfg = {};
				try {
					cfg = JSON.parse(root.getAttribute('data-config') || '{}');
				} catch (e) {}
				var b = cfg.bounds || {},
					lo = g.querySelector('input[name="min_price"], .nexvio-pf__lo'),
					hi = g.querySelector('input[name="max_price"], .nexvio-pf__hi');
				if ((lo && parseFloat(lo.value) > b.min) || (hi && parseFloat(hi.value) < b.max)) {
					n++;
				}
				return;
			}
			var sel = g.querySelector('select');
			if (sel) {
				if (sel.value) {
					n++;
				}
				return;
			}
			if (g.querySelector('input:checked:not([value=""])')) {
				n++;
			}
		});
		return n;
	}
	function updateBadges() {
		document.querySelectorAll('.nexvio-fd').forEach(function (d) {
			var n = count(d);
			buttonsOf(d).forEach(function (b) {
				var badge = b.querySelector('.nexvio-fd__badge');
				if (badge) {
					badge.textContent = n;
					badge.hidden = n < 1;
				}
				b.classList.toggle('has-active', n > 0);
			});
		});
	}

	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t.closest) {
			return;
		}
		var btn = t.closest('[data-nexvio-fd-open]');
		if (btn) {
			var d = drawerOf(btn);
			if (d) {
				e.preventDefault();
				if (d.classList.contains('is-open')) {
					close(d);
				} else {
					open(d, btn);
				}
			}
			return;
		}
		var x = t.closest('[data-nexvio-fd-close]');
		if (x) {
			e.preventDefault();
			close(x.closest('.nexvio-fd'));
			return;
		}
		var ov = t.closest('[data-nexvio-fd-overlay]');
		if (ov) {
			var dr = ov.closest('.nexvio-fd');
			if (dr && dr.getAttribute('data-overlay-close') === '1') {
				close(dr);
			}
		}
	});

	/* "Apply" (and Enter in the search box) applies the filters and closes the panel */
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (f.classList && f.classList.contains('nexvio-pf__form')) {
			var d = f.closest('.nexvio-fd');
			if (d) {
				window.setTimeout(function () {
					close(d);
				}, 60);
			}
		}
	});

	document.addEventListener('keydown', function (e) {
		var d = openDrawer();
		if (!d) {
			return;
		}
		if (e.key === 'Escape') {
			close(d);
			return;
		}
		if (e.key === 'Enter' && e.target && e.target.name === 'nexvio_q' && d.contains(e.target)) {
			window.setTimeout(function () {
				close(d);
			}, 60);
			return;
		}
		if (e.key === 'Tab') {
			var panel = d.querySelector('.nexvio-fd__panel');
			var f = panel.querySelectorAll(
				'a[href],button:not([disabled]),input:not([type="hidden"]),select,[tabindex]:not([tabindex="-1"])'
			);
			var vis = Array.prototype.filter.call(f, function (el) {
				return el.offsetParent !== null;
			});
			if (!vis.length) {
				return;
			}
			var first = vis[0],
				last = vis[vis.length - 1];
			if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});

	function init() {
		document.querySelectorAll('.nexvio-fd').forEach(portal);
		updateBadges();
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
	window.addEventListener('popstate', function () {
		window.setTimeout(updateBadges, 400);
	});
	if (window.jQuery) {
		window.jQuery(function ($) {
			$(document.body).on('nexvio_filter_updated', function () {
				window.setTimeout(updateBadges, 30);
			});
		});
	}
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/nexvio_filter_drawer.default',
				function () {
					init();
				}
			);
		}
	});
})();
