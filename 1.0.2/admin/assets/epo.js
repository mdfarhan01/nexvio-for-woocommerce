/* Nexvio Extra Options: form builder (fields, choices, prices, conditional logic) + live preview. */
(function ($) {
	'use strict';

	var D = window.NEXVIO_EPO;
	var form = document.getElementById('nexvio-epo-form');
	if (!D || !form) { return; }

	var T = D.i18n;
	var TYPES = D.types;
	var list = document.getElementById('nexvio-epo-list');
	var palette = document.getElementById('nexvio-epo-palette');
	var addBtn = document.getElementById('nexvio-epo-add');
	var preview = document.getElementById('nexvio-epo-preview');
	var jsonInput = document.getElementById('nexvio-epo-json');
	var fields = Array.isArray(D.fields) ? D.fields : [];
	var openCard = fields.length ? -1 : -1;

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (text !== undefined && text !== null) { n.textContent = text; }
		return n;
	}
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function rid(prefix) {
		var s = '';
		var chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		for (var i = 0; i < 7; i++) { s += chars.charAt(Math.floor(Math.random() * chars.length)); }
		return prefix + s;
	}

	/* ------------------------------------------------------------------
	 * Model
	 * ------------------------------------------------------------------ */
	function newField(type) {
		var t = TYPES[type];
		var f = { id: rid('f'), type: type, label: t.label, description: '', logic: { enabled: false, action: 'show', match: 'all', rules: [] } };
		if (type === 'divider') { f.label = ''; }
		if (type === 'header') { f.tag = 'h3'; return f; }
		if (type === 'divider') { return f; }
		if (type === 'faq') { f.label = T.faqDefaultTitle; f.style = 'accordion'; f.open_first = false; f.apply = 'products'; f.products = []; f.ptitles = {}; f.kind = 'faq'; f.look = 'cards'; f.badge = 'auto'; f.items = [{ id: rid('q'), q: '', a: '' }]; return f; }
		f.required = false; f.hide_label = false; f.width = 'full'; f.placeholder = ''; f.default = '';
		f.price_type = 'none'; f.price = 0;
		if (t.choice) {
			f.choices = [newChoice('Option 1'), newChoice('Option 2')];
			if (type === 'radio' || type === 'checkbox') { f.display = 'list'; }
			if (type === 'swatch') { f.swatch_mode = 'color'; f.multiple = false; f.choices[0].color = '#d9534f'; f.choices[1].color = '#3d8bff'; }
		}
		if (type === 'text' || type === 'textarea') { f.maxlength = 0; }
		if (type === 'number') { f.min = ''; f.max = ''; f.step = 1; }
		if (type === 'range') { f.min = 0; f.max = 100; f.step = 1; }
		return f;
	}
	function newChoice(label) { return { id: rid('c'), label: label, price: 0, price_type: 'fixed', sale: '', image: '', color: '', default: false }; }

	function typeName(f) { return TYPES[f.type] ? TYPES[f.type].label : f.type; }
	function fieldName(f) { return f.label || typeName(f); }

	/* ------------------------------------------------------------------
	 * Palette
	 * ------------------------------------------------------------------ */
	function renderPalette() {
		palette.textContent = '';
		palette.appendChild(el('h3', 'nexvio-fb__pal-title', T.palette));
		var g = el('div', 'nexvio-fb__tiles');
		Object.keys(TYPES).forEach(function (k) {
			var b = el('button', 'nexvio-fb__tile');
			b.type = 'button';
			var i = el('span', 'dashicons ' + TYPES[k].icon);
			i.setAttribute('aria-hidden', 'true');
			b.appendChild(i);
			var tx = el('span', 'nexvio-fb__tile-text');
			tx.appendChild(el('strong', '', TYPES[k].label));
			tx.appendChild(el('small', '', TYPES[k].desc));
			b.appendChild(tx);
			b.addEventListener('click', function () {
				fields.push(newField(k));
				openCard = fields.length - 1;
				togglePalette(false);
				render();
				var last = list.lastElementChild;
				if (last) { last.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
			});
			g.appendChild(b);
		});
		palette.appendChild(g);
	}
	function togglePalette(show) {
		palette.hidden = !show;
		addBtn.setAttribute('aria-expanded', show ? 'true' : 'false');
		if (show) { renderPalette(); }
	}
	addBtn.addEventListener('click', function () { togglePalette(palette.hidden); });

	/* ------------------------------------------------------------------
	 * Small controls: each option is a row, label and help on the left,
	 * the control on the right (same pattern as the other Nexvio settings)
	 * ------------------------------------------------------------------ */
	function row(label, hint, control, cls) {
		var r = el('div', 'nxe-row' + (cls ? ' ' + cls : ''));
		var l = el('div', 'nxe-row__label');
		l.appendChild(el('strong', '', label));
		if (hint) { l.appendChild(el('p', '', hint)); }
		r.appendChild(l);
		var c = el('div', 'nxe-row__control');
		c.appendChild(control);
		r.appendChild(c);
		return r;
	}
	function heading(text, hint) {
		var h = el('div', 'nxe-heading');
		h.appendChild(el('strong', '', text));
		if (hint) { h.appendChild(el('span', '', hint)); }
		return h;
	}
	function textIn(obj, prop, label, ph, hint, type) {
		var i = el('input');
		i.type = type || 'text';
		i.value = obj[prop] == null ? '' : obj[prop];
		i.placeholder = ph || '';
		i.addEventListener('input', function () { obj[prop] = i.value; draw(); });
		return row(label, hint, i);
	}
	function numIn(obj, prop, label, attrs, hint, unit) {
		var i = el('input');
		i.type = 'number';
		Object.keys(attrs || {}).forEach(function (k) { i.setAttribute(k, attrs[k]); });
		i.value = obj[prop] == null ? '' : obj[prop];
		i.addEventListener('input', function () { obj[prop] = i.value === '' ? '' : parseFloat(i.value); draw(); });
		var c = i;
		if (unit) { c = el('span', 'nxe-unit'); c.appendChild(i); c.appendChild(el('em', '', unit)); }
		return row(label, hint, c, 'nxe-row--short');
	}
	function switchIn(obj, prop, label, hint, onText, redraw) {
		var l = el('label', 'nexvio-toggle');
		var c = el('input');
		c.type = 'checkbox';
		c.checked = !!obj[prop];
		var t = el('span', '', c.checked ? T.on : T.off);
		c.addEventListener('change', function () { obj[prop] = c.checked; t.textContent = c.checked ? T.on : T.off; if (redraw) { render(); } else { draw(); } });
		l.appendChild(c);
		l.appendChild(t);
		return row(label, hint, l);
	}
	function segIn(obj, prop, label, opts, hint, redraw) {
		var g = el('div', 'nxe-seg');
		g.setAttribute('role', 'radiogroup');
		Object.keys(opts).forEach(function (k) {
			var b = el('button', 'nxe-seg__b' + (String(obj[prop]) === k ? ' is-on' : ''), opts[k]);
			b.type = 'button';
			b.setAttribute('role', 'radio');
			b.setAttribute('aria-checked', String(obj[prop]) === k ? 'true' : 'false');
			b.addEventListener('click', function () {
				obj[prop] = k;
				if (redraw) { render(); return; }
				Array.prototype.forEach.call(g.children, function (x) { x.classList.remove('is-on'); x.setAttribute('aria-checked', 'false'); });
				b.classList.add('is-on'); b.setAttribute('aria-checked', 'true');
				draw();
			});
			g.appendChild(b);
		});
		return row(label, hint, g);
	}
	function selectIn(obj, prop, label, opts, redraw, hint) {
		var s = el('select');
		Object.keys(opts).forEach(function (k) {
			var o = el('option', '', opts[k]);
			o.value = k;
			if (String(obj[prop]) === k) { o.selected = true; }
			s.appendChild(o);
		});
		s.addEventListener('change', function () { obj[prop] = s.value; if (redraw) { render(); } else { draw(); } });
		return row(label, hint, s);
	}
	function money(obj, prop, unitProp, withUnit) {
		var w = el('span', 'nxe-money');
		var unit;
		if (withUnit) {
			unit = el('select', 'nxe-money__unit');
			[['fixed', D.currency], ['percent', '%']].forEach(function (o) {
				var op = el('option', '', o[1]); op.value = o[0]; if (obj[unitProp] === o[0]) { op.selected = true; } unit.appendChild(op);
			});
			unit.addEventListener('change', function () { obj[unitProp] = unit.value; draw(); });
		} else {
			unit = el('span', 'nxe-money__unit nxe-money__unit--static', D.currency);
		}
		var i = el('input');
		i.type = 'number'; i.step = 'any'; i.value = obj[prop] || 0; i.title = T.price;
		i.addEventListener('input', function () { obj[prop] = parseFloat(i.value) || 0; draw(); });
		w.appendChild(unit);
		w.appendChild(i);
		return w;
	}

	/* ------------------------------------------------------------------
	 * Choices
	 * ------------------------------------------------------------------ */
	function pickImage(cb) {
		if (!window.wp || !wp.media) { return; }
		var frame = wp.media({ title: T.pickImage, multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			cb(a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
		});
		frame.open();
	}

	function choiceEditor(f) {
		var box = el('div', 'nxe-choices');
		var multi = f.type === 'checkbox' || (f.type === 'swatch' && f.multiple);
		var withLook = f.type === 'swatch';

		f.choices.forEach(function (c, ci) {
			if (c.sale === undefined || c.sale === null) { c.sale = ''; }
			var card = el('div', 'nxe-cc');
			var top = el('div', 'nxe-cc__top' + (withLook ? ' has-look' : ''));
			top.appendChild(el('span', 'nxe-choice__n', String(ci + 1)));
			var name = el('input');
			name.type = 'text'; name.value = c.label; name.placeholder = T.choiceName;
			name.addEventListener('input', function () { c.label = name.value; draw(); });
			top.appendChild(name);
			if (withLook) {
				if (f.swatch_mode === 'image') {
					var im = el('button', 'nxe-choice__img' + (c.image ? ' has-img' : ''));
					im.type = 'button';
					im.title = T.pickImage;
					if (c.image) { im.style.backgroundImage = 'url("' + c.image.replace(/["()\\]/g, '') + '")'; } else { im.textContent = '+'; }
					im.addEventListener('click', function () { pickImage(function (u) { c.image = u; render(); }); });
					top.appendChild(im);
				} else {
					var col = el('input', 'nxe-choice__col');
					col.type = 'color'; col.value = c.color || '#cccccc'; col.title = T.colour;
					col.addEventListener('input', function () { c.color = col.value; draw(); });
					top.appendChild(col);
				}
			}
			var def = el('label', 'nxe-cc__def');
			var dc = el('input');
			dc.type = multi ? 'checkbox' : 'radio';
			dc.name = 'def-' + f.id;
			dc.checked = !!c.default;
			dc.setAttribute('aria-label', T.chosen);
			dc.addEventListener('click', function () {
				if (multi) { c.default = dc.checked; } else {
					var was = c.default;
					f.choices.forEach(function (o) { o.default = false; });
					c.default = !was;
					dc.checked = c.default;
				}
				draw();
			});
			def.appendChild(dc);
			def.appendChild(el('span', '', T.chosenShort));
			top.appendChild(def);
			var rm = el('button', 'nexvio-fb__tool nexvio-fb__tool--remove');
			rm.type = 'button'; rm.title = T.remove; rm.setAttribute('aria-label', T.remove);
			rm.appendChild(el('span', 'dashicons dashicons-no-alt'));
			rm.addEventListener('click', function () { f.choices.splice(ci, 1); render(); });
			top.appendChild(rm);
			card.appendChild(top);

			/* prices: regular + (optional) sale */
			var prices = el('div', 'nxe-cc__prices');
			var warn = el('p', 'nxe-cc__warn', T.saleWarn);
			function unitText() { return c.price_type === 'percent' ? '%' : D.currency; }
			function checkWarn() {
				var bad = c.sale !== '' && !(parseFloat(c.sale) < (parseFloat(c.price) || 0));
				warn.hidden = !bad;
			}

			var fReg = el('label', 'nxe-pf');
			fReg.appendChild(el('span', 'nxe-pf__l', T.regularPrice));
			var mReg = el('span', 'nxe-money');
			var unit = el('select', 'nxe-money__unit');
			[['fixed', D.currency], ['percent', '%']].forEach(function (o) {
				var op = el('option', '', o[1]); op.value = o[0]; if (c.price_type === o[0]) { op.selected = true; } unit.appendChild(op);
			});
			var regIn = el('input');
			regIn.type = 'number'; regIn.step = 'any'; regIn.value = c.price || 0; regIn.title = T.regularPrice;
			regIn.addEventListener('input', function () { c.price = parseFloat(regIn.value) || 0; checkWarn(); draw(); });
			mReg.appendChild(unit); mReg.appendChild(regIn);
			fReg.appendChild(mReg);

			var fSale = el('label', 'nxe-pf');
			fSale.appendChild(el('span', 'nxe-pf__l', T.salePrice));
			var mSale = el('span', 'nxe-money');
			var saleUnit = el('span', 'nxe-money__unit nxe-money__unit--static', unitText());
			var saleIn = el('input');
			saleIn.type = 'number'; saleIn.step = 'any'; saleIn.placeholder = T.saleNone; saleIn.title = T.salePrice;
			saleIn.value = c.sale === '' ? '' : c.sale;
			saleIn.addEventListener('input', function () {
				c.sale = saleIn.value === '' ? '' : (parseFloat(saleIn.value) || 0);
				checkWarn(); draw();
			});
			mSale.appendChild(saleUnit); mSale.appendChild(saleIn);
			fSale.appendChild(mSale);
			unit.addEventListener('change', function () { c.price_type = unit.value; saleUnit.textContent = unitText(); draw(); });

			prices.appendChild(fReg);
			prices.appendChild(fSale);
			card.appendChild(prices);
			warn.hidden = true;
			card.appendChild(warn);
			checkWarn();
			box.appendChild(card);
		});

		var bar = el('div', 'nxe-choices__bar');
		var add = el('button', 'nexvio-btn nexvio-btn--ghost nexvio-btn--sm', '+ ' + T.addChoice);
		add.type = 'button';
		add.addEventListener('click', function () { f.choices.push(newChoice('')); render(); });
		var bulk = el('button', 'nexvio-btn nexvio-btn--ghost nexvio-btn--sm', T.bulk);
		bulk.type = 'button';
		bar.appendChild(add);
		bar.appendChild(bulk);
		box.appendChild(bar);

		var bp = el('div', 'nxe-bulk');
		bp.hidden = true;
		bp.appendChild(el('p', 'nxe-f__hint', T.bulkHelp));
		var ta = el('textarea');
		ta.rows = 5;
		ta.placeholder = 'Small\nMedium | 2\nLarge | 5 | 4\nXL | 10%';
		bp.appendChild(ta);
		var go = el('button', 'nexvio-btn nexvio-btn--sm', T.bulkAdd);
		go.type = 'button';
		go.addEventListener('click', function () {
			ta.value.split(/\r?\n/).forEach(function (line) {
				line = line.trim();
				if (!line) { return; }
				var parts = line.split('|');
				var c = newChoice(parts[0].trim());
				var p = (parts[1] || '').trim();
				if (p) {
					if (p.slice(-1) === '%') { c.price_type = 'percent'; p = p.slice(0, -1); }
					c.price = parseFloat(p.replace(',', '.')) || 0;
				}
				var sp = (parts[2] || '').trim().replace('%', '');
				if (sp) { c.sale = parseFloat(sp.replace(',', '.')); if (isNaN(c.sale)) { c.sale = ''; } }
				f.choices.push(c);
			});
			render();
		});
		bp.appendChild(go);
		box.appendChild(bp);
		bulk.addEventListener('click', function () { bp.hidden = !bp.hidden; });
		return box;
	}

	/* ------------------------------------------------------------------
	 * Product picker (which products show a FAQ field)
	 * ------------------------------------------------------------------ */
	function productPicker(f) {
		f.products = Array.isArray(f.products) ? f.products : [];
		if (!f.ptitles || Array.isArray(f.ptitles)) { f.ptitles = {}; }
		var box = el('div', 'nxe-pp');
		var chipsUl = el('ul', 'nxe-pp__chips');
		var wrap = el('div', 'nxe-pp__search');
		var input = el('input');
		input.type = 'text'; input.placeholder = T.faqSearch; input.autocomplete = 'off';
		var out = el('ul', 'nxe-pp__results');
		out.hidden = true;
		var timer = null;

		function drawChips() {
			chipsUl.textContent = '';
			if (!f.products.length) { chipsUl.appendChild(el('li', 'nxe-pp__none', T.faqNoProducts)); return; }
			f.products.forEach(function (id) {
				var li = el('li', 'nxe-pp__chip');
				li.appendChild(el('span', '', f.ptitles[id] || ('#' + id)));
				var x = el('button', '', '\u00d7');
				x.type = 'button'; x.setAttribute('aria-label', T.remove);
				x.addEventListener('click', function () {
					f.products = f.products.filter(function (p) { return p !== id; });
					drawChips();
				});
				li.appendChild(x);
				chipsUl.appendChild(li);
			});
		}
		input.addEventListener('input', function () {
			clearTimeout(timer);
			var term = input.value.trim();
			if (term.length < 2) { out.hidden = true; out.textContent = ''; return; }
			timer = setTimeout(function () {
				$.get(D.ajax, { action: 'nexvio_epo_search_products', nonce: D.nonce, term: term }).done(function (res) {
					out.textContent = '';
					var rows = res && res.success ? res.data : [];
					if (!rows.length) { out.appendChild(el('li', 'is-empty', T.noResult)); }
					rows.forEach(function (p) {
						var id = parseInt(p.id, 10);
						if (f.products.indexOf(id) > -1) { return; }
						var li = el('li', '', p.title || ('#' + id));
						li.addEventListener('click', function () {
							f.products.push(id);
							f.ptitles[id] = p.title || ('#' + id);
							input.value = '';
							out.hidden = true; out.textContent = '';
							drawChips();
						});
						out.appendChild(li);
					});
					out.hidden = false;
				});
			}, 250);
		});
		document.addEventListener('click', function (e) { if (!box.contains(e.target)) { out.hidden = true; } });
		wrap.appendChild(input);
		wrap.appendChild(out);
		box.appendChild(chipsUl);
		box.appendChild(wrap);
		drawChips();
		return box;
	}

	/* ------------------------------------------------------------------
	 * FAQ questions
	 * ------------------------------------------------------------------ */
	function faqEditor(f) {
		var box = el('div', 'nxe-faq');
		f.items = f.items || [];
		f.items.forEach(function (it, i) {
			var c = el('div', 'nxe-faq__item');
			var bar = el('div', 'nxe-faq__bar');
			bar.appendChild(el('span', 'nxe-choice__n', String(i + 1)));
			var q = el('input');
			q.type = 'text'; q.value = it.q || ''; q.placeholder = f.kind === 'faq' || !f.kind ? T.faqQ : T.faqItemTitle;
			q.addEventListener('input', function () { it.q = q.value; draw(); });
			bar.appendChild(q);
			function tool(icon, label, fn, cls) {
				var b = el('button', 'nexvio-fb__tool' + (cls ? ' ' + cls : ''));
				b.type = 'button'; b.title = label; b.setAttribute('aria-label', label);
				b.appendChild(el('span', 'dashicons ' + icon));
				b.addEventListener('click', fn);
				bar.appendChild(b);
			}
			tool('dashicons-arrow-up-alt2', T.up, function () { if (i > 0) { var x = f.items[i - 1]; f.items[i - 1] = it; f.items[i] = x; render(); } });
			tool('dashicons-arrow-down-alt2', T.down, function () { if (i < f.items.length - 1) { var x = f.items[i + 1]; f.items[i + 1] = it; f.items[i] = x; render(); } });
			tool('dashicons-no-alt', T.remove, function () { f.items.splice(i, 1); render(); }, 'nexvio-fb__tool--remove');
			c.appendChild(bar);
			var a = el('textarea');
			a.rows = 3; a.value = it.a || ''; a.placeholder = f.kind === 'faq' || !f.kind ? T.faqA : T.faqItemText;
			a.addEventListener('input', function () { it.a = a.value; draw(); });
			c.appendChild(a);
			box.appendChild(c);
		});
		var add = el('button', 'nexvio-btn nexvio-btn--ghost nexvio-btn--sm', '+ ' + (f.kind === 'faq' || !f.kind ? T.faqAdd : T.faqAddItem));
		add.type = 'button';
		add.addEventListener('click', function () { f.items.push({ id: rid('q'), q: '', a: '' }); render(); });
		box.appendChild(add);
		return box;
	}

	/* ------------------------------------------------------------------
	 * Conditional logic: every field type, plus quantity, price, customer, day
	 * ------------------------------------------------------------------ */
	var OPS_BY = {
		choice: ['is', 'isnot', 'in', 'notin', 'notempty', 'empty'],
		multi: ['is', 'isnot', 'in', 'notin', 'countgte', 'countlte', 'notempty', 'empty'],
		text: ['is', 'isnot', 'contains', 'notcontains', 'starts', 'ends', 'in', 'notin', 'lengt', 'lenlt', 'notempty', 'empty'],
		number: ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'notempty', 'empty'],
		date: ['is', 'isnot', 'before', 'after', 'notempty', 'empty'],
		color: ['is', 'isnot', 'notempty', 'empty'],
		upload: ['notempty', 'empty'],
		user: ['is', 'isnot'],
		day: ['is', 'isnot', 'in', 'notin']
	};
	var DAYS = [['1', T.mon], ['2', T.tue], ['3', T.wed], ['4', T.thu], ['5', T.fri], ['6', T.sat], ['0', T.sun]];
	var SPECIAL = { __qty: 'number', __price: 'number', __user: 'user', __day: 'day' };
	function kindOf(f, id) {
		if (SPECIAL[id]) { return SPECIAL[id]; }
		var t = f ? f.type : '';
		if (TYPES[t] && TYPES[t].choice) { return (t === 'checkbox' || (t === 'swatch' && f.multiple)) ? 'multi' : 'choice'; }
		if (t === 'number' || t === 'range') { return 'number'; }
		if (t === 'date' || t === 'time') { return 'date'; }
		if (t === 'color') { return 'color'; }
		if (t === 'upload') { return 'upload'; }
		return 'text';
	}
	function opLabel(kind, op) {
		var L = {
			notempty: { choice: T.opChosen, multi: T.opChosen, upload: T.opHasFile },
			empty: { choice: T.opNotChosen, multi: T.opNotChosen, upload: T.opNoFile },
			countgte: { multi: T.opCountGte },
			countlte: { multi: T.opCountLte }
		};
		if (L[op] && L[op][kind]) { return L[op][kind]; }
		if (op === 'lengt') { return T.opLenGt; }
		if (op === 'lenlt') { return T.opLenLt; }
		return D.ops[op] || op;
	}
	function sourceFields(f) {
		return fields.filter(function (o) { return o !== f && !TYPES[o.type].layout; });
	}
	function sourceOf(id) { return fields.filter(function (o) { return o.id === id; })[0]; }
	function sourceName(id) {
		var names = { __qty: T.qtySource, __price: T.priceSource, __user: T.userSource, __day: T.daySource };
		if (names[id]) { return names[id]; }
		var s = sourceOf(id);
		return s ? fieldName(s) : '…';
	}
	function valueText(r) {
		var src = sourceOf(r.field);
		var kind = kindOf(src, r.field);
		if (r.op === 'empty' || r.op === 'notempty') { return ''; }
		var vals = String(r.value || '').split(',').filter(Boolean);
		if (kind === 'choice' || kind === 'multi') {
			return vals.map(function (v) { var c = (src.choices || []).filter(function (x) { return x.id === v; })[0]; return c ? '"' + c.label + '"' : ''; }).filter(Boolean).join(', ');
		}
		if (kind === 'day') { return vals.map(function (v) { var d = DAYS.filter(function (x) { return x[0] === v; })[0]; return d ? d[1] : ''; }).filter(Boolean).join(', '); }
		if (kind === 'user') { return r.value === 'in' ? T.loggedIn : T.loggedOut; }
		return r.value === '' || r.value == null ? '…' : '"' + r.value + '"';
	}
	function sentence(f) {
		var lg = f.logic;
		var parts = lg.rules.filter(function (r) { return r.field; }).map(function (r) {
			var kind = kindOf(sourceOf(r.field), r.field);
			var t = sourceName(r.field) + ' ' + opLabel(kind, r.op);
			var v = valueText(r);
			return v ? t + ' ' + v : t;
		});
		if (!parts.length) { return ''; }
		return (lg.action === 'hide' ? T.sumHide : T.sumShow) + ' ' + parts.join(lg.match === 'any' ? ' ' + T.sumOr + ' ' : ' ' + T.sumAnd + ' ') + '.';
	}

	function chips(options, current, onChange) {
		/* options: [[value,label]], current: comma list */
		var box = el('div', 'nxe-chips');
		var sel = String(current || '').split(',').filter(Boolean);
		options.forEach(function (o) {
			var b = el('button', 'nxe-chip' + (sel.indexOf(o[0]) > -1 ? ' is-on' : ''), o[1]);
			b.type = 'button';
			b.addEventListener('click', function () {
				var i = sel.indexOf(o[0]);
				if (i > -1) { sel.splice(i, 1); } else { sel.push(o[0]); }
				b.classList.toggle('is-on', i < 0);
				onChange(sel.join(','));
			});
			box.appendChild(b);
		});
		return box;
	}

	function ruleValue(r, src, kind) {
		if (r.op === 'empty' || r.op === 'notempty') { return el('span', 'nxe-rule__none', T.noValue); }
		if (kind === 'user') {
			var us = el('select');
			[['in', T.loggedIn], ['out', T.loggedOut]].forEach(function (o) { var op = el('option', '', o[1]); op.value = o[0]; if (r.value === o[0]) { op.selected = true; } us.appendChild(op); });
			if (!r.value) { r.value = 'in'; }
			us.addEventListener('change', function () { r.value = us.value; draw(); sum(); });
			return us;
		}
		if (kind === 'day') {
			if (r.op === 'in' || r.op === 'notin') { return chips(DAYS, r.value, function (v) { r.value = v; draw(); sum(); }); }
			var ds = el('select');
			DAYS.forEach(function (o) { var op = el('option', '', o[1]); op.value = o[0]; if (r.value === o[0]) { op.selected = true; } ds.appendChild(op); });
			if (!r.value) { r.value = DAYS[0][0]; }
			ds.addEventListener('change', function () { r.value = ds.value; draw(); sum(); });
			return ds;
		}
		if (kind === 'choice' || kind === 'multi') {
			var opts = (src.choices || []).map(function (c) { return [c.id, c.label || '—']; });
			if (r.op === 'in' || r.op === 'notin') { return chips(opts, r.value, function (v) { r.value = v; draw(); sum(); }); }
			if (r.op === 'countgte' || r.op === 'countlte') { return numberInput(r, 1); }
			var vs = el('select');
			var ov = el('option', '', T.pickChoice); ov.value = ''; vs.appendChild(ov);
			opts.forEach(function (o) { var op = el('option', '', o[1]); op.value = o[0]; if (r.value === o[0]) { op.selected = true; } vs.appendChild(op); });
			vs.addEventListener('change', function () { r.value = vs.value; draw(); sum(); });
			return vs;
		}
		if (kind === 'number' || r.op === 'lengt' || r.op === 'lenlt') { return numberInput(r); }
		var vi = el('input');
		vi.type = kind === 'color' ? 'color' : 'text';
		if (src && (src.type === 'date' || src.type === 'time') && (r.op === 'is' || r.op === 'isnot' || r.op === 'before' || r.op === 'after')) { vi.type = src.type; }
		if (kind === 'color' && !r.value) { r.value = '#000000'; }
		vi.value = r.value || (kind === 'color' ? '#000000' : '');
		vi.placeholder = (r.op === 'in' || r.op === 'notin') ? T.listPh : T.value;
		vi.addEventListener('input', function () { r.value = vi.value; draw(); sum(); });
		return vi;
	}
	function numberInput(r, min) {
		var vi = el('input');
		vi.type = 'number'; vi.step = 'any'; vi.value = r.value || ''; vi.placeholder = T.value;
		if (min != null) { vi.min = min; }
		vi.addEventListener('input', function () { r.value = vi.value; draw(); sum(); });
		return vi;
	}

	var sumNode = null, sumField = null;
	function sum() { if (sumNode && sumField) { var t = sentence(sumField); sumNode.textContent = t; sumNode.hidden = !t; } }

	var QUICK = function (srcs) {
		var list = [
			[T.quickQty, { field: '__qty', op: 'gte', value: '10' }],
			[T.quickUser, { field: '__user', op: 'is', value: 'in' }],
			[T.quickWeekend, { field: '__day', op: 'in', value: '6,0' }]
		];
		if (srcs.length) { list.unshift([T.quickField, { field: srcs[0].id, op: OPS_BY[kindOf(srcs[0], srcs[0].id)][0], value: '' }]); }
		return list;
	};

	function logicEditor(f) {
		var lg = f.logic;
		var box = el('div', 'nxe-logic');
		var srcs = sourceFields(f);
		box.appendChild(switchIn(lg, 'enabled', T.logicOn, T.logicHint, null, true));
		if (!lg.enabled) { return box; }

		var line = el('div', 'nxe-logic__line');
		function sel2(opts, cur, fn) {
			var s = el('select');
			opts.forEach(function (o) { var op = el('option', '', o[1]); op.value = o[0]; if (cur === o[0]) { op.selected = true; } s.appendChild(op); });
			s.addEventListener('change', function () { fn(s.value); });
			return s;
		}
		line.appendChild(sel2([['show', T.show], ['hide', T.hide]], lg.action, function (v) { lg.action = v; draw(); sum(); }));
		line.appendChild(el('span', '', T.thisWhen));
		line.appendChild(sel2([['all', T.all], ['any', T.any]], lg.match, function (v) { lg.match = v; draw(); sum(); render(); }));
		line.appendChild(el('span', '', T.ofRules));
		box.appendChild(line);

		var rules = el('div', 'nxe-rules');
		box.appendChild(rules);

		if (!lg.rules.length) {
			var q = el('div', 'nxe-quick');
			q.appendChild(el('p', 'nxe-f__hint', T.quickTitle));
			QUICK(srcs).forEach(function (o) {
				var b = el('button', 'nxe-quick__b', '+ ' + o[0]);
				b.type = 'button';
				b.addEventListener('click', function () { lg.rules.push(JSON.parse(JSON.stringify(o[1]))); render(); });
				q.appendChild(b);
			});
			rules.appendChild(q);
		}

		lg.rules.forEach(function (r, ri) {
			var card = el('div', 'nxe-rule');
			card.appendChild(el('span', 'nxe-rule__n', ri === 0 ? T.when : (lg.match === 'any' ? T.or : T.and)));
			var body = el('div', 'nxe-rule__body');

			var fs = el('select', 'nxe-rule__field');
			var g1 = el('optgroup'); g1.label = T.groupShop;
			[['__qty', T.qtySource], ['__price', T.priceSource], ['__user', T.userSource], ['__day', T.daySource]].forEach(function (o) {
				var op = el('option', '', o[1]); op.value = o[0]; if (r.field === o[0]) { op.selected = true; } g1.appendChild(op);
			});
			var g2 = el('optgroup'); g2.label = T.groupFields;
			srcs.forEach(function (s) { var op = el('option', '', fieldName(s) + ' · ' + typeName(s)); op.value = s.id; if (r.field === s.id) { op.selected = true; } g2.appendChild(op); });
			var o0 = el('option', '', T.pickField); o0.value = ''; if (!r.field) { o0.selected = true; }
			fs.appendChild(o0); fs.appendChild(g1);
			if (srcs.length) { fs.appendChild(g2); }
			fs.addEventListener('change', function () { r.field = fs.value; r.value = ''; r.op = fs.value ? OPS_BY[kindOf(sourceOf(fs.value), fs.value)][0] : 'is'; render(); });
			body.appendChild(fs);

			if (r.field) {
				var src = sourceOf(r.field);
				var kind = kindOf(src, r.field);
				var allowed = OPS_BY[kind];
				if (allowed.indexOf(r.op) < 0) { r.op = allowed[0]; }
				var ops = el('select', 'nxe-rule__op');
				allowed.forEach(function (k) { var op = el('option', '', opLabel(kind, k)); op.value = k; if (r.op === k) { op.selected = true; } ops.appendChild(op); });
				ops.addEventListener('change', function () { r.op = ops.value; if (r.op === 'in' || r.op === 'notin' || r.op === 'is' || r.op === 'isnot') { r.value = ''; } render(); });
				body.appendChild(ops);
				var val = el('div', 'nxe-rule__val');
				val.appendChild(ruleValue(r, src, kind));
				body.appendChild(val);
			}
			card.appendChild(body);
			var rm = el('button', 'nexvio-fb__tool nexvio-fb__tool--remove');
			rm.type = 'button'; rm.title = T.remove; rm.setAttribute('aria-label', T.remove);
			rm.appendChild(el('span', 'dashicons dashicons-no-alt'));
			rm.addEventListener('click', function () { lg.rules.splice(ri, 1); render(); });
			card.appendChild(rm);
			rules.appendChild(card);
		});

		if (lg.rules.length) {
			var add = el('button', 'nexvio-btn nexvio-btn--ghost nexvio-btn--sm', '+ ' + T.addRule);
			add.type = 'button';
			add.addEventListener('click', function () { lg.rules.push({ field: srcs.length ? srcs[0].id : '__qty', op: srcs.length ? OPS_BY[kindOf(srcs[0], srcs[0].id)][0] : 'gte', value: '' }); render(); });
			box.appendChild(add);
		}
		sumNode = el('div', 'nxe-summary');
		sumField = f;
		box.appendChild(sumNode);
		sum();
		return box;
	}

	/* ------------------------------------------------------------------
	 * Field card (tabs: Basics / Choices / Price / Conditions)
	 * ------------------------------------------------------------------ */
	var tabOf = {};

	function tabsFor(f) {
		var t = TYPES[f.type];
		var list = [['basic', T.tabBasic]];
		if (t.choice) { list.push(['choices', T.tabChoices]); }
		if (f.type === 'faq') { list.push(['faq', T.tabQuestions]); }
		if (!t.layout && !t.choice && t.prices.length > 1) { list.push(['price', T.tabPrice]); }
		if (f.type !== 'faq') { list.push(['logic', T.tabLogic]); }
		return list;
	}

	function body(f) {
		var b = el('div', 'nexvio-fb__body nxe-body');
		var t = TYPES[f.type];
		var tabs = tabsFor(f);
		var cur = tabOf[f.id];
		if (!tabs.some(function (x) { return x[0] === cur; })) { cur = 'basic'; }

		var nav = el('div', 'nxe-tabs');
		nav.setAttribute('role', 'tablist');
		tabs.forEach(function (x) {
			var btn = el('button', 'nxe-tab' + (cur === x[0] ? ' is-on' : ''), x[1]);
			btn.type = 'button';
			btn.setAttribute('role', 'tab');
			btn.setAttribute('aria-selected', cur === x[0] ? 'true' : 'false');
			if (x[0] === 'logic' && f.logic.enabled) { btn.appendChild(el('i', 'nxe-tab__dot')); }
			if (x[0] === 'choices') { btn.appendChild(el('em', 'nxe-tab__count', String((f.choices || []).length))); }
			if (x[0] === 'faq') { btn.appendChild(el('em', 'nxe-tab__count', String((f.items || []).length))); }
			btn.addEventListener('click', function () { tabOf[f.id] = x[0]; render(); });
			nav.appendChild(btn);
		});
		b.appendChild(nav);

		var pane = el('div', 'nxe-pane');
		b.appendChild(pane);

		if (cur === 'logic') { pane.appendChild(logicEditor(f)); return b; }
		if (cur === 'choices') {
			pane.appendChild(heading(T.tabChoices, T.choicesHint));
			pane.appendChild(choiceEditor(f));
			return b;
		}
		if (cur === 'faq') {
			pane.appendChild(heading(T.tabQuestions, T.faqHint));
			pane.appendChild(faqEditor(f));
			return b;
		}
		if (cur === 'price') {
			pane.appendChild(heading(T.priceHeading, T.priceIntro));
			var names = { none: T.priceNone, fixed: T.priceFixed, percent: T.pricePct, char: T.priceChar, value: T.priceValue };
			var opts = {};
			t.prices.forEach(function (p) { opts[p] = names[p]; });
			var hints = { none: T.priceNoneHint, fixed: T.priceFixedHint, percent: T.pricePctHint, char: T.priceCharHint, value: T.priceValueHint };
			pane.appendChild(selectIn(f, 'price_type', T.priceHow, opts, true, hints[f.price_type] || ''));
			if (f.price_type !== 'none') {
				var w = el('span', 'nxe-money');
				w.appendChild(el('span', 'nxe-money__unit nxe-money__unit--static', f.price_type === 'percent' ? '%' : D.currency));
				var inp = el('input'); inp.type = 'number'; inp.step = 'any'; inp.value = f.price || 0;
				inp.addEventListener('input', function () { f.price = parseFloat(inp.value) || 0; draw(); });
				w.appendChild(inp);
				pane.appendChild(row(f.price_type === 'percent' ? T.pricePctAmt : T.priceAmt, '', w, 'nxe-row--short'));
			}
			return b;
		}

		/* basics */
		if (f.type === 'divider') { pane.appendChild(el('p', 'nxe-f__hint nxe-pad', T.dividerHint)); return b; }
		if (f.type === 'header') {
			pane.appendChild(textIn(f, 'label', T.headingText, '', ''));
			pane.appendChild(textIn(f, 'description', T.desc, '', T.descHint));
			pane.appendChild(segIn(f, 'tag', T.tag, { h2: T.big, h3: T.medium, h4: T.small }));
			return b;
		}
		if (f.type === 'faq') {
			if (f.apply !== 'products' && f.apply !== 'form') { f.apply = 'form'; }
			pane.appendChild(textIn(f, 'label', T.faqTitle, T.faqTitlePh, T.faqTitleHint));
			pane.appendChild(heading(T.faqShowOn, T.faqShowOnHint));
			pane.appendChild(segIn(f, 'apply', T.faqShowOn, { products: T.faqOnProducts, form: T.faqOnForm }, '', true));
			if (f.apply === 'products') {
				var pp = el('div', 'nxe-pad');
				pp.appendChild(productPicker(f));
				pane.appendChild(pp);
			} else {
				pane.appendChild(el('p', 'nxe-f__hint nxe-pad', T.faqFormNote));
			}
			if (!f.kind) { f.kind = 'faq'; }
			if (!f.look) { f.look = 'cards'; }
			if (!f.badge) { f.badge = 'auto'; }
			pane.appendChild(heading(T.faqKind));
			var kindRow = segIn(f, 'kind', T.faqKind, { faq: T.faqKindFaq, features: T.faqKindFeat, info: T.faqKindInfo }, '', false);
			kindRow.addEventListener('click', function () {
				if (f.kind === 'features' && f.style !== 'static') { f.style = 'static'; }
				if (f.kind === 'faq' && f.style === 'static') { f.style = 'accordion'; }
				f.badge = 'auto';
				f.look = f.kind === 'features' ? 'tiles' : (f.look === 'tiles' ? 'cards' : f.look);
				setTimeout(render, 0);
			});
			pane.appendChild(kindRow);
			pane.appendChild(heading(T.faqLook));
			pane.appendChild(segIn(f, 'look', T.faqLook, { cards: T.lkCards, lines: T.lkLines, filled: T.lkFilled, accent: T.lkAccent, pills: T.lkPills, dark: T.lkDark, numbered: T.lkNumbered, soft: T.lkSoft, outline: T.lkOutline, tiles: T.lkTiles }, ''));
			pane.appendChild(segIn(f, 'badge', T.faqBadge, { auto: T.faqBadgeAuto, qa: T.faqBadgeQa, number: T.faqBadgeNum, check: T.faqBadgeCheck, none: T.faqBadgeNone }, ''));
			pane.appendChild(segIn(f, 'style', T.faqStyle, { accordion: T.faqOne, multi: T.faqMany, static: T.faqAll }, T.faqStyleHint));
			if (f.style !== 'static') { pane.appendChild(switchIn(f, 'open_first', T.faqOpenFirst, T.faqOpenFirstHint)); }
			var info = el('div', 'nxe-info');
			info.appendChild(el('span', 'dashicons dashicons-info-outline'));
			info.appendChild(el('p', '', T.faqInfo));
			pane.appendChild(info);
			return b;
		}

		pane.appendChild(heading(T.secField));
		pane.appendChild(textIn(f, 'label', T.label, '', T.labelHint));
		pane.appendChild(textIn(f, 'description', T.desc, '', T.descHint));
		if (['text', 'textarea', 'number', 'select'].indexOf(f.type) > -1) { pane.appendChild(textIn(f, 'placeholder', T.placeholder, '', T.placeholderHint)); }
		if (['text', 'textarea', 'number', 'date', 'time', 'range'].indexOf(f.type) > -1) {
			pane.appendChild(textIn(f, 'default', T.default, '', T.defaultHint, f.type === 'date' ? 'date' : (f.type === 'time' ? 'time' : (f.type === 'number' || f.type === 'range' ? 'number' : 'text'))));
		}
		if (f.type === 'text' || f.type === 'textarea') { pane.appendChild(numIn(f, 'maxlength', T.maxlength, { min: 0 }, T.maxHint, T.chars)); }
		if (f.type === 'number' || f.type === 'range') {
			pane.appendChild(numIn(f, 'min', T.min, { step: 'any' }));
			pane.appendChild(numIn(f, 'max', T.max, { step: 'any' }));
			pane.appendChild(numIn(f, 'step', T.step, { step: 'any', min: 0 }, T.stepHint));
		}
		if (f.type === 'radio' || f.type === 'checkbox') { pane.appendChild(segIn(f, 'display', T.display, { list: T.list, inline: T.inline, buttons: T.buttons })); }
		if (f.type === 'swatch') {
			pane.appendChild(segIn(f, 'swatch_mode', T.swMode, { color: T.swColour, image: T.swImage }, '', true));
			pane.appendChild(switchIn(f, 'multiple', T.swMulti, T.swMultiHint, null, true));
		}

		pane.appendChild(heading(T.secRules));
		pane.appendChild(switchIn(f, 'required', T.requiredShort, T.requiredHint));
		pane.appendChild(switchIn(f, 'hide_label', T.hideLabel, T.hideLabelHint));
		pane.appendChild(segIn(f, 'width', T.width, { full: T.full, half: T.half, third: T.third }, T.widthHint));
		return b;
	}

	function move(i, d) {
		var j = i + d;
		if (j < 0 || j >= fields.length) { return; }
		var x = fields[i]; fields[i] = fields[j]; fields[j] = x;
		if (openCard === i) { openCard = j; } else if (openCard === j) { openCard = i; }
		render();
	}

	function summary(f) {
		var parts = [typeName(f)];
		if (f.required) { parts.push(T.requiredBadge); }
		if (f.choices) { parts.push(f.choices.length + ' ' + T.choicesWord); }
		if (f.items) { parts.push(f.items.length + ' ' + T.questionsWord); }
		if (f.type === 'faq' && f.apply === 'products') { parts.push((f.products || []).length + ' ' + T.productsWord); }
		if (f.price_type && f.price_type !== 'none' && parseFloat(f.price)) { parts.push(T.price + ' ' + f.price + (f.price_type === 'percent' ? '%' : '')); }
		if (f.logic && f.logic.enabled) { parts.push(T.conditional); }
		return parts.join(' · ');
	}

	function card(f, i) {
		var li = el('li', 'nexvio-fb__item' + (openCard === i ? ' is-open' : ''));
		li.draggable = true;
		li.dataset.index = i;

		var head = el('div', 'nexvio-fb__head');
		var grip = el('span', 'nexvio-fb__grip dashicons dashicons-menu');
		grip.setAttribute('aria-hidden', 'true');
		head.appendChild(grip);
		var ic = el('span', 'nexvio-fb__icon dashicons ' + (TYPES[f.type] ? TYPES[f.type].icon : 'dashicons-admin-generic'));
		ic.setAttribute('aria-hidden', 'true');
		head.appendChild(ic);

		var titles = el('div', 'nexvio-fb__titles');
		if (f.type === 'divider') {
			titles.appendChild(el('strong', 'nexvio-fb__label-static', typeName(f)));
		} else {
			var lab = el('input', 'nexvio-fb__label');
			lab.type = 'text'; lab.value = f.label; lab.placeholder = typeName(f); lab.setAttribute('aria-label', T.label);
			lab.addEventListener('input', function () { f.label = lab.value; draw(); });
			lab.addEventListener('change', function () { render(); });
			lab.addEventListener('mousedown', function (e) { e.stopPropagation(); });
			titles.appendChild(lab);
		}
		titles.appendChild(el('span', 'nexvio-fb__type', summary(f)));
		head.appendChild(titles);

		var tools = el('div', 'nexvio-fb__tools');
		function tool(cls, icon, label, fn) {
			var b = el('button', 'nexvio-fb__tool' + (cls ? ' ' + cls : ''));
			b.type = 'button'; b.title = label; b.setAttribute('aria-label', label);
			b.appendChild(el('span', 'dashicons ' + icon));
			b.addEventListener('click', function (e) { e.stopPropagation(); fn(); });
			tools.appendChild(b);
		}
		tool('', 'dashicons-arrow-up-alt2', T.up, function () { move(i, -1); });
		tool('', 'dashicons-arrow-down-alt2', T.down, function () { move(i, 1); });
		tool('', 'dashicons-admin-page', T.copy, function () {
			var c = JSON.parse(JSON.stringify(f));
			c.id = rid('f');
			(c.choices || []).forEach(function (ch) { ch.id = rid('c'); });
			(c.items || []).forEach(function (it) { it.id = rid('q'); });
			c.logic = { enabled: false, action: 'show', match: 'all', rules: [] };
			fields.splice(i + 1, 0, c);
			openCard = i + 1;
			render();
		});
		tool('nexvio-fb__tool--more', openCard === i ? 'dashicons-arrow-up' : 'dashicons-edit', T.more, function () { openCard = openCard === i ? -1 : i; render(); });
		tool('nexvio-fb__tool--remove', 'dashicons-no-alt', T.remove, function () {
			fields.splice(i, 1);
			openCard = -1;
			render();
		});
		head.appendChild(tools);
		head.addEventListener('click', function (e) { if (e.target.closest('input, button')) { return; } openCard = openCard === i ? -1 : i; render(); });
		li.appendChild(head);
		if (openCard === i) { li.appendChild(body(f)); }

		li.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', String(i)); li.classList.add('is-drag'); });
		li.addEventListener('dragend', function () { li.classList.remove('is-drag'); });
		li.addEventListener('dragover', function (e) { e.preventDefault(); });
		li.addEventListener('drop', function (e) {
			e.preventDefault();
			var from = parseInt(e.dataTransfer.getData('text/plain'), 10);
			if (isNaN(from) || from === i) { return; }
			var m = fields.splice(from, 1)[0];
			fields.splice(i, 0, m);
			openCard = -1;
			render();
		});
		return li;
	}

	function render() {
		var scroll = window.pageYOffset;
		list.textContent = '';
		if (!fields.length) { list.appendChild(el('li', 'nexvio-fb__empty', T.empty)); }
		fields.forEach(function (f, i) { list.appendChild(card(f, i)); });
		draw();
		window.scrollTo(0, scroll);
	}

	/* ------------------------------------------------------------------
	 * Live preview: the real product-page markup (rendered by the server)
	 * and the real product-page script, so it can be tried out.
	 * ------------------------------------------------------------------ */
	var pvTimer = null, pvSeq = 0;

	function draw() {
		clearTimeout(pvTimer);
		pvTimer = setTimeout(paint, 220);
	}
	function paint() {
		var seq = ++pvSeq;
		if (!fields.length) { preview.innerHTML = '<p class="nexvio-fb__empty">' + esc(T.empty) + '</p>'; return; }
		$.post(D.ajax, { action: 'nexvio_epo_preview', nonce: D.nonce, fields: JSON.stringify(fields), base: 50 }).done(function (res) {
			if (seq !== pvSeq || !res || !res.success) { return; }
			preview.innerHTML = res.data.html || '<p class="nexvio-fb__empty">' + esc(T.empty) + '</p>';
			if (window.NexvioEpo) { window.NexvioEpo.init(preview); }
		});
	}

	/* ------------------------------------------------------------------
	 * Assignment panels
	 * ------------------------------------------------------------------ */
	function syncApply() {
		var v = $('input[name="nexvio_epo_rules[apply]"]:checked').val();
		$('.nexvio-epo-panel').each(function () { this.hidden = this.getAttribute('data-apply') !== v; });
	}
	$(document).on('change', 'input[name="nexvio_epo_rules[apply]"]', syncApply);

	/* product pickers */
	$('.nexvio-epo-pick').each(function () {
		var box = $(this);
		var name = box.data('name');
		var chips = box.find('.nexvio-epo-chips');
		var input = box.find('.nexvio-epo-pick__search');
		var out = box.find('.nexvio-epo-pick__results');
		var timer = null;
		chips.on('click', 'button', function () { $(this).closest('li').remove(); });
		input.on('input', function () {
			clearTimeout(timer);
			var term = input.val().trim();
			if (term.length < 2) { out.prop('hidden', true).empty(); return; }
			timer = setTimeout(function () {
				$.get(D.ajax, { action: 'nexvio_epo_search_products', nonce: D.nonce, term: term }).done(function (res) {
					out.empty();
					var rows = res && res.success ? res.data : [];
					if (!rows.length) { out.append($('<li class="is-empty"></li>').text(T.noResult)); }
					rows.forEach(function (p) {
						if (chips.find('li[data-id="' + p.id + '"]').length) { return; }
						$('<li></li>').text(p.title || ('#' + p.id)).on('click', function () {
							var li = $('<li></li>').attr('data-id', p.id);
							li.append($('<span></span>').text(p.title || ('#' + p.id)));
							li.append($('<button type="button" aria-label="x">×</button>'));
							li.append($('<input type="hidden">').attr('name', name + '[]').val(p.id));
							chips.append(li);
							input.val('');
							out.prop('hidden', true).empty();
						}).appendTo(out);
					});
					out.prop('hidden', false);
				});
			}, 250);
		});
		$(document).on('click', function (e) { if (!$(e.target).closest(box).length) { out.prop('hidden', true); } });
	});

	/* ------------------------------------------------------------------
	 * Save
	 * ------------------------------------------------------------------ */
	form.addEventListener('submit', function () { jsonInput.value = JSON.stringify(fields); });

	syncApply();
	render();
})(jQuery);
