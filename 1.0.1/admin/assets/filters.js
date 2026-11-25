/* Nexvio - Filters page: add, order and style the Product Filter fields, with a live preview. */
(function() {
	'use strict';
	var D = window.NEXVIO_FILTERS;
	var list = document.getElementById('nexvio-fb-list');
	var form = document.getElementById('nexvio-fb-form');
	if (!D || !list || !form) { return; }

	var T = D.i18n,
		TYPES = D.types;
	var fields = (D.fields || []).map(function(f) { return Object.assign({}, f); });
	var palette = document.getElementById('nexvio-fb-palette');
	var addBtn = document.getElementById('nexvio-fb-add');
	var preview = document.getElementById('nexvio-fb-preview');
	var openCard = -1;

	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(
c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' } [c]; }); }

	function el(tag, cls, text) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (text != null) { e
				.textContent = text; } return e; }

	function attr(tax) { return (D.attributes || []).filter(function(a) { return a.taxonomy === tax; })[0] || null; }

	function key(f) { return f.type === 'attribute' ? 'attribute:' + f.taxonomy : f.type; }

	function has(k) { return fields.some(function(f) { return key(f) === k; }); }

	function typeName(f) {
		var t = TYPES[f.type] ? TYPES[f.type].label : f.type;
		if (f.type === 'attribute') { var a = attr(f.taxonomy); return t + ' · ' + (a ? a.label : f.taxonomy); }
		return t;
	}

	/* ------------------------------------------------------------------
	 * New fields
	 * ------------------------------------------------------------------ */
	function newField(type, tax) {
		var f = { type: type, label: TYPES[type].label, open: true };
		var looks = Object.keys(TYPES[type].looks || {});
		if (looks.length) { f.display = looks[0]; }
		if (type === 'attribute') {
			var a = attr(tax);
			f.taxonomy = tax;
			f.label = a ? a.label : tax;
			f.display = 'auto';
			f.counts = a && a.guess === '';
			f.hide_empty = true;
			f.logic = 'any';
			f.limit = 10;
		}
		if (type === 'category') { Object.assign(f, { display: 'radio', counts: true, hide_empty: true, collapse: true,
				all: true, limit: 10 }); }
		if (type === 'perpage') { f.values = '12,24,48'; }
		if (type === 'tag') { Object.assign(f, { counts: true, hide_empty: true, logic: 'any', limit: 10,
			display: 'chips' }); }
		if (type === 'search') { f.placeholder = ''; }
		if (type === 'sale' || type === 'featured') { f.text = ''; }
		return f;
	}

	function renderPalette() {
		palette.textContent = '';
		var attrs = D.attributes || [];
		var h1 = el('h3', 'nexvio-fb__pal-title', T.attrs);
		palette.appendChild(h1);
		var g1 = el('div', 'nexvio-fb__tiles');
		if (!attrs.length) {
			palette.appendChild(el('p', 'nexvio-fb__pal-note', T.noAttrsHelp));
		}
		attrs.forEach(function(a) {
			var icon = a.guess === 'color' ? 'dashicons-art' : (a.guess === 'size' ? 'dashicons-editor-expand' :
				'dashicons-tag');
			var hint = a.guess === 'color' ? T.colour : (a.guess === 'size' ? T.size : TYPES.attribute.label);
			var withPics = (a.terms || []).some(function(t) { return t.image; });
			if (withPics) { hint = T.image;
				icon = 'dashicons-format-image'; }
			g1.appendChild(tile(a.label, hint, icon, has('attribute:' +
				a.taxonomy), function() { add(newField('attribute', a.taxonomy)); }, (a.guess ===
				'color' || withPics) ? a.terms : null));
		});
		var mk = el('a', 'nexvio-fb__tile nexvio-fb__tile--link');
		mk.href = D.attrUrl;
		mk.target = '_blank';
		mk.rel = 'noopener';
		var mi = el('span', 'dashicons dashicons-plus-alt2');
		mi.setAttribute('aria-hidden', 'true');
		mk.appendChild(mi);
		var mt = el('span', 'nexvio-fb__tile-text');
		mt.appendChild(el('strong', '', T.newAttr));
		mt.appendChild(el('small', '', T.colour +
			', ' + T.size + '…'));
		mk.appendChild(mt);
		g1.appendChild(mk);
		palette.appendChild(g1);
		palette.appendChild(el('h3', 'nexvio-fb__pal-title', T.other));
		var g2 = el('div', 'nexvio-fb__tiles');
		Object.keys(TYPES).forEach(function(t) {
			if (t === 'attribute') { return; }
			g2.appendChild(tile(TYPES[t].label, TYPES[t].desc, TYPES[t].icon, TYPES[t].single && has(t),
		function() { add(newField(t)); }));
		});
		palette.appendChild(g2);
	}

	function tile(label, desc, icon, used, onAdd, colours) {
		var b = el('button', 'nexvio-fb__tile');
		b.type = 'button';
		b.disabled = !!used;
		var i = el('span', 'dashicons ' + icon);
		i.setAttribute('aria-hidden', 'true');
		b.appendChild(i);
		var tx = el('span', 'nexvio-fb__tile-text');
		tx.appendChild(el('strong', '', label));
		tx.appendChild(el('small', '', used ? T.added : desc));
		b.appendChild(tx);
		if (colours && colours.length) {
			var dots = el('span', 'nexvio-fb__tile-dots');
			colours.slice(0, 5).forEach(function(c) {
				var d = el('i');
				d.style.background = c.color || '#d1d5db';
				if (c.image) {
					d.style.backgroundImage = 'url("' +
						c.image.replace(/"/g, '') + '")';
					d.style.backgroundSize = 'cover';
				}
				dots.appendChild(d);
			});
			b.appendChild(dots);
		}
		b.addEventListener('click', onAdd);
		return b;
	}

	function add(f) {
		fields.push(f);
		openCard = fields.length - 1;
		togglePalette(false);
		render();
		var card = list.lastElementChild;
		if (card) { card.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); var inp = card.querySelector(
				'.nexvio-fb__label'); if (inp) { inp.focus(); } }
	}

	function togglePalette(open) {
		palette.hidden = !open;
		addBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open) { renderPalette(); }
	}
	addBtn.addEventListener('click', function() { togglePalette(palette.hidden); });

	/* ------------------------------------------------------------------
	 * Field cards
	 * ------------------------------------------------------------------ */
	function check(f, prop, label) {
		var l = el('label', 'nexvio-fb__check');
		var c = el('input');
		c.type = 'checkbox';
		c.checked = !!f[prop];
		c.addEventListener('change', function() { f[prop] = c.checked;
			draw(); });
		l.appendChild(c);
		l.appendChild(el('span', '', label));
		return l;
	}

	function textInput(f, prop, label, placeholder) {
		var w = el('label', 'nexvio-fb__text');
		w.appendChild(el('span', '', label));
		var i = el('input');
		i.type = 'text';
		i.value = f[prop] || '';
		i.placeholder = placeholder || '';
		i.addEventListener('input', function() { f[prop] = i.value;
			draw(); });
		w.appendChild(i);
		return w;
	}

	function numberInput(f, prop, label, suffix) {
		var w = el('label', 'nexvio-fb__text nexvio-fb__text--num');
		w.appendChild(el('span', '', label));
		var box = el('span', 'nexvio-fb__num');
		var i = el('input');
		i.type = 'number';
		i.min = 1;
		i.max = 100;
		i.value = f[prop] || 10;
		i.addEventListener('input', function() { f[prop] = Math.max(1, Math.min(100, parseInt(i.value, 10) || 10));
			draw(); });
		box.appendChild(i);
		box.appendChild(el('em', '', suffix));
		w.appendChild(box);
		return w;
	}

	function card(f, i) {
		var li = el('li', 'nexvio-fb__item' + (openCard === i ? ' is-open' : ''));
		li.draggable = true;
		li.dataset.index = i;

		var head = el('div', 'nexvio-fb__head');
		var grip = el('span', 'nexvio-fb__grip dashicons dashicons-menu');
		grip.setAttribute('aria-hidden', 'true');
		head.appendChild(grip);
		var ic = el('span', 'nexvio-fb__icon dashicons ' +
			(TYPES[f.type] ? TYPES[f.type].icon : 'dashicons-filter'));
		ic.setAttribute('aria-hidden', 'true');
		head.appendChild(ic);
		var titles = el('div', 'nexvio-fb__titles');
		var lab = el('input', 'nexvio-fb__label');
		lab.type = 'text';
		lab.value = f.label || '';
		lab.setAttribute('aria-label', T.name);
		lab.placeholder = typeName(f);
		lab.addEventListener('input', function() { f.label = lab.value;
			draw(); });
		titles.appendChild(lab);
		titles.appendChild(el('span', 'nexvio-fb__type', typeName(f)));
		head.appendChild(titles);

		var tools = el('div', 'nexvio-fb__tools');
		[
			['up', 'dashicons-arrow-up-alt2', i === 0],
			['down', 'dashicons-arrow-down-alt2', i === fields.length - 1]
		].forEach(function(b) {
			var btn = el('button', 'nexvio-fb__tool');
			btn.type = 'button';
			btn.disabled = b[2];
			btn.title = T[b[0]];
			btn.setAttribute('aria-label', T[b[0]] +
				': ' + (f.label || typeName(f)));
			var s = el('span', 'dashicons ' + b[1]);
			s.setAttribute('aria-hidden', 'true');
			btn.appendChild(s);
			btn.addEventListener('click', function() { move(i, b[0] === 'up' ? -1 : 1); });
			tools.appendChild(btn);
		});
		var more = el('button', 'nexvio-fb__tool nexvio-fb__tool--more');
		more.type = 'button';
		more.setAttribute('aria-expanded', openCard === i ? 'true' : 'false');
		more.title = T.more;
		more.appendChild(el('span', '', T.more));
		var ch = el('span', 'dashicons dashicons-arrow-down-alt2');
		ch.setAttribute('aria-hidden', 'true');
		more.appendChild(ch);
		more.addEventListener('click', function() { openCard = openCard === i ? -1 : i;
			render(); });
		tools.appendChild(more);
		var rm = el('button', 'nexvio-fb__tool nexvio-fb__tool--remove');
		rm.type = 'button';
		rm.title = T.remove;
		rm.setAttribute('aria-label', T.remove +
			': ' + (f.label || typeName(f)));
		var rs = el('span', 'dashicons dashicons-trash');
		rs.setAttribute('aria-hidden', 'true');
		rm.appendChild(rs);
		rm.addEventListener('click', function() { fields.splice(i, 1);
			openCard = -1;
			render(); });
		tools.appendChild(rm);
		head.appendChild(tools);
		li.appendChild(head);

		/* look: pill buttons */
		var looks = TYPES[f.type] ? TYPES[f.type].looks : {};
		if (looks && Object.keys(looks).length) {
			var lk = el('div', 'nexvio-fb__looks');
			lk.setAttribute('role', 'radiogroup');
			lk.setAttribute('aria-label', T.look);
			lk.appendChild(el('span', 'nexvio-fb__looks-label', T.look));
			Object.keys(looks).forEach(function(k) {
				var b = el('button', 'nexvio-fb__look' + (f.display === k ? ' is-on' : ''), looks[k]);
				b.type = 'button';
				b.setAttribute('role', 'radio');
				b.setAttribute('aria-checked', f.display === k ? 'true' : 'false');
				b.addEventListener('click', function() { f.display = k;
					render(); });
				lk.appendChild(b);
			});
			li.appendChild(lk);
		}

		/* options */
		var body = el('div', 'nexvio-fb__body');
		body.hidden = openCard !== i;
		body.appendChild(check(f, 'open', T.open));
		if (f.type === 'search') { body.appendChild(textInput(f, 'placeholder', T.placeholder, T.searchPh)); }
		if (f.type === 'category') {
			body.appendChild(check(f, 'collapse', T.collapse));
			body.appendChild(check(f, 'all', T.all));
			body.appendChild(check(f, 'counts', T.counts));
			body.appendChild(check(f, 'hide_empty', T.hideEmpty));
			body.appendChild(numberInput(f, 'limit', T.limit, T.items));
		}
		if (f.type === 'attribute' || f.type === 'tag') {
			body.appendChild(check(f, 'counts', T.counts));
			body.appendChild(check(f, 'hide_empty', T.hideEmpty));
			body.appendChild(numberInput(f, 'limit', T.limit, T.items));
			var lg = el('label', 'nexvio-fb__text');
			lg.appendChild(el('span', '', T.logic));
			var sel = el('select');
			[
				['any', T.any],
				['all', T.every]
			].forEach(function(o) { var op = el('option', '', o[1]);
				op.value = o[0];
				op.selected = (f.logic || 'any') === o[0];
				sel.appendChild(op); });
			sel.addEventListener('change', function() { f.logic = sel.value; });
			lg.appendChild(sel);
			body.appendChild(lg);
		}
		if (f.type === 'stock') { body.appendChild(check(f, 'show_out', T.showOut)); }
		if (f.type === 'perpage') { body.appendChild(textInput(f, 'values', T.perValues, '12,24,48')); }
		if (f.type === 'sale' || f.type === 'featured') { body.appendChild(textInput(f, 'text', T.text, f.type === 'sale' ? T
				.onSale : T.featured)); }
		li.appendChild(body);

		/* drag to reorder */
		li.addEventListener('dragstart', function(e) { if (document.activeElement && document.activeElement.tagName ===
				'INPUT') { e.preventDefault(); return; } li.classList.add('is-dragging');
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData('text/plain', String(i)); });
		li.addEventListener('dragend', function() { li.classList.remove('is-dragging'); });
		li.addEventListener('dragover', function(e) { e.preventDefault();
			li.classList.add('is-over'); });
		li.addEventListener('dragleave', function() { li.classList.remove('is-over'); });
		li.addEventListener('drop', function(e) {
			e.preventDefault();
			li.classList.remove('is-over');
			var from = parseInt(e.dataTransfer.getData('text/plain'), 10);
			if (isNaN(from) || from === i) { return; }
			var moved = fields.splice(from, 1)[0];
			fields.splice(i, 0, moved);
			openCard = -1;
			render();
		});
		return li;
	}

	function move(i, d) {
		var j = i + d;
		if (j < 0 || j >= fields.length) { return; }
		var tmp = fields[i];
		fields[i] = fields[j];
		fields[j] = tmp;
		if (openCard === i) { openCard = j; }
		render();
		var btn = list.children[j] && list.children[j].querySelector(d < 0 ? '.nexvio-fb__tool' :
			'.nexvio-fb__tool:nth-child(2)');
		if (btn && !btn.disabled) { btn.focus(); }
	}

	function render() {
		list.textContent = '';
		if (!fields.length) { list.appendChild(el('li', 'nexvio-fb__empty', T.empty)); }
		fields.forEach(function(f, i) { list.appendChild(card(f, i)); });
		if (!palette.hidden) { renderPalette(); }
		draw();
	}

	/* ------------------------------------------------------------------
	 * Live preview (same markup and CSS as the widget)
	 * ------------------------------------------------------------------ */
	function group(title, open, inner) {
		return '<section class="nexvio-pf__group' + (open ? ' is-open' : '') +
			'"><h4 class="nexvio-pf__gtitle"><button type="button" class="nexvio-pf__toggle" tabindex="-1" aria-expanded="' +
			(open ? 'true' : 'false') + '">' + esc(title) +
			'<svg class="nexvio-pf__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
			'<path d="m6 9 6 6 6-6"/></svg></button></h4><div class="nexvio-pf__gbody"' +
			(open ? '' : ' hidden') + '>' + inner + '</div></section>';
	}

	function opt(type, name, count, checked) {
		return '<li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="' + type + '" tabindex="-1"' +
			(checked ? ' checked' : '') + '><span class="nexvio-pf__mark"></span><span class="nexvio-pf__name">' + esc(name) +
			'</span>' +
			(count != null ? '<span class="nexvio-pf__count">' + esc(count) + '</span>' : '') + '</label></li>';
	}

	function values(f, vals) {
		var look = f.display || 'checkbox';
		if (look === 'auto') {
			var a = attr(f.taxonomy);
			look = vals.some(function(v) { return v.image; }) ? 'image' : (a && a.guess ? a.guess : 'checkbox');
		}
		var limit = f.limit || 10,
			shown = vals.slice(0, limit);
		var more = vals.length > limit ? '<button type="button" class="nexvio-pf__more" tabindex="-1">+' + (vals.length -
			limit) + '</button>' : '';
		if (look === 'color' || look === 'image') {
			return '<ul class="nexvio-pf__list nexvio-pf__list--swatches">' + shown.map(function(v, i) {
				var pic = look === 'image' && v.image ? v.image.replace(/["()\\]/g, '') : '';
				return '<li class="nexvio-pf__item"><label class="nexvio-pf__swatch" title="' + esc(v.name) +
					'"><input type="checkbox" tabindex="-1"' + (i === 0 ? ' checked' : '') +
					'><span class="nexvio-pf__dot' +
					(pic ? ' is-image' : '') + '" style="--sw:' + esc(v.color || '#d1d5db') + (pic ?
						';--sw-img:url(' + esc(pic) + ')' : '') +
					'">' + (v.color || pic ? '' : esc((v.name || '?').charAt(0))) + '</span></label></li>';
			}).join('') + '</ul>' + more;
		}
		if (look === 'select') { return '<div class="nexvio-pf__select"><select tabindex="-1"><option>' + esc(T.any) +
				'</option></select></div>'; }
		var chips = look === 'chips' || look === 'size';
		return '<ul class="nexvio-pf__list' + (chips ? ' nexvio-pf__list--chips' : '') +
			(look === 'size' ? ' nexvio-pf__list--sizes' : '') + '">' + shown.map(function(v, i) {
				if (chips) {
					return '<li class="nexvio-pf__item"><label class="nexvio-pf__opt nexvio-pf__chip"><input type="checkbox" tabindex="-1"' +
						(i === 0 ? ' checked' : '') + '><span class="nexvio-pf__name">' + esc(v.name) + '</span>' +
						(f.counts ? '<span class="nexvio-pf__count">' + esc(v.count) + '</span>' : '') + '</label></li>';
				}
				return opt(look === 'radio' ? 'radio' : 'checkbox', v.name, f.counts ? v.count : null, false);
			}).join('') + '</ul>' + more;
	}

	function draw() {
		var html = '<div class="nexvio-pf__form">';
		fields.forEach(function(f) {
			var title = f.label || typeName(f),
				open = !!f.open,
				inner = '';
			if (f.type === 'search') {
				inner =
					'<div class="nexvio-pf__search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">' +
					'<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>' +
					'<input type="search" tabindex="-1" placeholder="' +
					esc(f.placeholder || T.searchPh) + '"></div>';
			} else if (f.type === 'price') {
				var d = f.display || 'slider_boxes';
				if (d !== 'boxes') { inner +=
						'<div class="nexvio-pf__range" style="--lo:12%;--hi:78%"><div class="nexvio-pf__track">' +
						'<div class="nexvio-pf__fill"></div></div><span class="nexvio-fb__knob" style="left:12%"></span>' +
						'<span class="nexvio-fb__knob" style="left:78%"></span></div>'; }
				if (d !== 'slider') {
					inner +=
						'<div class="nexvio-pf__prices"><label class="nexvio-pf__pbox"><span class="nexvio-pf__plabel">Min</span><span class="nexvio-pf__pfield"><i>' +
						esc(D.currency) +
						'</i><input type="number" tabindex="-1" value="20"></span></label><span class="nexvio-pf__dash">–</span>' +
						'<label class="nexvio-pf__pbox"><span class="nexvio-pf__plabel">Max</span><span class="nexvio-pf__pfield"><i>' +
						esc(D.currency) + '</i><input type="number" tabindex="-1" value="180"></span></label></div>';
				}
			} else if (f.type === 'category' && f.display === 'select') {
				var cs = D.categories && D.categories.length ? D.categories : [{ name: 'Clothing', children: [
					{ name: 'Shirts' }] }];
				inner = '<div class="nexvio-pf__select"><select tabindex="-1"><option>' + esc(T.allCats) +
					'</option>' +
					cs.map(function(c) {
						return '<option>' + esc(c.name) + '</option>' +
							(c.children || []).map(function(k) { return '<option>— ' + esc(k.name) +
									'</option>'; }).join('');
					}).join('') +
					'</select></div>';
			} else if (f.type === 'category' && f.display === 'chips') {
				var cc = D.categories && D.categories.length ? D.categories : [{ name: 'Clothing', count: 12 },
				{ name: 'Bags', count: 6 }];
				var flat = [];
				cc.forEach(function(c) { flat.push({ name: c.name, count: c.count });
						(c.children || []).forEach(function(k) { flat.push({ name: k.name, count: k
								.count }); }); });
				inner = values(Object.assign({}, f, { display: 'chips' }), flat);
			} else if (f.type === 'category') {
				var box = f.display === 'checkbox' ? 'checkbox' : 'radio';
				var cats = D.categories && D.categories.length ? D.categories : [{ name: 'Clothing', count: 12,
					children: [{ name: 'Shirts', count: 7 }, { name: 'Dresses', count: 5 }] }, { name: 'Bags',
					count: 6, children: [] }];
				inner = (f.all && box === 'radio' ? '<ul class="nexvio-pf__list nexvio-pf__list--all">' +
						opt('radio', T.allCats, null, true) + '</ul>' : '') +
					'<ul class="nexvio-pf__list nexvio-pf__list--tree">' +
					cats.slice(0, f.limit || 10).map(function(c, ci) {
						var kids = c.children && c.children.length,
							openKids = kids && (!f.collapse || ci === 0);
						return '<li class="nexvio-pf__item' + (kids ? ' has-children' : '') +
							'"><div class="nexvio-pf__row"><label class="nexvio-pf__opt"><input type="' + box +
							'" tabindex="-1"><span class="nexvio-pf__mark"></span><span class="nexvio-pf__name">' +
							esc(c.name) + '</span>' +
							(f.counts ? '<span class="nexvio-pf__count">' + esc(c.count) + '</span>' : '') +
							'</label>' +
							(kids && f.collapse ?
								'<button type="button" class="nexvio-pf__sub-toggle" tabindex="-1" aria-expanded="' +
								(openKids ? 'true' : 'false') + '"><span></span></button>' : '') + '</div>' +
							(kids ? '<ul class="nexvio-pf__sublist"' + (openKids ? '' : ' hidden') + '>' +
								c.children.map(function(k) { return opt(box, k.name, f.counts ? k.count : null,
										false); }).join('') + '</ul>' : '') +
							'</li>';
					}).join('') + '</ul>';
			} else if (f.type === 'attribute') {
				var a = attr(f.taxonomy);
				inner = values(f, a && a.terms.length ? a.terms : [{ name: 'A', count: 3 }, { name: 'B', count: 2 }]);
			} else if (f.type === 'tag') {
				inner = values(f, (D.tags && D.tags.length ? D.tags : ['New', 'Limited']).map(function(
				n) { return { name: n, count: 4 }; }));
			} else if (f.type === 'stock') {
				var st = [{ name: T.inStock, count: null }, { name: T.preorder, count: null }];
				if (f.show_out) { st.push({ name: T.soldOut, count: null }); }
				inner = values(Object.assign({}, f, { counts: false }), st);
			} else if (f.type === 'sale' || f.type === 'featured') {
				inner = '<ul class="nexvio-pf__list">' + opt('checkbox', f.text || (f.type === 'sale' ? T.onSale : T
					.featured), null, false) + '</ul>';
			} else if (f.type === 'sort' || f.type === 'perpage') {
				var ch = f.type === 'sort' ? T.sorts : [T.defaultTxt].concat(String(f.values || '12,24,48').split(',')
					.filter(Boolean));
				var lk2 = f.display || (f.type === 'sort' ? 'select' : 'chips');
				if (lk2 === 'select') {
					inner = '<div class="nexvio-pf__select"><select tabindex="-1">' +
						ch.map(function(o) { return '<option>' + esc(o) + '</option>'; }).join('') +
						'</select></div>';
				} else {
					inner = '<ul class="nexvio-pf__list' + (lk2 === 'chips' ?
							' nexvio-pf__list--chips nexvio-pf__list--single' : '') + '">' +
						ch.map(function(o, oi) {
							return '<li class="nexvio-pf__item"><label class="nexvio-pf__opt' + (lk2 === 'chips' ?
									' nexvio-pf__chip' : '') +
								'"><input type="radio" tabindex="-1"' + (oi === 0 ? ' checked' : '') + '>' +
								(lk2 === 'chips' ? '' : '<span class="nexvio-pf__mark"></span>') +
								'<span class="nexvio-pf__name">' + esc(o) +
								'</span></label></li>';
						}).join('') + '</ul>';
				}
			} else if (f.type === 'rating') {
				inner = '<ul class="nexvio-pf__list nexvio-pf__list--rating">' + [4, 3, 2].map(function(n) {
					return '<li class="nexvio-pf__item"><label class="nexvio-pf__opt"><input type="radio" tabindex="-1"><span class="nexvio-pf__mark"></span><span class="nexvio-pf__stars" style="--r:' +
						n * 20 + '%"></span><span class="nexvio-pf__name">' + esc(T.starsUp.replace('%d',
						n)) + '</span></label></li>';
				}).join('') + '</ul>';
			}
			html += group(title, open, inner);
		});
		html += '</div>';
		preview.innerHTML = fields.length ? html : '<p class="nexvio-fb__empty">' + esc(T.empty) + '</p>';
	}

	/* the preview's + buttons and group titles work like on the shop */
	preview.addEventListener('click', function(e) {
		var t = e.target.closest('.nexvio-pf__sub-toggle');
		if (t) { var sub = t.closest('.nexvio-pf__item').querySelector('.nexvio-pf__sublist'); if (sub) { sub
					.hidden = !sub.hidden;
				t.setAttribute('aria-expanded', sub.hidden ? 'false' : 'true'); } return; }
		var g = e.target.closest('.nexvio-pf__toggle');
		if (g) { var sec = g.closest('.nexvio-pf__group'); var b = sec.querySelector('.nexvio-pf__gbody');
			b.hidden = !b.hidden;
			sec.classList.toggle('is-open', !b.hidden); }
	});

	/* Look: Light / Dark + main colour */
	function contrast(hex) {
		hex = (hex || '').replace('#', '');
		if (hex.length === 3) { hex = hex.replace(/(.)/g, '$1$1'); }
		if (hex.length !== 6) { return '#ffffff'; }
		var r = parseInt(hex.substr(0, 2), 16),
			g = parseInt(hex.substr(2, 2), 16),
			b = parseInt(hex.substr(4, 2), 16);
		return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.6 ? '#111111' : '#ffffff';
	}

	function setMain(hex) {
		if (!/^#([0-9a-f]{3}){1,2}$/i.test(hex || '')) { return; }
		['--nexvio-pf-check', '--nexvio-pf-fill', '--nexvio-pf-focus', '--nexvio-pf-handle-b', '--nexvio-pf-chip-border-a',
			'--nexvio-pf-btn'
		].forEach(function(v) { preview.style.setProperty(v, hex); });
		preview.style.setProperty('--nexvio-pf-chip-bg-a', 'color-mix(in srgb,' + hex + ' 16%,transparent)');
		preview.style.setProperty('--nexvio-pf-btn-t', contrast(hex));
		var app = document.getElementById('nexvio-app');
		if (app) { app.style.setProperty('--nexvio-main', hex); }
		form.querySelectorAll('.nexvio-swatch').forEach(function(sw) { sw.classList.toggle('is-active', sw.getAttribute(
				'data-colour').toLowerCase() === hex.toLowerCase()); });
	}
	var colour = form.querySelector('input.nexvio-color');
	var $ = window.jQuery;
	if (colour) {
		if ($ && $.fn.wpColorPicker) {
			$(colour).wpColorPicker({ change: function(e, ui) { setMain(ui.color.toString()); }, clear: function() { setMain(
						colour.getAttribute('data-default-color')); } });
		} else {
			colour.addEventListener('input', function() { setMain(colour.value); });
		}
		form.querySelectorAll('.nexvio-swatch').forEach(function(sw) {
			sw.addEventListener('click', function() {
				var hex = sw.getAttribute('data-colour');
				if ($ && $.fn.wpColorPicker) { $(colour).wpColorPicker('color', hex); } else { colour.value =
						hex; }
				setMain(hex);
			});
		});
		setMain(colour.value);
	}
	form.querySelectorAll('input[name="nexvio_filter_style[preset]"]').forEach(function(r) {
		r.addEventListener('change', function() {
			if (!r.checked) { return; }
			preview.classList.toggle('nexvio-pf--light', r.value === 'light');
			preview.classList.toggle('nexvio-pf--dark', r.value !== 'light');
		});
	});

	form.addEventListener('submit', function() {
		document.getElementById('nexvio-fb-json').value = JSON.stringify(fields);
	});

	render();
})();