/* Nexvio Header Search: the icon opens the search.
   Layout (dropdown / bar / modal / fullscreen / slide / inline) is chosen per device
   by the data-layout* attributes and applied when the search opens and on resize. */
(function () {
	'use strict';

	var LAYOUTS = ['dropdown', 'bar', 'modal', 'fullscreen', 'slide', 'inline'];
	var OVERLAY = { bar: 1, modal: 1, fullscreen: 1, slide: 1 };
	var MOBILE_CSS = 767; /* same breakpoint as header-search.css */

	function breakpoints() {
		var t = 1024, m = 767;
		try {
			var b = window.elementorFrontend.config.responsive.activeBreakpoints;
			if (b.mobile && b.mobile.value) { m = b.mobile.value; }
			if (b.tablet && b.tablet.value) { t = b.tablet.value; }
		} catch (e) { /* default Elementor breakpoints */ }
		return { t: t, m: m };
	}

	function layoutFor(hs) {
		var b = breakpoints(), w = window.innerWidth, d = hs.dataset;
		var v = w <= b.m ? d.layoutMobile : (w <= b.t ? d.layoutTablet : d.layout);
		v = v || d.layout;
		return LAYOUTS.indexOf(v) > -1 ? v : 'dropdown';
	}

	function applyLayout(hs) {
		var l = layoutFor(hs);
		LAYOUTS.forEach(function (x) { hs.classList.remove('nexvio-hs--' + x); });
		hs.classList.add('nexvio-hs--' + l);
		hs.dataset.active = l;
		return l;
	}

	function syncLock(hs) {
		var on = hs.classList.contains('is-open') && OVERLAY[hs.dataset.active];
		if (on) {
			document.documentElement.classList.add('nexvio-hs-lock');
		} else if (!document.querySelector('.nexvio-hs.is-open.nexvio-hs--bar,.nexvio-hs.is-open.nexvio-hs--modal,.nexvio-hs.is-open.nexvio-hs--fullscreen,.nexvio-hs.is-open.nexvio-hs--slide')) {
			document.documentElement.classList.remove('nexvio-hs-lock');
		}
	}

	/* keep dropdown / inline panels inside the screen */
	function place(hs) {
		var panel = hs.querySelector('.nexvio-hs__panel');
		var toggle = hs.querySelector('.nexvio-hs__toggle');
		if (!panel || !toggle || !hs.classList.contains('is-open')) { return; }
		var l = hs.dataset.active;
		panel.style.translate = '';
		hs.style.removeProperty('--nexvio-hs-top');
		if (l !== 'dropdown' && l !== 'inline') { return; }
		var r = toggle.getBoundingClientRect();
		if (window.innerWidth <= MOBILE_CSS) {
			var off = parseFloat(getComputedStyle(hs).getPropertyValue('--nexvio-hs-offset')) || 0;
			hs.style.setProperty('--nexvio-hs-top', Math.max(0, l === 'inline' ? r.top : r.bottom + off) + 'px');
			return;
		}
		var p = panel.getBoundingClientRect();
		var vw = document.documentElement.clientWidth, dx = 0;
		if (p.left < 12) { dx = 12 - p.left; } else if (p.right > vw - 12) { dx = vw - 12 - p.right; }
		if (dx) { panel.style.translate = dx + 'px 0'; }
	}

	function close(hs, focusToggle) {
		if (!hs || !hs.classList.contains('is-open')) { return; }
		hs.classList.remove('is-open');
		var t = hs.querySelector('.nexvio-hs__toggle');
		var p = hs.querySelector('.nexvio-hs__panel');
		if (t) { t.setAttribute('aria-expanded', 'false'); if (focusToggle) { t.focus(); } }
		if (p) { p.hidden = true; p.style.translate = ''; }
		hs.style.removeProperty('--nexvio-hs-top');
		syncLock(hs);
	}

	function open(hs) {
		document.querySelectorAll('.nexvio-hs.is-open').forEach(function (o) { if (o !== hs) { close(o); } });
		applyLayout(hs);
		hs.classList.add('is-open');
		var t = hs.querySelector('.nexvio-hs__toggle');
		var p = hs.querySelector('.nexvio-hs__panel');
		if (t) { t.setAttribute('aria-expanded', 'true'); }
		if (p) { p.hidden = false; }
		syncLock(hs);
		place(hs);
		var input = hs.querySelector('.nexvio-hs__input');
		if (input) {
			setTimeout(function () {
				try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); }
				var v = input.value; input.value = ''; input.value = v; /* caret at the end */
			}, 30);
		}
	}

	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t.closest) { return; }
		var toggle = t.closest('.nexvio-hs__toggle');
		if (toggle) {
			e.preventDefault();
			var hs = toggle.closest('.nexvio-hs');
			if (hs.classList.contains('is-open')) { close(hs); } else { open(hs); }
			return;
		}
		var x = t.closest('.nexvio-hs__close');
		if (x) { e.preventDefault(); close(x.closest('.nexvio-hs'), true); return; }

		/* click outside (or on the overlay) closes */
		document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) {
			var form = hs.querySelector('.nexvio-hs__form');
			if (form && !form.contains(t) && !hs.querySelector('.nexvio-hs__toggle').contains(t)) { close(hs); }
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) { close(hs, true); });
		}
	});

	/* rotate / resize / scroll: switch to the layout of the new device and re-fit the panel */
	var raf = 0, timer = 0;
	function refit() {
		cancelAnimationFrame(raf);
		raf = requestAnimationFrame(function () {
			document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) { place(hs); });
		});
	}
	window.addEventListener('scroll', refit, { passive: true });
	window.addEventListener('resize', function () {
		clearTimeout(timer);
		timer = setTimeout(function () {
			document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) {
				applyLayout(hs);
				syncLock(hs);
				place(hs);
			});
		}, 80);
	});

	/* empty search: don't send "?nexvio_q=" for nothing, just go to the page */
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (!f.classList || !f.classList.contains('nexvio-hs__form') || e.defaultPrevented) { return; }
		var input = f.querySelector('.nexvio-hs__input');
		if (input) { input.value = input.value.trim(); }
	});
})();
