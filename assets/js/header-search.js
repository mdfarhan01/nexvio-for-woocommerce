/* Nexvio Header Search: icon opens the search bar. */
/* Source: https://github.com/mdfarhan01/nexvio-for-woocommerce (this file is not minified; edit it directly). */
(function () {
	'use strict';

	function close(hs, focusToggle) {
		if (!hs || !hs.classList.contains('is-open')) {
			return;
		}
		hs.classList.remove('is-open');
		var t = hs.querySelector('.nexvio-hs__toggle');
		var p = hs.querySelector('.nexvio-hs__panel');
		if (t) {
			t.setAttribute('aria-expanded', 'false');
			if (focusToggle) {
				t.focus();
			}
		}
		if (p) {
			p.hidden = true;
		}
		document.documentElement.classList.remove('nexvio-hs-lock');
	}

	function open(hs) {
		document.querySelectorAll('.nexvio-hs.is-open').forEach(function (o) {
			if (o !== hs) {
				close(o);
			}
		});
		hs.classList.add('is-open');
		var t = hs.querySelector('.nexvio-hs__toggle');
		var p = hs.querySelector('.nexvio-hs__panel');
		if (t) {
			t.setAttribute('aria-expanded', 'true');
		}
		if (p) {
			p.hidden = false;
		}
		if (hs.classList.contains('nexvio-hs--bar')) {
			document.documentElement.classList.add('nexvio-hs-lock');
		}
		var input = hs.querySelector('.nexvio-hs__input');
		if (input) {
			setTimeout(function () {
				input.focus();
				var v = input.value;
				input.value = '';
				input.value = v; /* caret at the end */
			}, 30);
		}
	}

	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t.closest) {
			return;
		}
		var toggle = t.closest('.nexvio-hs__toggle');
		if (toggle) {
			e.preventDefault();
			var hs = toggle.closest('.nexvio-hs');
			if (hs.classList.contains('is-open')) {
				close(hs);
			} else {
				open(hs);
			}
			return;
		}
		var x = t.closest('.nexvio-hs__close');
		if (x) {
			e.preventDefault();
			close(x.closest('.nexvio-hs'), true);
			return;
		}

		/* click outside (or on the bar overlay) closes */
		document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) {
			var form = hs.querySelector('.nexvio-hs__form');
			if (form && !form.contains(t) && !hs.querySelector('.nexvio-hs__toggle').contains(t)) {
				close(hs);
			}
		});
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			document.querySelectorAll('.nexvio-hs.is-open').forEach(function (hs) {
				close(hs, true);
			});
		}
	});

	/* empty search: don't send "?nexvio_q=" for nothing, just go to the page */
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (!f.classList || !f.classList.contains('nexvio-hs__form') || e.defaultPrevented) {
			return;
		}
		var input = f.querySelector('.nexvio-hs__input');
		if (input) {
			input.value = input.value.trim();
		}
	});
})();
