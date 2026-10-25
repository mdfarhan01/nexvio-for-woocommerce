/* Nexvio Product Slider - lightweight scroll-snap slider, no dependencies */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;


	/* ------------------------------------------------------------------
	 * Continuous infinite scroll ("marquee")
	 * One set of the real cards + identical copies. The rail moves by a
	 * fractional offset every frame; when the offset passes the width of
	 * one set it is reduced by exactly that width, so the picture on screen
	 * is identical and the loop has no visible jump.
	 * ------------------------------------------------------------------ */
	function cssNum(el, name, fallback) {
		var v = parseFloat(getComputedStyle(el).getPropertyValue(name));
		return isNaN(v) ? fallback : v;
	}

	function initMarquee(root, track, prev, next, cfg) {
		var originals = Array.prototype.slice.call(track.querySelectorAll('.nexvio-ps__slide'));
		if (!originals.length) { return; }

		root.classList.add('is-marquee');
		var dots = root.querySelector('.nexvio-ps__dots');
		if (dots) { dots.hidden = true; }

		var rail = document.createElement('div');
		rail.className = 'nexvio-ps__rail';
		originals.forEach(function (sl) { rail.appendChild(sl); });
		track.appendChild(rail);

		var gap = 0, slideW = 0, setW = 1, offset = 0;
		var dir = cfg.direction === 'right' ? -1 : 1;
		var paused = false, dragging = false, moved = false, nudge = null, last = 0;

		function speed() {
			/* responsive speed: desktop / tablet / mobile */
			var w = window.innerWidth, sp = cfg.speed || {};
			var v = w <= 767 && sp.mobile ? sp.mobile : (w <= 1024 && sp.tablet ? sp.tablet : sp.desktop);
			return Math.max(1, v || 40);
		}

		function build() {
			var ratio = setW > 1 ? offset / setW : 0;
			rail.querySelectorAll('.is-clone').forEach(function (c) { c.remove(); });
			root.style.removeProperty('--nexvio-ps-slide-w');

			var spv = cssNum(root, '--nexvio-ps-spv', 4);
			var peek = cssNum(root, '--nexvio-ps-peek', 0);
			gap = cssNum(root, '--nexvio-ps-gap', 18);
			var W = track.clientWidth || root.clientWidth || 1;
			slideW = Math.max(60, (W - gap * (spv - 1) - (peek > 0 ? gap : 0)) / (spv + peek));
			root.style.setProperty('--nexvio-ps-slide-w', slideW + 'px');

			setW = originals.length * (slideW + gap);
			/* enough copies to always cover the visible width + one set */
			var copies = Math.max(1, Math.ceil((W + slideW) / setW) + 1);
			for (var k = 0; k < copies; k++) {
				originals.forEach(function (o) {
					var c = o.cloneNode(true);
					c.classList.add('is-clone');
					c.setAttribute('aria-hidden', 'true');
					c.querySelectorAll('a, button, input, select').forEach(function (x) { x.setAttribute('tabindex', '-1'); });
					rail.appendChild(c);
				});
			}
			offset = ratio * setW;
			paint();
		}

		function wrap(v) { return ((v % setW) + setW) % setW; }
		function paint() { rail.style.transform = 'translate3d(' + (-wrap(offset)).toFixed(3) + 'px,0,0)'; }

		function frame(t) {
			if (!document.documentElement.contains(root)) { return; }
			var dt = last ? Math.min(64, t - last) : 16; /* clamp after a hidden tab */
			last = t;
			if (nudge) {
				var p = Math.min(1, (t - nudge.start) / nudge.dur);
				var e = p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2; /* ease in-out */
				offset = nudge.from + (nudge.to - nudge.from) * e;
				if (p >= 1) { nudge = null; }
			} else if (!paused && !dragging && !reduceMotion) {
				offset += dir * speed() * dt / 1000;
			}
			offset = wrap(offset);
			paint();
			window.requestAnimationFrame(frame);
		}

		function step(d) {
			var from = nudge ? nudge.to : offset;
			nudge = { from: offset, to: from + d * (slideW + gap), start: performance.now(), dur: 450 };
		}
		if (prev) { prev.disabled = false; prev.addEventListener('click', function () { step(-1); }); }
		if (next) { next.disabled = false; next.addEventListener('click', function () { step(1); }); }

		if (cfg.pauseOnHover) {
			root.addEventListener('mouseenter', function () { paused = true; });
			root.addEventListener('mouseleave', function () { paused = false; });
		}
		root.addEventListener('focusin', function () { paused = true; });
		root.addEventListener('focusout', function () { paused = !!cfg.pauseOnHover && root.matches(':hover'); });

		/* drag / swipe (mouse and touch) */
		if (cfg.drag !== false) {
			var startX = 0, startOff = 0, pid = null;
			track.addEventListener('pointerdown', function (e) {
				if (e.pointerType === 'mouse' && e.button !== 0) { return; }
				pid = e.pointerId; startX = e.clientX; startOff = offset; moved = false; nudge = null;
			});
			window.addEventListener('pointermove', function (e) {
				if (pid !== e.pointerId) { return; }
				var dx = e.clientX - startX;
				if (!dragging && Math.abs(dx) > 6) { dragging = true; moved = true; track.classList.add('is-dragging'); }
				if (dragging) { offset = startOff - dx; }
			});
			var end = function (e) {
				if (pid !== e.pointerId) { return; }
				pid = null;
				if (dragging) { dragging = false; track.classList.remove('is-dragging'); }
			};
			window.addEventListener('pointerup', end);
			window.addEventListener('pointercancel', end);
			track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
		}

		var rt;
		window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(build, 150); });
		/* images or fonts can change sizes after load */
		window.addEventListener('load', build);

		build();
		window.requestAnimationFrame(frame);
	}

	/* Slide mode with fewer products than visible cards: use the full width (no empty space on the right). */
	function fitFew(root, track) {
		root.style.removeProperty('--nexvio-ps-spv');
		root.style.removeProperty('--nexvio-ps-peek');
		var n = track.querySelectorAll('.nexvio-ps__slide').length;
		var spv = cssNum(root, '--nexvio-ps-spv', 4);
		var few = n > 0 && n <= spv;
		root.classList.toggle('is-few', few);
		if (few) {
			root.style.setProperty('--nexvio-ps-spv', n);
			root.style.setProperty('--nexvio-ps-peek', 0);
		}
	}

	function init(root) {
		if (!root || root.getAttribute('data-nexvio-ready') === '1') { return; }
		root.setAttribute('data-nexvio-ready', '1');

		var track = root.querySelector('.nexvio-ps__track');
		if (!track) { return; } /* grid widget: no slider */
		var prev = root.querySelector('.nexvio-ps__prev');
		var next = root.querySelector('.nexvio-ps__next');
		var dotsWrap = root.querySelector('.nexvio-ps__dots');
		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-settings') || '{}'); } catch (e) { cfg = {}; }

		if (cfg.mode === 'marquee') { initMarquee(root, track, prev, next, cfg); return; }

		fitFew(root, track);
		var fitTimer;
		window.addEventListener('resize', function () { clearTimeout(fitTimer); fitTimer = setTimeout(function () { fitFew(root, track); }, 150); });

		var slides = function () { return track.querySelectorAll('.nexvio-ps__slide'); };
		var gap = function () { return parseFloat(getComputedStyle(track).columnGap) || 0; };
		var step = function () {
			var s = track.querySelector('.nexvio-ps__slide');
			return s ? s.getBoundingClientRect().width + gap() : track.clientWidth;
		};
		var perView = function () { return Math.max(1, Math.floor((track.clientWidth + gap() + 2) / step())); };
		var by = function () { return cfg.scrollBy === 'page' ? perView() : 1; };
		var maxLeft = function () { return track.scrollWidth - track.clientWidth; };
		var atStart = function () { return track.scrollLeft <= 2; };
		var atEnd = function () { return track.scrollLeft >= maxLeft() - 2; };
		var behavior = reduceMotion ? 'auto' : 'smooth';

		function go(dir) {
			if (cfg.loop) {
				if (dir > 0 && atEnd()) { track.scrollTo({ left: 0, behavior: behavior }); return; }
				if (dir < 0 && atStart()) { track.scrollTo({ left: maxLeft(), behavior: behavior }); return; }
			}
			track.scrollBy({ left: dir * step() * by(), behavior: behavior });
		}

		function pageCount() {
			var n = slides().length, pv = perView(), b = by();
			if (n <= pv) { return 1; }
			return Math.ceil((n - pv) / b) + 1;
		}

		function buildDots() {
			if (!dotsWrap || !cfg.dots) { return; }
			var pages = pageCount();
			dotsWrap.innerHTML = '';
			if (pages < 2) { return; }
			for (var i = 0; i < pages; i++) {
				var d = document.createElement('button');
				d.type = 'button';
				d.className = 'nexvio-ps__dot';
				d.setAttribute('aria-label', (cfg.dotLabel || 'Slide') + ' ' + (i + 1));
				(function (idx) {
					d.addEventListener('click', function () {
						track.scrollTo({ left: Math.min(maxLeft(), idx * step() * by()), behavior: behavior });
					});
				})(i);
				dotsWrap.appendChild(d);
			}
		}

		function update() {
			if (prev) { prev.disabled = !cfg.loop && atStart(); }
			if (next) { next.disabled = !cfg.loop && atEnd(); }
			if (dotsWrap && cfg.dots) {
				var dots = dotsWrap.querySelectorAll('.nexvio-ps__dot');
				if (!dots.length) { return; }
				var idx = atEnd() ? dots.length - 1 : Math.round(track.scrollLeft / (step() * by()));
				for (var i = 0; i < dots.length; i++) { dots[i].classList.toggle('is-active', i === idx); }
			}
		}

		if (prev) { prev.addEventListener('click', function () { go(-1); restart(); }); }
		if (next) { next.addEventListener('click', function () { go(1); restart(); }); }

		var ticking = false;
		track.addEventListener('scroll', function () {
			if (ticking) { return; }
			ticking = true;
			requestAnimationFrame(function () { update(); ticking = false; });
		}, { passive: true });

		var resizeTimer;
		window.addEventListener('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () { buildDots(); update(); }, 150);
		});

		/* mouse drag (touch already scrolls natively) */
		if (cfg.drag) {
			var down = false, moved = false, startX = 0, startLeft = 0;
			track.addEventListener('pointerdown', function (e) {
				if (e.pointerType !== 'mouse' || e.button !== 0) { return; }
				down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
			});
			window.addEventListener('pointermove', function (e) {
				if (!down) { return; }
				var dx = e.clientX - startX;
				if (!moved && Math.abs(dx) > 5) { moved = true; track.classList.add('is-dragging'); }
				if (moved) { track.scrollLeft = startLeft - dx; }
			});
			window.addEventListener('pointerup', function () {
				if (!down) { return; }
				down = false;
				if (moved) {
					track.classList.remove('is-dragging');
					var target = Math.round(track.scrollLeft / step()) * step();
					track.scrollTo({ left: target, behavior: behavior });
				}
			});
			track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
		}

		/* autoplay */
		var timer = null, paused = false;
		function start() {
			if (!cfg.autoplay || reduceMotion) { return; }
			stop();
			timer = setInterval(function () {
				if (!document.body.contains(root)) { stop(); return; }
				if (!paused && !document.hidden) {
					if (!cfg.loop && atEnd()) { track.scrollTo({ left: 0, behavior: behavior }); }
					else { go(1); }
				}
			}, Math.max(1000, cfg.autoplaySpeed || 4000));
		}
		function stop() { if (timer) { clearInterval(timer); timer = null; } }
		function restart() { if (timer) { start(); } }
		if (cfg.pauseOnHover) {
			root.addEventListener('mouseenter', function () { paused = true; });
			root.addEventListener('mouseleave', function () { paused = false; });
			root.addEventListener('focusin', function () { paused = true; });
			root.addEventListener('focusout', function () { paused = false; });
			track.addEventListener('touchstart', function () { paused = true; }, { passive: true });
		}

		/* keyboard */
		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
		});

		buildDots();
		update();
		start();
	}


	/* ------------------------------------------------------------------
	 * Wishlist (built-in, AJAX)
	 * ------------------------------------------------------------------ */
	var DATA = window.NEXVIO || {};

	function rootCfg(el) {
		var root = el.closest('.nexvio-ps');
		try { return JSON.parse((root && root.getAttribute('data-settings')) || '{}'); } catch (e) { return {}; }
	}

	var toastEl = null, toastTimer = null;
	function toast(text, link, linkText) {
		if (!text) { return; }
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'nexvio-ps-toast';
			toastEl.setAttribute('role', 'status');
			toastEl.setAttribute('aria-live', 'polite');
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = '';
		var span = document.createElement('span');
		span.textContent = text;
		toastEl.appendChild(span);
		if (link && linkText) {
			var a = document.createElement('a');
			a.href = link;
			a.textContent = linkText;
			toastEl.appendChild(a);
		}
		toastEl.classList.add('is-on');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.classList.remove('is-on'); }, 3200);
	}

	function setHearts(id, on) {
		document.querySelectorAll('.nexvio-ps__wish[data-wish][data-id="' + id + '"]').forEach(function (b) {
			b.classList.toggle('is-on', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function jqTrigger(name, args) {
		if (window.jQuery) {
			try { window.jQuery(document.body).trigger(name, args || []); } catch (e) {}
		}
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.nexvio-ps__wish[data-wish]') : null;
		if (!btn) { return; }
		e.preventDefault();
		var id = btn.getAttribute('data-id');
		var w = rootCfg(btn).wish || {};
		if (btn.classList.contains('is-loading') || !DATA.ajax) { return; }
		document.querySelectorAll('.nexvio-ps__wish[data-wish][data-id="' + id + '"]').forEach(function (b) { b.classList.add('is-loading'); });

		var body = new FormData();
		body.append('action', 'nexvio_wishlist_toggle');
		body.append('product_id', id);
		body.append('nonce', DATA.nonce || '');

		fetch(DATA.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json().catch(function () { return { success: false }; }); })
			.then(function (res) {
				if (res && res.success) {
					var on = !!res.data.in;
					setHearts(id, on);
					if (w.toast) { toast(on ? w.added : w.removed, on ? (res.data.url || DATA.wishlist) : '', w.view); }
					jqTrigger(on ? 'nexvio_added_to_wishlist' : 'nexvio_removed_from_wishlist', [id, res.data]);
					jqTrigger('nexvio_wishlist_changed', [id, res.data]);
					document.querySelectorAll('.nexvio-wishlist-count').forEach(function (c) { c.textContent = res.data.count; });
					return;
				}
				var d = (res && res.data) || {};
				if (d.login) { window.location.href = d.login; return; }
				if (d.reload) { window.location.reload(); return; }
				if (w.toast) { toast(w.error || 'Error'); }
			})
			.catch(function () { if (w.toast) { toast(w.error || 'Error'); } })
			.then(function () {
				document.querySelectorAll('.nexvio-ps__wish[data-wish][data-id="' + id + '"]').forEach(function (b) { b.classList.remove('is-loading'); });
			});
	}, true);

	/* products replaced by AJAX (filters): start new sliders */
	if (window.MutationObserver) {
		var pending = false;
		new MutationObserver(function () {
			if (pending) { return; }
			pending = true;
			requestAnimationFrame(function () { pending = false; initAll(); });
		}).observe(document.documentElement, { childList: true, subtree: true });
	}


	/* ------------------------------------------------------------------
	 * Colour swatches: show the variation image on the card
	 * ------------------------------------------------------------------ */
	function swCard(btn) { return btn.closest('.nexvio-ps__card'); }

	function swShow(card, btn) {
		var img = card.querySelector('.nexvio-ps__img');
		if (img && !img.hasAttribute('data-orig-src')) {
			img.setAttribute('data-orig-src', img.getAttribute('src') || '');
			img.setAttribute('data-orig-srcset', img.getAttribute('srcset') || '');
		}
		var links = card.querySelectorAll('a.nexvio-ps__media, .nexvio-ps__name a');
		links.forEach(function (a) { if (!a.hasAttribute('data-orig-href')) { a.setAttribute('data-orig-href', a.getAttribute('href') || ''); } });

		var src = btn ? btn.getAttribute('data-img') : null;
		if (img) {
			/* a colour with its own image shows it; anything else shows the main image */
			var newSrc = src || img.getAttribute('data-orig-src');
			var newSet = src ? (btn.getAttribute('data-srcset') || '') : img.getAttribute('data-orig-srcset');
			if (img.getAttribute('src') !== newSrc) { img.setAttribute('src', newSrc); }
			if (newSet) { img.setAttribute('srcset', newSet); } else { img.removeAttribute('srcset'); }
		}
		var link = btn ? btn.getAttribute('data-link') : null;
		links.forEach(function (a) { a.setAttribute('href', link || a.getAttribute('data-orig-href')); });
	}

	function swSelected(card) { return card.querySelector('.nexvio-ps__sw[aria-pressed="true"]'); }

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.nexvio-ps__sw') : null;
		if (!btn) { return; }
		e.preventDefault();
		var card = swCard(btn);
		if (!card) { return; }
		var on = btn.getAttribute('aria-pressed') !== 'true';
		card.querySelectorAll('.nexvio-ps__sw').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
		if (on) { btn.setAttribute('aria-pressed', 'true'); }
		swShow(card, on ? btn : null);
	});

	/* hover mode (desktop): preview while the pointer is over a swatch */
	document.addEventListener('mouseover', function (e) {
		var btn = e.target.closest ? e.target.closest('.nexvio-ps__swatches[data-action="hover"] .nexvio-ps__sw') : null;
		if (!btn) { return; }
		swShow(swCard(btn), btn);
	});
	document.addEventListener('mouseout', function (e) {
		var wrap = e.target.closest ? e.target.closest('.nexvio-ps__swatches[data-action="hover"]') : null;
		if (!wrap || (e.relatedTarget && wrap.contains(e.relatedTarget))) { return; }
		var card = swCard(wrap);
		swShow(card, swSelected(card));
	});

	/* load the colour image before it is needed: no flash on change */
	document.addEventListener('pointerenter', function (e) {
		var btn = e.target && e.target.classList && e.target.classList.contains('nexvio-ps__sw') ? e.target : null;
		if (btn && btn.getAttribute('data-img') && !btn.hasAttribute('data-preloaded')) {
			btn.setAttribute('data-preloaded', '1');
			var pre = new Image();
			pre.src = btn.getAttribute('data-img');
		}
	}, true);

	function initAll(scope) {
		(scope || document).querySelectorAll('.nexvio-ps').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	/* Elementor editor and frontend re-renders */
	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_slider.default', function ($scope) {
			var el = $scope && $scope[0] ? $scope[0] : null;
			if (el) { initAll(el); }
		});
	});
})();
