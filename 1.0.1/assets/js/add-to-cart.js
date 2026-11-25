/* Nexvio Add to Cart widget: quantity stepper + AJAX add to cart (WooCommerce wc-ajax). */
(function () {
	'use strict';

	function num(v, d) { var n = parseFloat(v); return isNaN(n) ? d : n; }

	function limits(input) {
		return { min: num(input.getAttribute('min'), 1), max: input.hasAttribute('max') ? num(input.getAttribute('max'), Infinity) : Infinity };
	}

	function clamp(input) {
		var l = limits(input), v = Math.round(num(input.value, l.min));
		v = Math.max(l.min, Math.min(l.max, v));
		input.value = v;
		var qty = input.closest('.nexvio-atc__qty');
		if (qty) {
			var btns = qty.querySelectorAll('.nexvio-atc__step');
			btns[0].disabled = v <= l.min;
			btns[1].disabled = v >= l.max;
		}
		return v;
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.nexvio-atc__input').forEach(clamp);
	}

	/* − / + */
	document.addEventListener('click', function (e) {
		var b = e.target.closest ? e.target.closest('.nexvio-atc__step') : null;
		if (!b) { return; }
		e.preventDefault();
		var input = b.closest('.nexvio-atc__qty').querySelector('.nexvio-atc__input');
		input.value = num(input.value, 1) + num(b.getAttribute('data-step'), 0);
		clamp(input);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});

	document.addEventListener('change', function (e) {
		if (e.target.classList && e.target.classList.contains('nexvio-atc__input')) { clamp(e.target); }
	});
	document.addEventListener('blur', function (e) {
		if (e.target.classList && e.target.classList.contains('nexvio-atc__input')) { clamp(e.target); }
	}, true);

	function jq() { return window.jQuery || null; }

	function applyFragments(fragments) {
		if (!fragments) { return; }
		Object.keys(fragments).forEach(function (sel) {
			try {
				document.querySelectorAll(sel).forEach(function (el) {
					var tmp = document.createElement('div');
					tmp.innerHTML = fragments[sel];
					if (tmp.firstElementChild) { el.replaceWith(tmp.firstElementChild); }
				});
			} catch (err) {}
		});
	}

	function message(root, html, isError) {
		var m = root.querySelector('.nexvio-atc__msg');
		if (!m) { return; }
		m.classList.toggle('is-error', !!isError);
		m.innerHTML = html;
	}

	function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

	/* AJAX add to cart */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form.classList || !form.classList.contains('nexvio-atc__form')) { return; }

		/* variable product (Product Showcase): a variation must be chosen first */
		var isVar = form.getAttribute('data-variable') === '1';
		if (isVar) {
			var vEl = form.elements['variation_id'];
			if (!vEl || !(parseInt(vEl.value, 10) > 0)) {
				e.preventDefault();
				var t0 = {};
				try { t0 = JSON.parse(form.getAttribute('data-texts') || '{}'); } catch (err0) {}
				message(form.closest('.nexvio-atc'), esc(t0.pick || 'Please choose a product option first.'), true);
				var vars = form.id ? document.querySelector('.nexvio-shw__vars[data-form="' + form.id + '"]') : null;
				if (vars) {
					Array.prototype.forEach.call(vars.querySelectorAll('.nexvio-shw__var'), function (g) {
						if (!g.getAttribute('data-value')) { g.classList.add('is-missing'); }
					});
				}
				/* Product Variations widget: highlight what is missing */
				document.dispatchEvent(new CustomEvent('nexvio:variation:missing', { detail: { product: form.getAttribute('data-product_id') } }));
				return;
			}
		}

		if (form.getAttribute('data-ajax') !== '1') { return; }
		e.preventDefault();

		var root = form.closest('.nexvio-atc');
		var btn = form.querySelector('.nexvio-atc__btn');
		var label = btn ? btn.querySelector('.nexvio-atc__label') : null;
		if (!btn || btn.classList.contains('is-loading')) { return; }

		var texts = {};
		try { texts = JSON.parse(form.getAttribute('data-texts') || '{}'); } catch (err) {}

		var input = form.querySelector('[name="quantity"]');
		var qty = input && input.classList.contains('nexvio-atc__input') ? clamp(input) : num(input ? input.value : 1, 1);
		var id = form.getAttribute('data-product_id');

		btn.classList.remove('is-added');
		btn.classList.add('is-loading');
		if (label && texts.adding) { label.textContent = texts.adding; }
		message(root, '');

		var $ = jq();
		if ($) { $(document.body).trigger('adding_to_cart', [$(btn), { product_id: id, quantity: qty }]); }

		var body = new URLSearchParams();
		body.append('product_id', id);
		body.append('quantity', qty);
		if (isVar) {
			body.append('variation_id', form.elements['variation_id'].value);
			Array.prototype.forEach.call(form.elements, function (el) {
				if (el.name && el.name.indexOf('attribute_') === 0) { body.append(el.name, el.value); }
			});
		}

		fetch(form.getAttribute('data-ajax-url'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || res.error) {
					/* WooCommerce refused (stock, validation): reload the product page so its notice shows */
					if (res && res.product_url) { window.location.href = res.product_url; return; }
					throw new Error('error');
				}
				var redirect = form.getAttribute('data-redirect');
				if (redirect) { window.location.href = redirect; return; }

				btn.classList.add('is-added');
				if (label) { label.textContent = texts.added || texts.label; }
				var cart = form.getAttribute('data-cart-url');
				message(root, esc(texts.added || '') + (texts.cart && cart ? '<a href="' + esc(cart) + '">' + esc(texts.cart) + '</a>' : ''));

				if ($) {
					/* updates mini-carts, Elementor menu cart, cart counters */
					$(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, $(btn)]);
					if (!window.wc_add_to_cart_params) { applyFragments(res.fragments); }
				} else {
					applyFragments(res.fragments);
				}

				setTimeout(function () {
					btn.classList.remove('is-added');
					if (label) { label.textContent = texts.label; }
				}, 2500);
			})
			.catch(function () {
				if (label) { label.textContent = texts.label; }
				message(root, esc(texts.error || 'Error'), true);
			})
			.then(function () { btn.classList.remove('is-loading'); });
	});

	/* ---------------------------------------------------------------
	   Variable product with the Product Variations widget:
	   that widget sends "nexvio:variation" (chosen variation + attributes);
	   the hidden fields, the button and the quantity limits follow it.
	   --------------------------------------------------------------- */
	function varTexts(form) {
		var t = {};
		try { t = JSON.parse(form.getAttribute('data-texts') || '{}'); } catch (e) { t = {}; }
		return t;
	}

	function applyVariation(form, d) {
		var vEl = form.elements['variation_id'];
		if (!vEl) { return; }
		vEl.value = d && d.variation ? d.variation : 0;

		/* the chosen attribute values travel with the form (also "any" attributes) */
		Array.prototype.slice.call(form.querySelectorAll('input[data-nx-attr]')).forEach(function (n) { n.remove(); });
		var sel = d && d.selection ? d.selection : {};
		Object.keys(sel).forEach(function (k) {
			if (sel[k] === '') { return; }
			var h = document.createElement('input');
			h.type = 'hidden';
			h.name = k;
			h.value = sel[k];
			h.setAttribute('data-nx-attr', '1');
			form.appendChild(h);
		});

		var root = form.closest('.nexvio-atc');
		if (root && vEl.value > 0) { message(root, ''); }

		if (form.getAttribute('data-avail') === '0') { return; }
		var btn = form.querySelector('.nexvio-atc__btn');
		var label = btn ? btn.querySelector('.nexvio-atc__label') : null;
		var texts = varTexts(form);
		if (btn && !btn.classList.contains('is-loading') && !btn.classList.contains('is-added')) {
			var bad = false, txt = texts.label || '';
			if (d && d.complete) {
				if (!d.data) { bad = true; txt = texts.unavail || texts.out || txt; }
				else if (!d.data.stock) { bad = true; txt = texts.out || txt; }
			}
			btn.disabled = bad;
			btn.classList.toggle('is-disabled', bad);
			if (bad) { btn.setAttribute('aria-disabled', 'true'); } else { btn.removeAttribute('aria-disabled'); }
			if (label && txt) { label.textContent = txt; }
		}

		var q = form.querySelector('input.nexvio-atc__input');
		if (q && d && d.data) {
			q.setAttribute('min', d.data.min > 0 ? d.data.min : 1);
			if (d.data.max > 0) { q.setAttribute('max', d.data.max); } else { q.removeAttribute('max'); }
			clamp(q);
		}
	}

	function syncVariations(scope) {
		Array.prototype.slice.call((scope || document).querySelectorAll('.nexvio-atc__form[data-variable="1"]')).forEach(function (form) {
			var st = window.nexvioVarState ? window.nexvioVarState[form.getAttribute('data-product_id')] : null;
			if (st) { applyVariation(form, st); }
		});
	}

	document.addEventListener('nexvio:variation', function (e) {
		var d = e.detail || {};
		Array.prototype.slice.call(document.querySelectorAll('.nexvio-atc__form[data-variable="1"]')).forEach(function (form) {
			if (String(form.getAttribute('data-product_id')) === String(d.product)) { applyVariation(form, d); }
		});
	});

	/* variable / grouped / external products with the WooCommerce form: the heart sits in the row of the button */
	function placeHeart(scope) {
		Array.prototype.slice.call((scope || document).querySelectorAll('.nexvio-atc--native')).forEach(function (root) {
			var heart = root.querySelector(':scope > .nexvio-atc__wish');
			if (!heart) { return; }
			var row = root.querySelector('.woocommerce-variation-add-to-cart') || root.querySelector('form.cart');
			if (row) { row.appendChild(heart); }
		});
	}

	function initWidget(scope) {
		initAll(scope);
		placeHeart(scope);
		syncVariations(scope);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initWidget(); });
	} else {
		initWidget();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_add_to_cart.default', function ($scope) {
			if ($scope && $scope[0]) { initWidget($scope[0]); }
		});
	});
})();
