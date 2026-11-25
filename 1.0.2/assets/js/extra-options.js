/* Nexvio Extra Options: conditional fields, live prices, validation, file upload,
   and hand-over of the chosen options to every add to cart (WooCommerce form, Nexvio Add to Cart, AJAX). */
(function () {
	'use strict';

	function $$(sel, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(sel)); }
	function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
	function cfg(root) {
		if (!root._nxe) {
			var c = {};
			try { c = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { c = {}; }
			root._nxe = c;
		}
		return root._nxe;
	}

	/* ---------------------------------------------------------------
	   Money
	   --------------------------------------------------------------- */
	function money(root, n) {
		var c = cfg(root).currency || { symbol: '', pos: 'left', dec: 2, dsep: '.', tsep: ',' };
		var neg = n < 0;
		var s = Math.abs(n).toFixed(c.dec);
		var parts = s.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, c.tsep);
		var out = parts.join(c.dsep);
		var sym = c.symbol;
		switch (c.pos) {
			case 'right': out = out + sym; break;
			case 'left_space': out = sym + ' ' + out; break;
			case 'right_space': out = out + ' ' + sym; break;
			default: out = sym + out;
		}
		return (neg ? '−' : '') + out;
	}

	/* ---------------------------------------------------------------
	   Fields and their values
	   --------------------------------------------------------------- */
	function fieldsOf(root) { return $$('.nexvio-epo__field[data-field]', root); }
	function isLayout(f) { var t = f.getAttribute('data-type'); return t === 'header' || t === 'divider'; }
	function logicOf(f) {
		if (!f._logic) {
			try { f._logic = JSON.parse(f.getAttribute('data-logic') || '{}'); } catch (e) { f._logic = {}; }
		}
		return f._logic;
	}

	/** The values of a field: an array of choice ids or one text. */
	function valuesOf(f) {
		var t = f.getAttribute('data-type');
		var out = [];
		if (t === 'select') {
			var s = f.querySelector('select');
			if (s && s.value !== '') { out.push(s.value); }
		} else if (t === 'radio' || t === 'checkbox' || t === 'swatch') {
			$$('input:checked', f).forEach(function (i) { out.push(i.value); });
		} else {
			var i = f.querySelector('[data-nxe-input]');
			if (i && String(i.value).trim() !== '') { out.push(String(i.value).trim()); }
		}
		return out;
	}

	/** One rule: the values of a field against the value of the rule (same as the PHP side). */
	function compare(val, op, rule) {
		var first = val.length ? val[0] : '', has = val.length > 0;
		var low = rule.toLowerCase(), f = first.toLowerCase();
		var numeric = rule !== '' && isFinite(Number(rule)) && has && first !== '' && isFinite(Number(first));
		var n = Number(first), m = Number(rule);
		switch (op) {
			case 'is': return val.indexOf(rule) > -1;
			case 'isnot': return val.indexOf(rule) === -1;
			case 'contains': return rule !== '' && val.some(function (x) { return x.toLowerCase().indexOf(low) > -1; });
			case 'notcontains': return !(rule !== '' && val.some(function (x) { return x.toLowerCase().indexOf(low) > -1; }));
			case 'starts': return has && rule !== '' && f.indexOf(low) === 0;
			case 'ends': return has && rule !== '' && f.length >= low.length && f.slice(-low.length) === low;
			case 'eq': return numeric && n === m;
			case 'neq': return !(numeric && n === m);
			case 'gt': return numeric && n > m;
			case 'gte': return numeric && n >= m;
			case 'lt': return numeric && n < m;
			case 'lte': return numeric && n <= m;
			case 'in':
			case 'notin':
				var list = low.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
				var hitIn = val.some(function (x) { return list.indexOf(x.toLowerCase()) > -1; });
				return op === 'in' ? hitIn : !hitIn;
			case 'lengt': return rule !== '' && isFinite(Number(rule)) && first.length > m;
			case 'lenlt': return rule !== '' && isFinite(Number(rule)) && first.length < m;
			case 'countgte': return rule !== '' && isFinite(Number(rule)) && val.length >= m;
			case 'countlte': return rule !== '' && isFinite(Number(rule)) && val.length <= m;
			case 'before': return has && rule !== '' && first < rule;
			case 'after': return has && rule !== '' && first > rule;
			case 'empty': return !has;
			case 'notempty': return has;
		}
		return false;
	}

	function baseOf(root) {
		var variable = root.getAttribute('data-variable') === '1';
		return root._base === undefined ? (variable ? null : num(root.getAttribute('data-base'))) : root._base;
	}

	/** id -> true / false: which fields are visible with the current values (same rules as the PHP side). */
	function visibility(root) {
		var map = {}, vals = {}, state = {};
		fieldsOf(root).forEach(function (f) {
			var id = f.getAttribute('data-field');
			map[id] = f;
			if (!isLayout(f)) { vals[id] = valuesOf(f); }
		});
		function vis(id) {
			if (Object.prototype.hasOwnProperty.call(state, id)) { return state[id] === null ? false : state[id]; }
			state[id] = null;
			var lg = logicOf(map[id]);
			var v = true;
			if (lg && lg.enabled && lg.rules && lg.rules.length) {
				var res = lg.rules.map(function (r) {
					var val = [];
					if (r.field === '__qty') { val = [String(qtyOf(root))]; }
					else if (r.field === '__price') { var pb = baseOf(root); val = pb === null ? [] : [String(Math.round(pb * 1e6) / 1e6)]; }
					else if (r.field === '__user') { val = [String(cfg(root).user || 'out')]; }
					else if (r.field === '__day') { val = [String(cfg(root).day || '0')]; }
					else if (map[r.field] && vals[r.field] && vis(r.field)) { val = vals[r.field]; }
					return compare(val, r.op, String(r.value));
				});
				var hit = lg.match === 'any' ? res.some(Boolean) : res.every(Boolean);
				v = lg.action === 'hide' ? !hit : hit;
			}
			state[id] = v;
			return v;
		}
		var out = {};
		Object.keys(map).forEach(function (id) { out[id] = vis(id); });
		return out;
	}

	function applyVisibility(root) {
		var vis = visibility(root);
		fieldsOf(root).forEach(function (f) {
			var show = vis[f.getAttribute('data-field')] !== false;
			if (f.hidden === show) { f.hidden = !show; }
		});
	}

	/* ---------------------------------------------------------------
	   Prices
	   --------------------------------------------------------------- */
	function qtyOf(root) {
		var scope = root.closest('form') || document;
		var q = scope.querySelector('input[name="quantity"], input.qty') || document.querySelector('form.cart input[name="quantity"], .nexvio-atc__input, input.qty');
		var n = q ? parseInt(q.value, 10) : 1;
		return n > 0 ? n : 1;
	}

	function extras(root, base) {
		var fixed = 0, pct = 0;
		fieldsOf(root).forEach(function (f) {
			if (f.hidden || isLayout(f)) { return; }
			var t = f.getAttribute('data-type');
			if (t === 'select') {
				var s = f.querySelector('select');
				var o = s && s.selectedOptions && s.selectedOptions[0];
				if (o && o.value !== '') {
					if (o.getAttribute('data-ptype') === 'percent') { pct += num(o.getAttribute('data-price')); } else { fixed += num(o.getAttribute('data-price')); }
				}
			} else if (t === 'radio' || t === 'checkbox' || t === 'swatch') {
				$$('input:checked', f).forEach(function (i) {
					if (i.getAttribute('data-ptype') === 'percent') { pct += num(i.getAttribute('data-price')); } else { fixed += num(i.getAttribute('data-price')); }
				});
			} else {
				var v = valuesOf(f);
				if (!v.length) { return; }
				var p = num(f.getAttribute('data-price'));
				switch (f.getAttribute('data-ptype')) {
					case 'fixed': fixed += p; break;
					case 'percent': pct += p; break;
					case 'char': fixed += p * v[0].replace(/\s+/g, '').length; break;
					case 'value': fixed += p * num(v[0]); break;
				}
			}
		});
		return fixed + (base || 0) * pct / 100;
	}

	function totals(root) {
		var box = root.querySelector('[data-nxe-totals]');
		if (!box) { return; }
		var variable = root.getAttribute('data-variable') === '1';
		var base = root._base === undefined ? (variable ? null : num(root.getAttribute('data-base'))) : root._base;
		var pending = variable && base === null;
		var ex = extras(root, base);
		var q = qtyOf(root);
		var hideZero = !!cfg(root).hideZero;
		var show = pending ? ex !== 0 : !(hideZero && ex === 0);
		box.hidden = !show;
		if (!show) { return; }
		var rows = { product: box.querySelector('[data-nxe-row="product"]'), options: box.querySelector('[data-nxe-row="options"]'), final: box.querySelector('[data-nxe-row="final"]') };
		var note = box.querySelector('[data-nxe-pending]');
		if (rows.product) { rows.product.hidden = pending; if (!pending) { rows.product.querySelector('b').textContent = money(root, base * q); } }
		rows.options.querySelector('b').textContent = money(root, ex * q);
		rows.final.hidden = pending;
		if (!pending) { rows.final.querySelector('b').textContent = money(root, (base + ex) * q); }
		if (note) { note.hidden = !pending; }
	}

	function refresh(root) {
		applyVisibility(root);
		totals(root);
	}

	/* ---------------------------------------------------------------
	   Validation
	   --------------------------------------------------------------- */
	function setError(f, msg) {
		var e = f.querySelector('.nexvio-epo__error');
		f.classList.toggle('is-invalid', !!msg);
		if (e) { e.textContent = msg || ''; e.hidden = !msg; }
	}

	/** Mark every problem; returns the first field with one (or null). */
	function validate(root) {
		var first = null;
		var tpl = cfg(root).i18n && cfg(root).i18n.required ? cfg(root).i18n.required : '%s';
		fieldsOf(root).forEach(function (f) {
			if (isLayout(f)) { return; }
			setError(f, '');
			if (f.hidden) { return; }
			var msg = '';
			var label = f.getAttribute('data-label') || '';
			if (f.getAttribute('data-required') === '1' && !valuesOf(f).length) {
				msg = tpl.replace('%s', label);
			} else {
				var i = f.querySelector('input[type=number], input[type=text], input[type=date], input[type=time], textarea');
				if (i && i.value !== '' && i.checkValidity && !i.checkValidity()) { msg = i.validationMessage; }
			}
			if (msg) {
				setError(f, msg);
				if (!first) { first = f; }
			}
		});
		return first;
	}

	/* ---------------------------------------------------------------
	   Hand-over to add to cart
	   --------------------------------------------------------------- */
	function collect(root) {
		var out = [];
		fieldsOf(root).forEach(function (f) {
			if (f.hidden || isLayout(f)) { return; }
			$$('[data-nxe-input]', f).forEach(function (i) {
				if (i.disabled) { return; }
				if ((i.type === 'radio' || i.type === 'checkbox') && !i.checked) { return; }
				if (i.tagName === 'SELECT' && i.value === '') { return; }
				if (i.value === '') { return; }
				out.push([i.name, i.value]);
			});
		});
		return out;
	}

	function roots() { return $$('[data-nexvio-epo]'); }
	function rootsFor(pid) {
		var all = roots();
		var hit = all.filter(function (r) { return pid && String(r.getAttribute('data-product')) === String(pid); });
		return hit.length ? hit : (all.length === 1 && !pid ? all : []);
	}
	function productOfForm(form) {
		var v = form.getAttribute('data-product_id');
		if (v) { return v; }
		var h = form.querySelector('[name="add-to-cart"]');
		if (h && h.value) { return h.value; }
		var sub = form.querySelector('button[name="add-to-cart"]');
		return sub ? sub.value : '';
	}

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.matches || !(form.matches('form.cart') || form.classList.contains('nexvio-atc__form'))) { return; }
		var rs = rootsFor(productOfForm(form));
		if (!rs.length) { return; }
		var bad = null;
		rs.forEach(function (r) { var b = validate(r); if (b && !bad) { bad = b; } });
		if (bad) {
			e.preventDefault();
			e.stopImmediatePropagation();
			var ctl = bad.querySelector('input:not([type=hidden]), select, textarea');
			try { bad.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (err) { /* old browser */ }
			if (ctl) { try { ctl.focus({ preventScroll: true }); } catch (err2) { ctl.focus(); } }
			return;
		}
		$$('input[data-nxe-injected]', form).forEach(function (n) { n.remove(); });
		rs.forEach(function (r) {
			if (form.contains(r)) { return; }
			collect(r).forEach(function (kv) {
				var h = document.createElement('input');
				h.type = 'hidden';
				h.name = kv[0];
				h.value = kv[1];
				h.setAttribute('data-nxe-injected', '1');
				form.appendChild(h);
			});
		});
	}, true);

	/* Nexvio Add to Cart (AJAX) builds its own request body */
	if (window.fetch) {
		var nativeFetch = window.fetch;
		window.fetch = function (input, init) {
			try {
				var url = typeof input === 'string' ? input : (input && input.url) || '';
				if (init && typeof init.body === 'string' && /post/i.test(init.method || '') && /add_to_cart|nexvio_shw_add/.test(url)) {
					var p = new URLSearchParams(init.body);
					rootsFor(p.get('product_id')).forEach(function (r) {
						collect(r).forEach(function (kv) { p.append(kv[0], kv[1]); });
					});
					init = Object.assign({}, init, { body: p.toString() });
				}
			} catch (err) { /* send the request unchanged */ }
			return nativeFetch.call(this, input, init);
		};
	}

	/* ---------------------------------------------------------------
	   Upload
	   --------------------------------------------------------------- */
	function upload(root, f, file) {
		if (root.hasAttribute('data-preview')) {
			var pn = f.querySelector('[data-nxe-fname]'), pc = f.querySelector('[data-nxe-clear]'), pt = f.querySelector('[data-nxe-token]');
			if (pn) { pn.textContent = file.name; }
			if (pc) { pc.hidden = false; }
			if (pt) { pt.value = 'preview'; }
			refresh(root);
			return;
		}
		var c = cfg(root);
		var box = f.querySelector('[data-nxe-upload]');
		var token = f.querySelector('[data-nxe-token]');
		var name = f.querySelector('[data-nxe-fname]');
		var clear = f.querySelector('[data-nxe-clear]');
		var i18n = c.i18n || {};
		var body = new FormData();
		body.append('action', 'nexvio_epo_upload');
		body.append('nonce', c.nonce);
		body.append('product_id', root.getAttribute('data-product'));
		body.append('field', f.getAttribute('data-field'));
		body.append('file', file);
		box.classList.add('is-busy');
		name.textContent = i18n.uploading || '';
		setError(f, '');
		fetch(c.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { throw new Error((res && res.data && res.data.message) || i18n.uploadErr || 'Error'); }
				token.value = res.data.token;
				name.textContent = res.data.name;
				clear.hidden = false;
			})
			.catch(function (err) {
				token.value = '';
				name.textContent = '';
				clear.hidden = true;
				setError(f, err && err.message ? err.message : (i18n.uploadErr || 'Error'));
			})
			.then(function () { box.classList.remove('is-busy'); refresh(root); });
	}

	/* ---------------------------------------------------------------
	   Events
	   --------------------------------------------------------------- */
	function rootOf(el) { return el && el.closest ? el.closest('[data-nexvio-epo]') : null; }

	/* colour field: swatch, readable value, clear button */
	function colorUi(f, value) {
		var wrap = f.querySelector('[data-nxe-colorwrap]');
		if (!wrap) { return; }
		var txt = wrap.querySelector('[data-nxe-colorval]');
		var clr = wrap.querySelector('[data-nxe-colorclear]');
		wrap.classList.toggle('is-empty', !value);
		if (txt) { txt.textContent = value ? String(value).toUpperCase() : (txt.getAttribute('data-ph') || ''); }
		if (clr) { clr.hidden = !value; }
	}

	/* number field: the - and + buttons */
	function stepNumber(btn) {
		var box = btn.closest('[data-nxe-num]');
		var input = box && box.querySelector('input[type=number]');
		if (!input || input.disabled) { return; }
		var dir = parseFloat(btn.getAttribute('data-nxe-step')) || 0;
		var step = parseFloat(input.step) > 0 ? parseFloat(input.step) : 1;
		var min = input.min !== '' ? parseFloat(input.min) : null;
		var max = input.max !== '' ? parseFloat(input.max) : null;
		var cur = parseFloat(input.value);
		if (isNaN(cur)) { cur = min !== null ? min : 0; }
		var next = cur + dir * step;
		var dec = (String(step).split('.')[1] || '').length;
		next = parseFloat(next.toFixed(Math.max(dec, 6)));
		if (min !== null && next < min) { next = min; }
		if (max !== null && next > max) { next = max; }
		input.value = String(next);
		input.dispatchEvent(new Event('input', { bubbles: true }));
	}
	document.addEventListener('click', function (e) {
		var st = e.target.closest && e.target.closest('[data-nxe-step]');
		if (st && rootOf(st)) { e.preventDefault(); stepNumber(st); return; }
		var cl = e.target.closest && e.target.closest('[data-nxe-colorclear]');
		if (cl && rootOf(cl)) {
			e.preventDefault();
			var f = cl.closest('.nexvio-epo__field');
			var h = f && f.querySelector('input[type=hidden][data-nxe-input]');
			if (h) { h.value = ''; colorUi(f, ''); h.dispatchEvent(new Event('input', { bubbles: true })); }
		}
	});

	function onChange(e) {
		var t = e.target;
		var root = rootOf(t);
		if (root) {
			var f = t.closest('.nexvio-epo__field');
			if (t.matches('[data-nxe-color]')) {
				var h = f.querySelector('input[type=hidden][data-nxe-input]');
				if (h) { h.value = t.value; }
				colorUi(f, t.value);
			}
			if (t.matches('[data-nxe-range]')) {
				var out = f.querySelector('[data-nxe-out]');
				if (out) { out.textContent = t.value; }
			}
			if (t.matches('[data-nxe-file]')) {
				if (t.files && t.files[0]) { upload(root, f, t.files[0]); }
				t.value = '';
				return;
			}
			if (f) { setError(f, ''); }
			refresh(root);
			return;
		}
		if (t.matches && t.matches('input[name="quantity"], input.qty, .nexvio-atc__input')) { roots().forEach(refresh); }
	}
	document.addEventListener('input', onChange);
	document.addEventListener('change', onChange);

	document.addEventListener('click', function (e) {
		var clear = e.target.closest ? e.target.closest('[data-nxe-clear]') : null;
		if (clear) {
			var f = clear.closest('.nexvio-epo__field');
			var root = rootOf(clear);
			f.querySelector('[data-nxe-token]').value = '';
			f.querySelector('[data-nxe-fname]').textContent = '';
			clear.hidden = true;
			refresh(root);
			return;
		}
		/* quantity buttons change the number without an event */
		if (e.target.closest && e.target.closest('form.cart, .nexvio-atc')) { setTimeout(function () { roots().forEach(refresh); }, 60); }
	});

	function varPrice(root, id) {
		var m = cfg(root).varPrices || {};
		return id && m[id] !== undefined ? num(m[id]) : null;
	}
	function setVariation(pid, id, display) {
		roots().forEach(function (r) {
			if (String(r.getAttribute('data-product')) !== String(pid)) { return; }
			var p = id ? varPrice(r, id) : null;
			if (p === null && id && display !== undefined && display !== null) { p = num(display); }
			r._base = p;
			refresh(r);
		});
	}
	document.addEventListener('nexvio:variation', function (e) {
		var d = e.detail || {};
		var id = d.complete ? (d.variation || (d.data && d.data.id) || 0) : 0;
		setVariation(d.product, id);
	});
	function wcVariations() {
		var $ = window.jQuery;
		if (!$ || wcVariations.done) { return; }
		wcVariations.done = true;
		$(document).on('found_variation', 'form.variations_form', function (e, v) {
			setVariation($(this).attr('data-product_id'), v && v.variation_id, v && v.display_price);
		});
		$(document).on('hide_variation reset_data', 'form.variations_form', function () {
			setVariation($(this).attr('data-product_id'), 0);
		});
	}

	window.NexvioEpo = { init: function (scope) { init(scope); }, refresh: function (r) { if (r) { refresh(r); } } };

	/* ---------------------------------------------------------------
	   Dropdown: a styled list on top of the real <select> (which keeps the value)
	   --------------------------------------------------------------- */
	function enhanceSelects(scope) {
		$$('.nexvio-epo__field--select select[data-nxe-input]', scope).forEach(function (sel) {
			if (sel._nxeDd) { return; }
			sel._nxeDd = true;
			sel.removeAttribute('required');
			var box = document.createElement('div');
			box.className = 'nexvio-epo__dd';
			sel.parentNode.insertBefore(box, sel);
			box.appendChild(sel);
			sel.classList.add('nexvio-epo__native');
			sel.tabIndex = -1;
			sel.setAttribute('aria-hidden', 'true');

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'nexvio-epo__ddbtn';
			btn.setAttribute('aria-haspopup', 'listbox');
			btn.setAttribute('aria-expanded', 'false');
			var lab = sel.id ? document.querySelector('label[for="' + sel.id + '"]') : null;
			if (lab) { if (!lab.id) { lab.id = sel.id + '-l'; } btn.setAttribute('aria-labelledby', lab.id); }
			var txt = document.createElement('span');
			txt.className = 'nexvio-epo__ddtxt';
			var chev = document.createElement('span');
			chev.className = 'nexvio-epo__ddchev';
			chev.setAttribute('aria-hidden', 'true');
			btn.appendChild(txt);
			btn.appendChild(chev);
			box.appendChild(btn);

			var panel = document.createElement('ul');
			panel.className = 'nexvio-epo__ddlist';
			panel.setAttribute('role', 'listbox');
			panel.hidden = true;
			box.appendChild(panel);

			var items = [];
			Array.prototype.forEach.call(sel.options, function (o, i) {
				var li = document.createElement('li');
				li.className = 'nexvio-epo__ddopt' + (o.value === '' ? ' is-placeholder' : '');
				li.setAttribute('role', 'option');
				li.setAttribute('data-i', i);
				var n = document.createElement('span');
				n.className = 'nexvio-epo__ddname';
				n.textContent = o.getAttribute('data-label') || o.textContent;
				li.appendChild(n);
				var pl = o.getAttribute('data-plabel');
				var ol = o.getAttribute('data-oldlabel');
				if (pl || ol) {
					var p = document.createElement('span');
					p.className = 'nexvio-epo__ddprice';
					if (ol) { var d = document.createElement('del'); d.className = 'nexvio-epo__cold'; d.textContent = ol; p.appendChild(d); p.appendChild(document.createTextNode(' ')); }
					p.appendChild(document.createTextNode(pl || ''));
					li.appendChild(p);
				}
				li.addEventListener('mousedown', function (e) { e.preventDefault(); });
				li.addEventListener('click', function () { pick(i); });
				panel.appendChild(li);
				items.push(li);
			});

			var active = -1;
			function sync() {
				var o = sel.options[sel.selectedIndex];
				var empty = !o || o.value === '';
				txt.textContent = o ? (o.getAttribute('data-label') || o.textContent) : '';
				var pl = o && o.getAttribute('data-plabel');
				if (pl && !empty) { var p = document.createElement('em'); p.textContent = pl; txt.appendChild(p); }
				btn.classList.toggle('is-empty', empty);
				items.forEach(function (li, i) { li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false'); li.classList.toggle('is-on', i === sel.selectedIndex); });
			}
			function setActive(i) {
				active = Math.max(0, Math.min(items.length - 1, i));
				items.forEach(function (li, k) { li.classList.toggle('is-active', k === active); });
				if (items[active] && items[active].scrollIntoView) { items[active].scrollIntoView({ block: 'nearest' }); }
			}
			/* while the list is open, lift every parent above the rest of the page (add to cart, tabs...) */
			var lifted = [];
			function lift() {
				var n = box.parentNode;
				while (n && n.nodeType === 1 && n !== document.body && n !== document.documentElement) {
					lifted.push({ n: n, z: n.style.zIndex, p: n.style.position });
					if (window.getComputedStyle(n).position === 'static') { n.style.position = 'relative'; }
					n.style.zIndex = '9999';
					n = n.parentNode;
				}
			}
			function drop() {
				lifted.forEach(function (r) { r.n.style.zIndex = r.z; r.n.style.position = r.p; });
				lifted = [];
			}
			box._nxeDrop = drop;
			function open() {
				$$('.nexvio-epo__ddlist').forEach(function (l) { if (l !== panel) { l.hidden = true; if (l.parentNode) { l.parentNode.classList.remove('is-open'); if (l.parentNode._nxeDrop) { l.parentNode._nxeDrop(); } } if (l.previousSibling) { l.previousSibling.setAttribute('aria-expanded', 'false'); } } });
				drop(); lift();
				panel.hidden = false;
				box.classList.add('is-open');
				btn.setAttribute('aria-expanded', 'true');
				setActive(sel.selectedIndex < 0 ? 0 : sel.selectedIndex);
			}
			function close(focus) {
				panel.hidden = true;
				drop();
				box.classList.remove('is-open');
				btn.setAttribute('aria-expanded', 'false');
				if (focus) { btn.focus(); }
			}
			function pick(i) {
				if (sel.selectedIndex !== i) {
					sel.selectedIndex = i;
					sel.dispatchEvent(new Event('change', { bubbles: true }));
				}
				sync();
				close(true);
			}
			btn.addEventListener('click', function () { if (panel.hidden) { open(); } else { close(); } });
			btn.addEventListener('keydown', function (e) {
				var k = e.key;
				if (panel.hidden) {
					if (k === 'ArrowDown' || k === 'ArrowUp') { e.preventDefault(); open(); }
					return;
				}
				if (k === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
				else if (k === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
				else if (k === 'Home') { e.preventDefault(); setActive(0); }
				else if (k === 'End') { e.preventDefault(); setActive(items.length - 1); }
				else if (k === 'Enter' || k === ' ') { e.preventDefault(); pick(active); }
				else if (k === 'Escape') { e.preventDefault(); close(true); }
				else if (k === 'Tab') { close(); }
			});
			document.addEventListener('click', function (e) { if (!box.contains(e.target)) { close(); } });
			sel.addEventListener('change', sync);
			sync();
		});
	}

	function init(scope) {
		wcVariations();
		$$('[data-nexvio-epo]', scope || document).forEach(function (r) {
			if (r._nxeInit) { return; }
			r._nxeInit = true;
			enhanceSelects(r);
			var st = window.nexvioVarState && window.nexvioVarState[r.getAttribute('data-product')];
			if (st && st.complete && st.variation) { r._base = varPrice(r, st.variation); }
			refresh(r);
		});
		if (scope && scope.matches && scope.matches('[data-nexvio-epo]') && !scope._nxeInit) {
			scope._nxeInit = true;
			enhanceSelects(scope);
			refresh(scope);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { init(); });
	} else {
		init();
	}
	window.addEventListener('load', function () { init(); });

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_extra_options.default', function ($scope) {
			if ($scope && $scope[0]) { init($scope[0]); }
		});
	});
})();
