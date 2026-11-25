/* Nexvio Product Images: thumbnails, arrows, swipe, hover zoom, lightbox, gallery arrows, variation image. */
(function () {
	'use strict';

	function $$(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}


	/* ---------- lightbox (one for the whole page) ---------- */
	var Lightbox = (function () {
		var el = null, big = null, strip = null, count = null, items = [], idx = 0, labels = {}, lastFocus = null, sx = null, sy = null;

		var ARR_L = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 5-7 7 7 7"/></svg>';
		var ARR_R = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 5 7 7-7 7"/></svg>';
		var X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg>';

		function build() {
			el = document.createElement('div');
			el.className = 'nexvio-lb';
			el.setAttribute('role', 'dialog');
			el.setAttribute('aria-modal', 'true');
			el.hidden = true;
			el.innerHTML =
				'<div class="nexvio-lb__bg" data-lb="close"></div>' +
				'<button type="button" class="nexvio-lb__x" data-lb="close"></button>' +
				'<div class="nexvio-lb__count"></div>' +
				'<button type="button" class="nexvio-lb__nav nexvio-lb__nav--prev" data-lb="prev"></button>' +
				'<button type="button" class="nexvio-lb__nav nexvio-lb__nav--next" data-lb="next"></button>' +
				'<div class="nexvio-lb__stage" data-lb="close"><img class="nexvio-lb__img" alt="" draggable="false"></div>' +
				'<div class="nexvio-lb__strip" role="group"></div>';
			document.body.appendChild(el);
			big = el.querySelector('.nexvio-lb__img');
			strip = el.querySelector('.nexvio-lb__strip');
			count = el.querySelector('.nexvio-lb__count');
			el.querySelector('.nexvio-lb__x').innerHTML = X;
			el.querySelector('.nexvio-lb__nav--prev').innerHTML = ARR_L;
			el.querySelector('.nexvio-lb__nav--next').innerHTML = ARR_R;

			el.addEventListener('click', function (e) {
				if (e.target === big) { return; }
				var t = e.target.closest ? e.target.closest('[data-lb], [data-lb-i]') : null;
				if (!t) { return; }
				if (t.hasAttribute('data-lb-i')) { go(parseInt(t.getAttribute('data-lb-i'), 10) || 0); return; }
				var a = t.getAttribute('data-lb');
				if (a === 'close') { close(); } else if (a === 'prev') { go(idx - 1); } else if (a === 'next') { go(idx + 1); }
			});
			el.addEventListener('touchstart', function (e) {
				if (e.touches.length !== 1) { sx = null; return; }
				sx = e.touches[0].clientX; sy = e.touches[0].clientY;
			}, { passive: true });
			el.addEventListener('touchend', function (e) {
				if (sx === null || !e.changedTouches.length) { return; }
				var dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
				sx = null;
				if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) { go(idx + (dx < 0 ? 1 : -1)); }
			}, { passive: true });
			document.addEventListener('keydown', function (e) {
				if (el.hidden) { return; }
				if (e.key === 'Escape') { e.preventDefault(); close(); }
				else if (e.key === 'ArrowLeft') { e.preventDefault(); go(idx - 1); }
				else if (e.key === 'ArrowRight') { e.preventDefault(); go(idx + 1); }
			});
		}

		function go(i) {
			var n = items.length;
			if (!n) { return; }
			idx = (i + n) % n;
			var it = items[idx];
			big.classList.add('is-swap');
			var next = new Image();
			var done = false;
			function apply() {
				if (done) { return; }
				done = true;
				big.src = it.src;
				if (it.srcset) { big.setAttribute('srcset', it.srcset); big.setAttribute('sizes', '100vw'); } else { big.removeAttribute('srcset'); big.removeAttribute('sizes'); }
				big.alt = it.alt || '';
				big.classList.remove('is-swap');
			}
			next.onload = apply; next.onerror = apply; next.src = it.src;
			setTimeout(apply, 500);
			count.textContent = (idx + 1) + ' / ' + n;
			Array.prototype.forEach.call(strip.children, function (b, k) {
				var on = k === idx;
				b.classList.toggle('is-active', on);
				if (on) {
					b.setAttribute('aria-current', 'true');
					strip.scrollLeft = b.offsetLeft - (strip.clientWidth - b.offsetWidth) / 2;
				} else { b.removeAttribute('aria-current'); }
			});
		}

		function open(list, at, lab) {
			if (!el) { build(); }
			items = list; labels = lab || {};
			lastFocus = document.activeElement;
			el.querySelector('.nexvio-lb__x').setAttribute('aria-label', labels.close || 'Close');
			el.querySelector('.nexvio-lb__nav--prev').setAttribute('aria-label', labels.prev || 'Previous image');
			el.querySelector('.nexvio-lb__nav--next').setAttribute('aria-label', labels.next || 'Next image');
			var many = items.length > 1;
			el.classList.toggle('is-single', !many);
			strip.innerHTML = '';
			if (many) {
				items.forEach(function (it, k) {
					var b = document.createElement('button');
					b.type = 'button';
					b.className = 'nexvio-lb__thumb';
					b.setAttribute('data-lb-i', String(k));
					var im = document.createElement('img');
					im.src = it.thumb || it.src; im.alt = ''; im.draggable = false;
					b.appendChild(im);
					strip.appendChild(b);
				});
			}
			el.hidden = false;
			document.documentElement.classList.add('nexvio-lb-open');
			void el.offsetWidth;
			el.classList.add('is-open');
			go(at || 0);
			el.querySelector('.nexvio-lb__x').focus({ preventScroll: true });
		}

		function close() {
			if (!el || el.hidden) { return; }
			el.classList.remove('is-open');
			el.hidden = true;
			document.documentElement.classList.remove('nexvio-lb-open');
			if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) {} }
		}

		return { open: open, close: close };
	})();

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
			/* keep the active button visible inside its strip: scroll ONLY when it is cut off, and only as much as needed.
			   A thumbnail that is already fully visible never moves the strip (no jumping when you click one). */
			var strip = t.parentNode;
			if (!strip || !t.offsetParent) { return; } /* hidden thumbnail: nothing to do */
			var pad = 4;
			if (strip.scrollHeight > strip.clientHeight + 1) {
				var top = t.offsetTop, bot = top + t.offsetHeight;
				if (top < strip.scrollTop + pad) { strip.scrollTop = Math.max(0, top - pad); }
				else if (bot > strip.scrollTop + strip.clientHeight - pad) { strip.scrollTop = bot - strip.clientHeight + pad; }
			} else if (strip.scrollWidth > strip.clientWidth + 1) {
				var left = t.offsetLeft, right = left + t.offsetWidth;
				if (left < strip.scrollLeft + pad) { strip.scrollLeft = Math.max(0, left - pad); }
				else if (right > strip.scrollLeft + strip.clientWidth - pad) { strip.scrollLeft = right - strip.clientWidth + pad; }
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
		var sx = null, sy = null, swiped = false;
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
			if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) {
				go(current + (dx < 0 ? 1 : -1));
				swiped = true;
				setTimeout(function () { swiped = false; }, 400);
			}
		}, { passive: true });


		/* ---------- hover zoom (magnifier): only with a mouse ---------- */
		if (cfg.zoom && window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
			var zoomOn = false;
			var zoomable = function (t) {
				return !(t && t.closest && t.closest('.nexvio-pi__nav, .nexvio-pi__thumbs-wrap'));
			};
			var moveZoom = function (e) {
				if (!zoomable(e.target)) { endZoom(); return; }
				var r = stage.getBoundingClientRect();
				if (!r.width || !r.height) { return; }
				var x = Math.max(0, Math.min(100, ((e.clientX - r.left) / r.width) * 100));
				var y = Math.max(0, Math.min(100, ((e.clientY - r.top) / r.height) * 100));
				img.style.transformOrigin = x + '% ' + y + '%';
				if (!zoomOn) { zoomOn = true; stage.classList.add('is-zooming'); }
			};
			var endZoom = function () {
				if (!zoomOn) { return; }
				zoomOn = false;
				stage.classList.remove('is-zooming');
			};
			stage.addEventListener('mousemove', moveZoom);
			stage.addEventListener('mouseleave', endZoom);
		}

		/* ---------- gallery row: no scrollbar, arrows (left / right for a row, up / down for a column) ---------- */
		$$(root, '.nexvio-pi__below').forEach(function (below) {
			var list = below.querySelector('.nexvio-pi__below-list');
			var a = below.querySelector('.nexvio-pi__gnav--a');
			var b = below.querySelector('.nexvio-pi__gnav--b');
			if (!list || !a || !b) { return; }

			function vertical() {
				return window.getComputedStyle(list).flexDirection === 'column';
			}

			function update() {
				var v = vertical();
				var pos = v ? list.scrollTop : list.scrollLeft;
				var max = v ? list.scrollHeight - list.clientHeight : list.scrollWidth - list.clientWidth;
				below.classList.toggle('is-vertical', v);
				below.classList.toggle('is-scrollable', max > 2);
				a.classList.toggle('is-off', pos <= 1);
				b.classList.toggle('is-off', pos >= max - 1);
			}

			function scrollBy(dir) {
				var first = list.querySelector('.nexvio-pi__bthumb');
				var v = vertical();
				var step = first ? (v ? first.offsetHeight : first.offsetWidth) * 2 : 160;
				var opts = { behavior: 'smooth' };
				if (v) { opts.top = dir * step; } else { opts.left = dir * step; }
				if (list.scrollBy) { list.scrollBy(opts); } else if (v) { list.scrollTop += dir * step; } else { list.scrollLeft += dir * step; }
			}

			a.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); scrollBy(-1); });
			b.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); scrollBy(1); });
			list.addEventListener('scroll', update, { passive: true });
			window.addEventListener('resize', update);
			if (window.ResizeObserver) { new ResizeObserver(update).observe(list); }
			list.querySelectorAll('img').forEach(function (im) { im.addEventListener('load', update); });
			update();
			setTimeout(update, 400);
		});

		/* ---------- lightbox: the big image with the whole gallery ---------- */
		if (cfg.lightbox) {
			var tapBlock = false;
			stage.addEventListener('touchend', function () { tapBlock = true; setTimeout(function () { tapBlock = false; }, 350); }, { passive: true });
			stage.addEventListener('click', function (e) {
				if (e.target.closest && e.target.closest('.nexvio-pi__nav, .nexvio-pi__thumbs-wrap')) { return; }
				if (swiped) { return; }
				if (document.body.classList.contains('elementor-editor-active')) { return; }
				openBox();
			});
			stage.setAttribute('tabindex', '0');
			stage.setAttribute('role', 'button');
			stage.addEventListener('keydown', function (e) {
				if (e.target === stage && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openBox(); }
			});
		}

		function openBox() {
			var list = (cfg.images || []).slice();
			if (!list.length) { return; }
			var at = current;
			if (original !== null) {
				/* a variation image is on screen: it is the first image of the viewer */
				list.unshift({ src: img.currentSrc || img.src, srcset: '', sizes: '', thumb: img.currentSrc || img.src, alt: img.alt });
				at = 0;
			}
			Lightbox.open(list, at, cfg.labels || {});
		}

		/* variable products with the Product Price widget: its image replaces the main image */
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
