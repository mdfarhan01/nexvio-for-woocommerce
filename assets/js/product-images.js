/* Nexvio Product Images: thumbnails, arrows, swipe, variation image. */
(function () {
	'use strict';

	function $$(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}

	function init(root) {
		if (root.getAttribute('data-pi-ready') === '1') { return; }
		root.setAttribute('data-pi-ready', '1');

		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-pi') || '{}'); } catch (e) { cfg = {}; }

		var stage = root.querySelector('.nexvio-pi__stage');
		var img = root.querySelector('.nexvio-pi__img');
		var thumbs = $$(root, '[data-pi-thumb]');
		if (!stage || !img) { return; }

		var current = 0;
		var swapTimer = 0;
		var original = null; /* the gallery image shown before a variation image replaced it */

		function show(src, srcset, sizes, alt) {
			if (!src) { return; }
			clearTimeout(swapTimer);
			img.classList.add('is-swap');
			var next = new Image();
			var done = false;
			function apply() {
				if (done) { return; }
				done = true;
				img.src = src;
				if (srcset) { img.setAttribute('srcset', srcset); } else { img.removeAttribute('srcset'); }
				if (sizes) { img.setAttribute('sizes', sizes); } else { img.removeAttribute('sizes'); }
				if (alt !== undefined) { img.alt = alt; }
				img.classList.remove('is-swap');
			}
			next.onload = apply;
			next.onerror = apply;
			next.src = src;
			swapTimer = setTimeout(apply, 400);
		}

		function count() {
			var n = 0;
			thumbs.forEach(function (b) { n = Math.max(n, (parseInt(b.getAttribute('data-pi-thumb'), 10) || 0) + 1); });
			return n;
		}

		function center(t) {
			/* keep the active button in view inside its (scrolling) strip */
			var strip = t.parentNode;
			if (!strip || !t.offsetParent) { return; } /* hidden thumbnail: nothing to centre */
			if (strip.scrollHeight > strip.clientHeight + 1) {
				strip.scrollTop = t.offsetTop - (strip.clientHeight - t.offsetHeight) / 2;
			} else if (strip.scrollWidth > strip.clientWidth + 1) {
				strip.scrollLeft = t.offsetLeft - (strip.clientWidth - t.offsetWidth) / 2;
			}
		}

		function go(i, focusEl) {
			var total = count();
			if (!total) { return; }
			current = (i + total) % total;
			var first = null;
			thumbs.forEach(function (b) {
				var on = (parseInt(b.getAttribute('data-pi-thumb'), 10) || 0) === current;
				b.classList.toggle('is-active', on);
				if (on) { b.setAttribute('aria-current', 'true'); if (!first) { first = b; } center(b); } else { b.removeAttribute('aria-current'); }
			});
			if (!first) { return; }
			show(first.getAttribute('data-src'), first.getAttribute('data-srcset'), first.getAttribute('data-sizes'), first.getAttribute('data-alt'));
			original = null;
			if (focusEl) { focusEl.focus({ preventScroll: true }); }
		}

		root.addEventListener('click', function (e) {
			var t = e.target.closest ? e.target.closest('[data-pi-thumb]') : null;
			if (t && root.contains(t)) {
				go(parseInt(t.getAttribute('data-pi-thumb'), 10) || 0);
				return;
			}
			var n = e.target.closest ? e.target.closest('[data-pi-nav]') : null;
			if (n && root.contains(n)) {
				go(current + (parseInt(n.getAttribute('data-pi-nav'), 10) || 1));
			}
		});

		root.addEventListener('keydown', function (e) {
			var el = e.target;
			if (!el.classList || !(el.classList.contains('nexvio-pi__thumb') || el.classList.contains('nexvio-pi__bthumb'))) { return; }
			var k = e.key;
			if (k === 'ArrowRight' || k === 'ArrowLeft' || k === 'ArrowDown' || k === 'ArrowUp') {
				e.preventDefault();
				var d = (k === 'ArrowRight' || k === 'ArrowDown') ? 1 : -1;
				var sameGroup = $$(el.parentNode, '[data-pi-thumb]');
				go(current + d);
				var target = sameGroup.filter(function (b) { return (parseInt(b.getAttribute('data-pi-thumb'), 10) || 0) === current; })[0];
				if (target) { target.focus({ preventScroll: true }); }
			}
		});

		/* swipe on the main image */
		var sx = null, sy = null;
		stage.addEventListener('touchstart', function (e) {
			if (e.touches.length !== 1) { sx = null; return; }
			sx = e.touches[0].clientX;
			sy = e.touches[0].clientY;
		}, { passive: true });
		stage.addEventListener('touchend', function (e) {
			if (sx === null || !e.changedTouches.length) { return; }
			var dx = e.changedTouches[0].clientX - sx;
			var dy = e.changedTouches[0].clientY - sy;
			sx = null;
			if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) { go(current + (dx < 0 ? 1 : -1)); }
		}, { passive: true });

		/* variable products with the Product Variations widget: its image replaces the main image */
		if (cfg.variations) {
			document.addEventListener('nexvio:variation', function (e) {
				if (!root.isConnected) { return; }
				var d = e.detail || {};
				if (cfg.product && d.product && String(d.product) !== String(cfg.product)) { return; }
				var im = d.image;
				var src = im ? (im.full_src || im.src) : '';
				if (src) {
					if (original === null) {
						original = { src: img.currentSrc || img.src, srcset: img.getAttribute('srcset'), sizes: img.getAttribute('sizes'), alt: img.alt };
					}
					show(src, im.srcset || '', im.sizes || '', im.alt || img.alt);
				} else if (original) {
					var o = original;
					original = null;
					show(o.src, o.srcset, o.sizes, o.alt);
				}
			});
		}

		/* variable products: the chosen variation's image replaces the main image */
		var $ = window.jQuery;
		if (cfg.variations && $) {
			$(document.body).on('found_variation', 'form.variations_form', function (e, v) {
				var im = v && v.image ? v.image : null;
				var src = im ? (im.full_src || im.src) : '';
				if (!src) { return; }
				if (original === null) {
					original = { src: img.currentSrc || img.src, srcset: img.getAttribute('srcset'), sizes: img.getAttribute('sizes'), alt: img.alt };
				}
				show(src, im.srcset || '', im.sizes || '', im.alt || img.alt);
			});
			$(document.body).on('reset_image', 'form.variations_form', function () {
				if (original) {
					var o = original;
					original = null;
					show(o.src, o.srcset, o.sizes, o.alt);
				}
			});
		}
	}

	function initAll(scope) {
		$$(scope || document, '.nexvio-pi[data-pi]').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_images.default', function ($scope) {
			if ($scope && $scope[0]) {
				$$($scope[0], '.nexvio-pi[data-pi]').forEach(function (n) { n.removeAttribute('data-pi-ready'); });
				initAll($scope[0]);
			}
		});
	});
})();
