/* Nexvio: side cart, header / floating cart counters, AJAX add to cart on product pages. */
/* Source: https://github.com/mdfarhan01/nexvio-for-woocommerce (this file is not minified; edit it directly). */
(function () {
	'use strict';

	var C = window.NEXVIO_CART || {};
	var root = null,
		panel = null,
		inner = null,
		opener = null;
	var nonce = C.nonce || '';
	var skipNextAdded = false;
	var timers = {};

	function $q() {
		return window.jQuery || null;
	}

	/* The panel opens only because of something the visitor did on this page (clicking a cart icon,
	 * adding a product). Never on its own, and never inside the Elementor editor / preview, where
	 * saving or re-rendering widgets must not pop it open. */
	var lastGesture = 0;
	['click', 'pointerdown', 'keydown', 'touchstart', 'submit'].forEach(function (name) {
		document.addEventListener(
			name,
			function (e) {
				if (e.isTrusted) {
					lastGesture = Date.now();
				}
			},
			true
		);
	});
	function userActed() {
		return Date.now() - lastGesture < 15000;
	}
	function inEditor() {
		return (
			!!(document.body && document.body.classList.contains('elementor-editor-active')) ||
			/[?&]elementor-preview=/.test(window.location.search) ||
			!!(
				window.elementorFrontend &&
				window.elementorFrontend.isEditMode &&
				window.elementorFrontend.isEditMode()
			)
		);
	}
	function url(endpoint) {
		return (C.ajax || '').replace('%%endpoint%%', endpoint);
	}

	/* ------------------------------------------------------------------
	 * Requests (retry once with a fresh nonce)
	 * ------------------------------------------------------------------ */
	function post(endpoint, data, retried) {
		var body = data instanceof FormData ? data : new FormData();
		if (!(data instanceof FormData)) {
			Object.keys(data || {}).forEach(function (k) {
				body.append(k, data[k]);
			});
		}
		body.set('nonce', nonce);
		return fetch(url(endpoint), { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) {
				return r.json().catch(function () {
					return { success: false, data: {} };
				});
			})
			.then(function (res) {
				var d = (res && res.data) || {};
				if (d.nonce) {
					nonce = d.nonce;
				}
				if (!res.success && d.retry && !retried) {
					return post(endpoint, body, true);
				}
				return res;
			});
	}

	/* ------------------------------------------------------------------
	 * Counters (header cart, floating cart, panel badge)
	 * ------------------------------------------------------------------ */
	function setCounts(state) {
		document.querySelectorAll('.nexvio-cart-count').forEach(function (el) {
			var mode = el.getAttribute('data-count-mode');
			var n = mode === 'lines' ? state.lines : mode === 'quantity' ? state.qty : state.count;
			n = parseInt(n, 10) || 0;
			if (String(el.textContent) !== String(n)) {
				el.textContent = n;
				el.classList.remove('is-bump');
				void el.offsetWidth;
				el.classList.add('is-bump');
				var fc = el.closest('.nexvio-fc');
				if (fc && n > 0) {
					fc.classList.remove('is-pulse');
					void fc.offsetWidth;
					fc.classList.add('is-pulse');
				}
			}
			el.classList.toggle('is-zero-hidden', el.getAttribute('data-hide-zero') === '1' && n < 1);
			var f = el.closest('.nexvio-fc');
			if (f) {
				f.classList.toggle('is-empty', n < 1);
			}
		});
	}

	function applyFragments(fr) {
		if (!fr) {
			return;
		}
		Object.keys(fr).forEach(function (sel) {
			document.querySelectorAll(sel).forEach(function (el) {
				var t = document.createElement('div');
				t.innerHTML = fr[sel];
				if (t.firstElementChild) {
					el.replaceWith(t.firstElementChild);
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Panel
	 * ------------------------------------------------------------------ */
	function bind() {
		root = document.getElementById('nexvio-side-cart');
		if (!root) {
			return;
		}
		panel = root.querySelector('.nexvio-sc__panel');
		inner = root.querySelector('.nexvio-sc__inner');
	}

	function render(state, messages) {
		if (!state) {
			return;
		}
		setCounts(state);
		if (!inner || typeof state.html !== 'string') {
			return;
		}
		var couponOpen = !!inner.querySelector('.nexvio-sc__coupon[open]');
		var scroll = inner.querySelector('.nexvio-sc__body');
		var top = scroll ? scroll.scrollTop : 0;
		inner.innerHTML = state.html;
		var body = inner.querySelector('.nexvio-sc__body');
		if (body) {
			body.scrollTop = top;
		}
		var list = messages || state.messages || [];
		if (couponOpen || (list.length && state.couponError)) {
			var d = inner.querySelector('.nexvio-sc__coupon');
			if (d) {
				d.open = true;
			}
		}
		showMessages(list);
	}

	function showMessages(list) {
		var box = inner ? inner.querySelector('.nexvio-sc__msgs') : null;
		if (!box) {
			return;
		}
		box.innerHTML = '';
		(list || []).forEach(function (m) {
			if (!m || !m.text) {
				return;
			}
			var p = document.createElement('p');
			p.className = 'nexvio-sc__msg nexvio-sc__msg--' + (m.type || 'notice');
			p.textContent = m.text;
			box.appendChild(p);
		});
	}

	function loading(on) {
		if (root) {
			root.classList.toggle('is-loading', !!on);
		}
	}

	function refresh(silent) {
		if (!silent) {
			loading(true);
		}
		return fetch(url('nexvio_cart_get'), { method: 'POST', credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (res) {
				if (res && res.success) {
					if (res.data.nonce) {
						nonce = res.data.nonce;
					}
					render(res.data);
				}
				return res;
			})
			.catch(function () {})
			.then(function (res) {
				loading(false);
				return res;
			});
	}

	function isOpen() {
		return root && root.classList.contains('is-open');
	}

	function open(from) {
		if (!root) {
			return;
		}
		opener = from || document.activeElement;
		root.removeAttribute('inert');
		root.setAttribute('aria-hidden', 'false');
		var sb = window.innerWidth - document.documentElement.clientWidth;
		document.documentElement.style.setProperty('--nexvio-sb', sb + 'px');
		document.documentElement.classList.add('nexvio-sc-lock');
		root.classList.add('is-open');
		window.setTimeout(function () {
			if (panel) {
				panel.focus({ preventScroll: true });
			}
		}, 30);
		refresh(false);
	}

	function close() {
		if (!isOpen()) {
			return;
		}
		root.classList.remove('is-open');
		root.setAttribute('aria-hidden', 'true');
		root.setAttribute('inert', '');
		document.documentElement.classList.remove('nexvio-sc-lock');
		if (opener && opener.focus) {
			opener.focus({ preventScroll: true });
		}
	}

	/* ------------------------------------------------------------------
	 * Cart actions inside the panel
	 * ------------------------------------------------------------------ */
	function act(endpoint, data, li) {
		loading(true);
		if (li) {
			li.classList.add('is-busy');
		}
		return post(endpoint, data)
			.then(function (res) {
				var d = (res && res.data) || {};
				if (d.html !== undefined) {
					d.couponError = !res.success && endpoint === 'nexvio_cart_coupon';
					render(d, d.messages);
				} else if (!res.success) {
					showMessages([{ type: 'error', text: C.i18n.error }]);
				}
				var $ = $q();
				if ($ && res.success) {
					if (d.fragments) {
						applyFragments(d.fragments);
					}
					$(document.body).trigger('nexvio_cart_updated', [d]);
				}
				return res;
			})
			.catch(function () {
				showMessages([{ type: 'error', text: C.i18n.error }]);
			})
			.then(function (r) {
				loading(false);
				if (li) {
					li.classList.remove('is-busy');
				}
				return r;
			});
	}

	function setQty(li, qty) {
		var key = li.getAttribute('data-key');
		clearTimeout(timers[key]);
		timers[key] = setTimeout(function () {
			act('nexvio_cart_qty', { key: key, qty: qty }, li);
		}, 380);
	}

	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t.closest) {
			return;
		}

		var trigger = t.closest('[data-nexvio-cart-open]');
		if (trigger && C.enabled && root && !inEditor()) {
			e.preventDefault();
			open(trigger);
			return;
		}
		if (!root || !root.contains(t)) {
			return;
		}

		if (t.closest('[data-nexvio-cart-close]')) {
			e.preventDefault();
			close();
			return;
		}
		if (t.closest('[data-nexvio-cart-overlay]')) {
			if (C.closeOverlay) {
				close();
			}
			return;
		}

		var q = t.closest('[data-nexvio-qty]');
		if (q) {
			var li = q.closest('.nexvio-sc__item');
			var input = li.querySelector('.nexvio-sc__qty input');
			var max = parseInt(input.getAttribute('max'), 10) || Infinity;
			var v = Math.min(
				max,
				Math.max(
					1,
					(parseInt(input.value, 10) || 1) + parseInt(q.getAttribute('data-nexvio-qty'), 10)
				)
			);
			input.value = v;
			li.querySelector('[data-nexvio-qty="-1"]').disabled = v <= 1;
			li.querySelector('[data-nexvio-qty="1"]').disabled = v >= max;
			setQty(li, v);
			return;
		}
		var rm = t.closest('[data-nexvio-remove]');
		if (rm) {
			var item = rm.closest('.nexvio-sc__item');
			item.classList.add('is-leaving');
			act('nexvio_cart_remove', { key: item.getAttribute('data-key') }, item);
			return;
		}
		var cr = t.closest('[data-nexvio-coupon-remove]');
		if (cr) {
			act('nexvio_cart_coupon_remove', { code: cr.getAttribute('data-nexvio-coupon-remove') });
		}
	});

	document.addEventListener('change', function (e) {
		var input = e.target;
		if (!root || !root.contains(input) || !input.closest('.nexvio-sc__qty')) {
			return;
		}
		var li = input.closest('.nexvio-sc__item');
		var max = parseInt(input.getAttribute('max'), 10) || Infinity;
		var v = Math.min(max, Math.max(1, parseInt(input.value, 10) || 1));
		input.value = v;
		setQty(li, v);
	});

	document.addEventListener('keydown', function (e) {
		if (!isOpen()) {
			return;
		}
		if (e.key === 'Escape' && C.closeEsc) {
			close();
			return;
		}
		if (e.key === 'Tab' && panel) {
			/* keep keyboard focus inside the open panel */
			var f = panel.querySelectorAll(
				'a[href],button:not([disabled]),input,summary,[tabindex]:not([tabindex="-1"])'
			);
			if (!f.length) {
				return;
			}
			var first = f[0],
				last = f[f.length - 1];
			if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});

	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (root && f.matches && f.matches('[data-nexvio-coupon]')) {
			e.preventDefault();
			var code = (f.querySelector('input[name="coupon_code"]') || {}).value || '';
			if (!code.trim()) {
				showMessages([{ type: 'error', text: C.i18n.coupon }]);
				return;
			}
			act('nexvio_cart_coupon', { code: code.trim() });
			return;
		}
		singleProductSubmit(e);
	});

	/* ------------------------------------------------------------------
	 * Open after adding
	 * ------------------------------------------------------------------ */
	function wantsOpen(type, el) {
		if (!C.enabled || !root || inEditor() || !userActed()) {
			return false;
		}
		var pref = el && el.closest ? el.closest('[data-nexvio-side]') : null;
		var p = pref ? pref.getAttribute('data-nexvio-side') : 'global';
		if (p === 'open') {
			return true;
		}
		if (p === 'no') {
			return false;
		}
		return !!C.autoOpen && (!type || (C.openTypes || []).indexOf(type) > -1);
	}

	function typeFromButton($btn) {
		var el = $btn && $btn[0] ? $btn[0] : null;
		if (!el) {
			return '';
		}
		var m = (el.className || '').match(/product_type_([a-z_-]+)/);
		if (m) {
			return m[1];
		}
		return el.closest && el.closest('.nexvio-atc') ? 'simple' : '';
	}

	/* ------------------------------------------------------------------
	 * Product pages: add simple and variable products without reloading
	 * ------------------------------------------------------------------ */
	function inlineMessage(form, text) {
		var box = form.parentNode.querySelector('.nexvio-atc-msg');
		if (!box) {
			box = document.createElement('div');
			box.className = 'nexvio-atc-msg';
			box.setAttribute('role', 'alert');
			form.parentNode.insertBefore(box, form.nextSibling);
		}
		box.textContent = text || '';
		box.hidden = !text;
	}

	function singleProductSubmit(e) {
		var form = e.target;
		if (e.defaultPrevented || !C.enabled || !C.ajaxSingle || !root) {
			return;
		}
		if (
			!form.matches ||
			!form.matches('form.cart') ||
			form.classList.contains('grouped_form') ||
			form.classList.contains('nexvio-atc__form')
		) {
			return;
		}
		if (form.getAttribute('data-nexvio-native') === '1') {
			return;
		}
		var submitter = e.submitter || form.querySelector('[type="submit"]');
		var id =
			(form.querySelector('input[name="add-to-cart"]') || {}).value ||
			(submitter && submitter.name === 'add-to-cart' ? submitter.value : '') ||
			form.getAttribute('data-product_id') ||
			'';
		if (!id) {
			return;
		}
		e.preventDefault();
		if (form.getAttribute('data-nexvio-busy') === '1') {
			return;
		} /* no double submit */

		var isVariable = form.classList.contains('variations_form');
		var vid = form.querySelector('input[name="variation_id"]');
		if (isVariable && (!vid || !parseInt(vid.value, 10))) {
			inlineMessage(form, C.i18n.options);
			return;
		}

		var data = new FormData(form);
		data.delete('add-to-cart'); /* never let WooCommerce's own handler add it a second time */
		data.set('nexvio_product_id', id);
		var btn = submitter || form.querySelector('.single_add_to_cart_button');
		form.setAttribute('data-nexvio-busy', '1');
		if (btn) {
			btn.classList.add('loading');
			btn.setAttribute('aria-busy', 'true');
			btn.disabled = true;
		}
		inlineMessage(form, '');

		post('nexvio_cart_add', data)
			.then(function (res) {
				var d = (res && res.data) || {};
				if (res.success) {
					render(d);
					var $ = $q();
					if ($) {
						skipNextAdded = true;
						$(document.body).trigger('added_to_cart', [d.fragments, d.cart_hash, $(btn)]);
					} else {
						applyFragments(d.fragments);
					}
					if (btn) {
						btn.classList.add('added');
					}
					if (wantsOpen(d.type || (isVariable ? 'variable' : 'simple'), form)) {
						open(btn);
					}
					return;
				}
				if (d.fallback) {
					/* not supported here: let WooCommerce handle it normally */
					var h = document.createElement('input');
					h.type = 'hidden';
					h.name = 'add-to-cart';
					h.value = id;
					form.appendChild(h);
					form.setAttribute('data-nexvio-native', '1');
					HTMLFormElement.prototype.submit.call(form);
					return;
				}
				var msg =
					(d.messages || [])
						.map(function (m) {
							return m.text;
						})
						.join(' ') || C.i18n.error;
				inlineMessage(form, msg);
			})
			.catch(function () {
				inlineMessage(form, C.i18n.error);
			})
			.then(function () {
				form.removeAttribute('data-nexvio-busy');
				if (btn) {
					btn.classList.remove('loading');
					btn.removeAttribute('aria-busy');
					btn.disabled = false;
				}
			});
	}

	/* ------------------------------------------------------------------
	 * WooCommerce events (product cards, Add to Cart widget, cart page)
	 * ------------------------------------------------------------------ */
	function hookJquery() {
		var $ = $q();
		if (!$) {
			return;
		}
		$(document.body).on('added_to_cart', function (ev, fragments, hash, $btn) {
			if (skipNextAdded) {
				skipNextAdded = false;
				return;
			}
			var type = typeFromButton($btn);
			if (wantsOpen(type, $btn && $btn[0])) {
				open($btn && $btn[0]);
			} else {
				refresh(true);
			}
		});
		$(document.body).on('removed_from_cart updated_cart_totals wc_fragments_refreshed', function () {
			refresh(true);
		});
	}

	/* ------------------------------------------------------------------
	 * Start: counters are right even on cached pages
	 * ------------------------------------------------------------------ */
	function start() {
		bind();
		hookJquery();
		var counters = document.querySelectorAll('.nexvio-cart-count');
		if (!counters.length && !root) {
			return;
		}
		var hasCart = /(?:^|;\s*)woocommerce_items_in_cart=1/.test(document.cookie);
		if (hasCart) {
			refresh(true);
		} else {
			setCounts({ count: 0, qty: 0, lines: 0 });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) {
			refresh(true);
		}
	});

	/* public */
	window.NEXVIOCart = { open: open, close: close, refresh: refresh };
})();
