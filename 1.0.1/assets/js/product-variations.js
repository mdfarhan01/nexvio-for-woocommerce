/* Nexvio Product Variations: choose colour, size… -> find the variation -> tell the other widgets.
   Every variation is in the page as JSON (data-variations), so no request is needed.
   The Add to Cart and Product Images widgets listen to the "nexvio:variation" event on the document:
   detail = { product, variation, selection, complete, available, data, image }. */
(function () {
	'use strict';

	var STATE = window.nexvioVarState = window.nexvioVarState || {};

	function low(x) { return String(x == null ? '' : x).toLowerCase(); }
	function $$(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }

	function vdata(root) {
		if (!root.__vd) {
			try { root.__vd = JSON.parse(root.getAttribute('data-variations') || '[]'); } catch (e) { root.__vd = []; }
		}
		return root.__vd;
	}

	function cfg(root) {
		if (!root.__cfg) {
			try { root.__cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { root.__cfg = {}; }
		}
		return root.__cfg;
	}

	function groups(root) { return $$(root, '.nexvio-var__group'); }

	function selection(root) {
		var o = {};
		groups(root).forEach(function (g) { o[g.getAttribute('data-attr')] = g.getAttribute('data-value') || ''; });
		return o;
	}

	/* does this variation fit the chosen values? (an empty choice or an "any" attribute fits everything) */
	function fits(d, sel) {
		for (var k in sel) {
			if (!Object.prototype.hasOwnProperty.call(sel, k) || sel[k] === '') { continue; }
			var a = d.attrs ? d.attrs[k] : undefined;
			if (a === undefined || a === '' || a === null) { continue; }
			if (low(a) !== low(sel[k])) { return false; }
		}
		return true;
	}

	function exactMatch(data, sel) {
		for (var k in sel) {
			if (Object.prototype.hasOwnProperty.call(sel, k) && sel[k] === '') { return null; }
		}
		for (var i = 0; i < data.length; i++) {
			if (fits(data[i], sel)) { return data[i]; }
		}
		return null;
	}

	/* image of the chosen value even before every attribute is chosen: only when all fitting variations share one image */
	function sharedImage(cands) {
		var src = null, pick = null;
		for (var i = 0; i < cands.length; i++) {
			var im = cands[i].img;
			if (!im) { return null; }
			if (src === null) { src = im.src; pick = im; } else if (im.src !== src) { return null; }
		}
		return pick;
	}

	function setGroup(g, val) {
		g.setAttribute('data-value', val);
		if (val) { g.classList.remove('is-missing'); }
		var want = low(val);
		$$(g, '.nexvio-var__opt').forEach(function (el) {
			var on = want !== '' && low(el.getAttribute('data-value')) === want;
			el.classList.toggle('is-selected', on);
			if (el.tagName === 'BUTTON') { el.setAttribute('aria-pressed', on ? 'true' : 'false'); }
			var r = el.querySelector('input[type="radio"]');
			if (r) { r.checked = on; }
		});
		var sel = g.querySelector('select.nexvio-var__select');
		if (sel) {
			var found = '';
			Array.prototype.forEach.call(sel.options, function (o) { if (want !== '' && low(o.value) === want) { found = o.value; } });
			sel.value = found;
		}
	}

	function refresh(root) {
		var data = vdata(root);
		var conf = cfg(root);
		var sel = selection(root);
		var keys = Object.keys(sel);
		var match = exactMatch(data, sel);
		var chosen = keys.some(function (k) { return sel[k] !== ''; });
		var complete = keys.every(function (k) { return sel[k] !== ''; });
		var cands = data.filter(function (d) { return fits(d, sel); });

		/* which values can still be picked */
		groups(root).forEach(function (g) {
			var key = g.getAttribute('data-attr');
			$$(g, '.nexvio-var__opt, option').forEach(function (el) {
				var val = el.tagName === 'OPTION' ? el.value : el.getAttribute('data-value');
				if (val === '' || val === null) { return; }
				var s2 = {};
				for (var k in sel) { if (Object.prototype.hasOwnProperty.call(sel, k)) { s2[k] = sel[k]; } }
				s2[key] = val;
				var ok = data.some(function (d) { return d.stock && fits(d, s2); });
				el.classList.toggle('is-unavailable', !ok);
				if (el.tagName === 'OPTION') { el.hidden = !ok && root.getAttribute('data-unav') === 'hide'; }
			});
		});

		/* clear link */
		var clear = root.querySelector('.nexvio-var__clear');
		if (clear) { clear.hidden = !chosen; }

		/* price of the chosen variation */
		var pe = root.querySelector('.nexvio-var__price');
		if (pe) {
			var html = '';
			if (match && match.price) {
				html = match.price;
			} else if (conf.priceAll) {
				/* not complete yet: when every fitting variation has the same price show it, else the product price range */
				var same = cands.length > 0 && cands.every(function (d) { return d.price === cands[0].price; });
				html = same ? cands[0].price : conf.priceAll;
			}
			if (html) { pe.innerHTML = html; pe.hidden = false; } else { pe.innerHTML = ''; pe.hidden = true; }
		}

		/* stock line */
		var av = root.querySelector('.nexvio-var__avail');
		if (av) {
			var val = av.querySelector('.nexvio-var__avail-val');
			if (match) {
				val.textContent = match.avail || '';
				val.className = 'nexvio-var__avail-val ' + (match.stock ? 'is-in' : 'is-out');
				av.hidden = false;
			} else if (complete) {
				val.textContent = (conf.texts && conf.texts.na) || '';
				val.className = 'nexvio-var__avail-val is-out';
				av.hidden = false;
			} else {
				av.hidden = true;
			}
		}

		var image = null;
		if (conf.img) { image = match && match.img ? match.img : (chosen ? sharedImage(cands) : null); }

		var detail = {
			product: String(conf.product || root.getAttribute('data-product') || ''),
			variation: match ? match.id : 0,
			selection: sel,
			complete: complete,
			available: !!(match && match.stock),
			data: match || null,
			image: image
		};
		STATE[detail.product] = detail;
		document.dispatchEvent(new CustomEvent('nexvio:variation', { detail: detail }));
	}

	function choose(root, g, val) {
		if (!root || !g) { return; }
		setGroup(g, val);
		refresh(root);
	}

	function initAll(scope) {
		$$(scope || document, '.nexvio-var[data-variations]:not([data-ready])').forEach(function (root) {
			root.setAttribute('data-ready', '1');
			groups(root).forEach(function (g) { setGroup(g, g.getAttribute('data-value') || ''); });
			refresh(root);
		});
	}

	/* buttons, swatches */
	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t || !t.closest) { return; }
		var o = t.closest('.nexvio-var__opt--btn, .nexvio-var__opt--sw');
		if (o) {
			e.preventDefault();
			var root = o.closest('.nexvio-var');
			var val = o.getAttribute('data-value') || '';
			/* clicking the chosen value again clears it */
			if (o.classList.contains('is-selected')) { val = ''; }
			choose(root, o.closest('.nexvio-var__group'), val);
			return;
		}
		var c = t.closest('.nexvio-var__clear');
		if (c) {
			e.preventDefault();
			var r = c.closest('.nexvio-var');
			groups(r).forEach(function (g) { setGroup(g, ''); });
			refresh(r);
		}
	});

	/* dropdown, checkbox style */
	document.addEventListener('change', function (e) {
		var t = e.target;
		if (!t || !t.closest) { return; }
		var root = t.closest('.nexvio-var');
		if (!root) { return; }
		var g = t.closest('.nexvio-var__group');
		if (t.classList.contains('nexvio-var__select')) {
			choose(root, g, t.value);
		} else if (t.type === 'radio' && t.closest('.nexvio-var__opt--check')) {
			choose(root, g, t.value);
		}
	});

	/* Add to Cart was clicked before everything was chosen: show what is missing */
	document.addEventListener('nexvio:variation:missing', function (e) {
		var pid = String(e.detail && e.detail.product != null ? e.detail.product : '');
		var first = null;
		$$(document, '.nexvio-var[data-variations]').forEach(function (root) {
			if (pid && String(cfg(root).product) !== pid) { return; }
			groups(root).forEach(function (g) {
				if (!g.getAttribute('data-value')) {
					g.classList.remove('is-missing');
					void g.offsetWidth; /* restart the shake */
					g.classList.add('is-missing');
					if (!first) { first = g; }
				}
			});
		});
		if (first && first.getBoundingClientRect) {
			var r = first.getBoundingClientRect();
			if (r.top < 0 || r.bottom > (window.innerHeight || document.documentElement.clientHeight)) {
				first.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_variations.default', function ($scope) {
			if ($scope && $scope[0]) {
				$$($scope[0], '.nexvio-var[data-ready]').forEach(function (n) { n.removeAttribute('data-ready'); });
				initAll($scope[0]);
			}
		});
	});
})();
