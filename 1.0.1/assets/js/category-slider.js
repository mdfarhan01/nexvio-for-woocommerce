/* Nexvio Product Category Slider: scroll-snap slider with arrows, dots, drag, autoplay and loop. */
(function () {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function init(root) {
		if (!root || root.getAttribute('data-nxcg-ready') === '1') { return; }
		var track = root.querySelector('.nxcg-sl__track');
		if (!track) { return; }
		root.setAttribute('data-nxcg-ready', '1');

		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-nxcg') || '{}'); } catch (e) { cfg = {}; }
		var prev = root.querySelector('.nxcg-sl__prev');
		var next = root.querySelector('.nxcg-sl__next');
		var dotsEl = root.querySelector('.nxcg-sl__dots');
		var slides = Array.prototype.slice.call(track.querySelectorAll('.nxcg-sl__slide'));
		var timer = null, hover = false, dots = [];

		function gap() { return parseFloat(getComputedStyle(track).columnGap) || 0; }
		function stepW() { return slides.length ? slides[0].getBoundingClientRect().width + gap() : track.clientWidth; }
		function perView() { return Math.max(1, Math.round((track.clientWidth + gap()) / stepW())); }
		function maxLeft() { return Math.max(0, track.scrollWidth - track.clientWidth); }
		function pages() { return Math.max(1, slides.length - perView() + 1); }
		function index() { return Math.min(pages() - 1, Math.round(track.scrollLeft / stepW())); }
		function behavior() { return reduce ? 'auto' : 'smooth'; }
		function goTo(i) {
			i = Math.max(0, Math.min(pages() - 1, i));
			track.scrollTo({ left: i === pages() - 1 ? maxLeft() : i * stepW(), behavior: behavior() });
		}
		function atStart() { return track.scrollLeft <= 2; }
		function atEnd() { return track.scrollLeft >= maxLeft() - 2; }

		function move(dir) {
			var by = cfg.page ? perView() : 1;
			if (dir > 0) {
				if (atEnd()) { if (cfg.loop) { goTo(0); } return; }
				goTo(index() + by);
			} else {
				if (atStart()) { if (cfg.loop) { goTo(pages() - 1); } return; }
				goTo(index() - by);
			}
		}

		function buildDots() {
			if (!dotsEl) { return; }
			dotsEl.innerHTML = '';
			dots = [];
			var n = pages();
			if (n < 2) { return; }
			for (var i = 0; i < n; i++) {
				(function (k) {
					var b = document.createElement('button');
					b.type = 'button';
					b.setAttribute('aria-label', 'Slide ' + (k + 1));
					b.addEventListener('click', function () { goTo(k); restart(); });
					dotsEl.appendChild(b);
					dots.push(b);
				})(i);
			}
		}

		function update() {
			var few = maxLeft() <= 2;
			root.classList.toggle('is-static', few);
			var i = atEnd() && !few ? pages() - 1 : index();
			dots.forEach(function (d, k) { d.classList.toggle('is-active', k === i); });
			if (prev) { prev.disabled = !cfg.loop && atStart(); }
			if (next) { next.disabled = !cfg.loop && atEnd(); }
		}

		function stop() { if (timer) { clearInterval(timer); timer = null; } }
		function start() {
			stop();
			if (!cfg.autoplay || reduce || maxLeft() <= 2) { return; }
			timer = setInterval(function () {
				if (hover || document.hidden || !document.documentElement.contains(root)) { return; }
				if (atEnd()) { goTo(0); } else { move(1); }
			}, cfg.delay || 3000);
		}
		function restart() { if (cfg.autoplay) { start(); } }

		if (prev) { prev.addEventListener('click', function () { move(-1); restart(); }); }
		if (next) { next.addEventListener('click', function () { move(1); restart(); }); }

		var raf = 0;
		track.addEventListener('scroll', function () {
			if (raf) { return; }
			raf = requestAnimationFrame(function () { raf = 0; update(); });
		}, { passive: true });

		track.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { e.preventDefault(); move(1); restart(); }
			else if (e.key === 'ArrowLeft') { e.preventDefault(); move(-1); restart(); }
		});

		if (cfg.pause) {
			root.addEventListener('mouseenter', function () { hover = true; });
			root.addEventListener('mouseleave', function () { hover = false; });
		}
		root.addEventListener('focusin', function () { hover = true; });
		root.addEventListener('focusout', function () { hover = cfg.pause ? root.matches(':hover') : false; });

		/* mouse drag (touch uses the native scrolling) */
		var down = false, moved = false, startX = 0, startL = 0, pid = null;
		track.addEventListener('pointerdown', function (e) {
			if (e.pointerType !== 'mouse' || e.button !== 0) { return; }
			down = true; moved = false; startX = e.clientX; startL = track.scrollLeft; pid = e.pointerId;
		});
		window.addEventListener('pointermove', function (e) {
			if (!down || e.pointerId !== pid) { return; }
			var dx = e.clientX - startX;
			if (!moved && Math.abs(dx) > 6) {
				moved = true;
				track.classList.add('is-dragging');
				try { track.setPointerCapture(pid); } catch (err) { /* not supported */ }
			}
			if (moved) { track.scrollLeft = startL - dx; }
		});
		function release(e) {
			if (!down || (e && e.pointerId !== pid)) { return; }
			down = false;
			if (moved) {
				track.classList.remove('is-dragging');
				/* snap to the nearest card */
				var i = Math.round(track.scrollLeft / stepW());
				goTo(i);
				restart();
			}
		}
		window.addEventListener('pointerup', release);
		window.addEventListener('pointercancel', release);
		track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
		track.addEventListener('dragstart', function (e) { e.preventDefault(); });

		var rt;
		function layout() { buildDots(); update(); start(); }
		window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(layout, 150); });
		window.addEventListener('load', layout);
		layout();
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.nxcg-sl').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		['nexvio_category_slider', 'nexvio_category_slider_layouts'].forEach(function (name) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', function ($scope) {
				if ($scope && $scope[0]) { initAll($scope[0]); }
			});
		});
	});
})();
