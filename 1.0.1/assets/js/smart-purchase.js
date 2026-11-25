/* Nexvio Smart Purchase: popup, gallery (thumbnails inside the image), variations, quantity,
   Add to cart. */
(function () {
	'use strict';

	var locks = 0;
	var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };

	function num(v, d) { var n = parseFloat(v); return isNaN(n) ? d : n; }
	function jq() { return window.jQuery || null; }
	function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
	function $$(el, sel) { return Array.prototype.slice.call(el.querySelectorAll(sel)); }

	/* ---------------- page scroll lock ---------------- */
	function lockScroll() {
		if (locks++ > 0) { return; }
		var sw = window.innerWidth - document.documentElement.clientWidth;
		document.documentElement.classList.add('nexvio-sp-lock');
		if (sw > 0) { document.documentElement.style.setProperty('--nexvio-sp-sw', sw + 'px'); }
	}
	function unlockScroll() {
		locks = Math.max(0, locks - 1);
		if (locks > 0) { return; }
		document.documentElement.classList.remove('nexvio-sp-lock');
		document.documentElement.style.removeProperty('--nexvio-sp-sw');
	}

	/* ---------------- one widget ---------------- */
	function init(root) {
		if (!root || root.getAttribute('data-sp-init') === '1') { return; }
		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-sp') || '{}'); } catch (e) { return; }
		root.setAttribute('data-sp-init', '1');

		var modal = root.querySelector('.nexvio-sp__modal');
		if (!modal) { return; }
		var dialog = modal.querySelector('.nexvio-sp__dialog');
		var form = modal.querySelector('.nexvio-sp__form');
		var msg = modal.querySelector('.nexvio-sp__msg');
		var wid = '';
		var holder = root.closest('.elementor-element');
		if (holder) { wid = holder.getAttribute('data-id') || ''; }

		/* ---- popup: move to <body>, keeping the Elementor wrapper classes so the Style tab still applies ---- */
		var portal = null;
		if (cfg.popup) {
			if (wid) {
				$$(document, '.nexvio-sp-portal[data-wid="' + wid + '"][data-pid="' + cfg.product.id + '"]').forEach(function (p) { p.parentNode.removeChild(p); });
			}
			portal = document.createElement('div');
			var docEl = root.closest('.elementor');
			portal.className = 'nexvio-sp-portal' + (docEl ? ' ' + Array.prototype.filter.call(docEl.classList, function (c) { return /^elementor(-\d+)?$/.test(c); }).join(' ') : '');
			portal.setAttribute('data-wid', wid);
			portal.setAttribute('data-pid', cfg.product.id);
			var inner = document.createElement('div');
			inner.className = 'elementor-element' + (wid ? ' elementor-element-' + wid : '');
			inner.appendChild(modal);
			portal.appendChild(inner);
			document.body.appendChild(portal);
		}

		/* ---- state ---- */
		var variations = [];
		try { variations = JSON.parse(form.getAttribute('data-variations') || '[]') || []; } catch (e2) {}
		var isVar = cfg.product.type === 'variable';
		var selected = {};
		var current = null; /* the matching variation */
		var qty = form.querySelector('.nexvio-sp__qty-input');
		var qLim = { min: cfg.product.min, max: cfg.product.max > 0 ? cfg.product.max : Infinity, step: cfg.product.step || 1 };
		var priceEl = modal.querySelector('.nexvio-sp__price');
		var stockEl = modal.querySelector('.nexvio-sp__stock');
		var skuEl = modal.querySelector('.nexvio-sp__sku [data-default]');
		var img = modal.querySelector('.nexvio-sp__img');
		var thumbsBox = modal.querySelector('.nexvio-sp__thumbs');
		var opener = null;
		var open = false;
		var closeTimer = null;

		var cmsg = root.querySelector('.nexvio-sp__cmsg');
		function setMsg(html, isErr) {
			[msg, cmsg].forEach(function (m) {
				if (!m) { return; }
				m.classList.toggle('is-error', !!isErr);
				m.innerHTML = html || '';
			});
		}
		function allBtns() { return $$(root, '.nexvio-sp__btn').concat($$(modal, '.nexvio-sp__btn')); }

		/* ================= gallery ================= */
		function thumbs() { return thumbsBox ? $$(thumbsBox, '.nexvio-sp__thumb') : []; }
		function activeIndex() {
			var t = thumbs();
			for (var i = 0; i < t.length; i++) { if (t[i].classList.contains('is-active')) { return i; } }
			return 0;
		}
		function paint(src, srcset, sizes, alt) {
			if (!img || !src) { return; }
			if (img.getAttribute('src') === src) { return; }
			var pre = new Image();
			var done = false;
			var apply = function () {
				if (done) { return; }
				done = true;
				img.classList.add('is-swap');
				setTimeout(function () {
					img.removeAttribute('srcset');
					img.removeAttribute('sizes');
					img.src = src;
					if (srcset) { img.setAttribute('srcset', srcset); }
					if (sizes) { img.setAttribute('sizes', sizes); }
					if (alt !== undefined && alt !== null && alt !== '') { img.alt = alt; }
					requestAnimationFrame(function () { img.classList.remove('is-swap'); });
				}, reduce.matches ? 0 : 120);
			};
			pre.onload = apply;
			pre.onerror = apply;
			pre.src = src;
			if (pre.complete) { apply(); }
		}
		function showThumb(btn, scroll) {
			var t = thumbs();
			t.forEach(function (b) {
				var on = b === btn;
				b.classList.toggle('is-active', on);
				b.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			if (!btn) { return; }
			paint(btn.getAttribute('data-src'), btn.getAttribute('data-srcset'), btn.getAttribute('data-sizes'), btn.getAttribute('data-alt'));
			if (scroll !== false && thumbsBox && thumbsBox.scrollWidth > thumbsBox.clientWidth) {
				var left = btn.offsetLeft - (thumbsBox.clientWidth - btn.offsetWidth) / 2;
				thumbsBox.scrollTo ? thumbsBox.scrollTo({ left: left, behavior: reduce.matches ? 'auto' : 'smooth' }) : (thumbsBox.scrollLeft = left);
			}
		}
		function removeVarThumb() {
			if (!thumbsBox) { return; }
			var v = thumbsBox.querySelector('.nexvio-sp__thumb[data-var="1"]');
			if (v) { v.parentNode.removeChild(v); }
		}
		function showVariationImage(v) {
			var im = v && v.image ? v.image : null;
			if (!im || !im.src) { resetGallery(); return; }
			var t = thumbs();
			var match = null;
			t.forEach(function (b) {
				if (!match && v.image_id && String(b.getAttribute('data-id')) === String(v.image_id) && b.getAttribute('data-var') !== '1') { match = b; }
			});
			removeVarThumb();
			if (!match && thumbsBox) {
				match = document.createElement('button');
				match.type = 'button';
				match.className = 'nexvio-sp__thumb';
				match.setAttribute('data-var', '1');
				match.setAttribute('data-id', v.image_id || '');
				match.setAttribute('aria-label', im.alt || im.title || 'Variation image');
				match.innerHTML = '<img alt="" draggable="false">';
				match.firstChild.src = im.gallery_thumbnail_src || im.thumb_src || im.src;
				thumbsBox.insertBefore(match, thumbsBox.firstChild);
			}
			var fake = match || { getAttribute: function (k) { return { 'data-src': im.src, 'data-srcset': im.srcset || '', 'data-sizes': im.sizes || '', 'data-alt': im.alt || '' }[k] || ''; } };
			if (match && match.getAttribute('data-var') === '1') {
				match.setAttribute('data-src', im.src);
				match.setAttribute('data-srcset', im.srcset || '');
				match.setAttribute('data-sizes', im.sizes || '');
				match.setAttribute('data-alt', im.alt || '');
			}
			if (match) { showThumb(match); } else { paint(fake.getAttribute('data-src'), fake.getAttribute('data-srcset'), fake.getAttribute('data-sizes'), fake.getAttribute('data-alt')); }
		}
		function resetGallery() {
			removeVarThumb();
			var t = thumbs();
			if (t.length) { showThumb(t[0]); }
			else if (img) { paint(img.getAttribute('data-default-src'), img.getAttribute('data-default-srcset'), img.getAttribute('data-default-sizes')); }
		}
		function nav(dir) {
			var t = thumbs();
			if (t.length < 2) { return; }
			var i = (activeIndex() + dir + t.length) % t.length;
			showThumb(t[i]);
		}

		/* swipe on the main image */
		(function () {
			var stage = modal.querySelector('.nexvio-sp__main');
			if (!stage) { return; }
			var x0 = null;
			stage.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
			stage.addEventListener('touchend', function (e) {
				if (x0 === null) { return; }
				var dx = e.changedTouches[0].clientX - x0;
				x0 = null;
				if (Math.abs(dx) > 40) { nav(dx < 0 ? 1 : -1); }
			}, { passive: true });
		})();

		/* ================= quantity ================= */
		function clampQty() {
			if (!qty) { return cfg.product.min; }
			var l = qLim, v = Math.round(num(qty.value, l.min));
			if (l.step > 1) { v = l.min + Math.round((v - l.min) / l.step) * l.step; }
			v = Math.max(l.min, Math.min(l.max, v));
			qty.value = v;
			qty.min = l.min;
			if (isFinite(l.max)) { qty.max = l.max; } else { qty.removeAttribute('max'); }
			var steps = $$(form, '.nexvio-sp__step');
			if (steps.length === 2) {
				steps[0].disabled = v <= l.min;
				steps[1].disabled = v >= l.max;
			}
			return v;
		}
		function getQty() { return qty ? clampQty() : num(form.elements.quantity.value, 1); }

		/* ================= variations ================= */
		function groups() { return $$(modal, '.nexvio-sp__var'); }
		function allChosen() {
			return groups().every(function (g) { return !!g.getAttribute('data-value'); });
		}
		function vMatches(v, sel) {
			return groups().every(function (g) {
				var n = g.getAttribute('data-name');
				var want = sel[n];
				if (want === undefined || want === '') { return true; }
				var have = v.attributes ? v.attributes[n] : '';
				return have === '' || have === undefined || String(have).toLowerCase() === String(want).toLowerCase();
			});
		}
		function vAvailable(v) { return v.is_in_stock !== false && v.variation_is_active !== false && v.variation_is_visible !== false; }

		function refreshOptions() {
			groups().forEach(function (g) {
				var name = g.getAttribute('data-name');
				$$(g, '.nexvio-sp__opt').forEach(function (o) {
					var val = o.getAttribute('data-value');
					var test = {};
					Object.keys(selected).forEach(function (k) { test[k] = selected[k]; });
					test[name] = val;
					var ok = variations.some(function (v) { return vAvailable(v) && vMatches(v, test); });
					var disabled = !ok && variations.length > 0;
					o.classList.toggle('is-disabled', disabled);
					o.setAttribute('aria-disabled', disabled ? 'true' : 'false');
					var sel = g.getAttribute('data-value') === val;
					o.classList.toggle('is-selected', sel);
					o.setAttribute('aria-checked', sel ? 'true' : 'false');
					o.tabIndex = sel || (!g.getAttribute('data-value') && o === g.querySelector('.nexvio-sp__opt')) ? 0 : -1;
				});
				var lab = g.querySelector('.nexvio-sp__varvalue');
				if (lab) {
					var cur = g.querySelector('.nexvio-sp__opt.is-selected');
					lab.textContent = cur ? cur.getAttribute('data-label') : '';
				}
				g.classList.remove('is-missing');
			});
			var clear = modal.querySelector('.nexvio-sp__clear');
			if (clear) { clear.hidden = Object.keys(selected).length === 0; }
		}

		function textFromHtml(html) {
			var d = document.createElement('div');
			d.innerHTML = html || '';
			return (d.textContent || '').trim();
		}

		function setButtonsEnabled(on) {
			$$(modal, '.nexvio-sp__btn[data-act="add"], .nexvio-sp__btn[data-act="buy"]').forEach(function (b) {
				b.classList.toggle('is-unavailable', !on);
				b.setAttribute('aria-disabled', on ? 'false' : 'true');
			});
		}

		function resetInfo() {
			if (priceEl && priceEl.hasAttribute('data-default')) { priceEl.innerHTML = priceEl.getAttribute('data-default'); }
			if (stockEl) {
				stockEl.textContent = stockEl.getAttribute('data-default') || '';
				stockEl.className = 'nexvio-sp__stock ' + (stockEl.getAttribute('data-default-class') || '');
			}
			if (skuEl) { skuEl.textContent = skuEl.getAttribute('data-default') || ''; }
			qLim.min = cfg.product.min;
			qLim.max = cfg.product.max > 0 ? cfg.product.max : Infinity;
			clampQty();
			var vp = modal.querySelector('.nexvio-sp__varprice');
			if (vp) { vp.hidden = true; vp.innerHTML = ''; }
		}

		function applyVariation(v) {
			current = v;
			form.elements.variation_id.value = v ? v.variation_id : 0;
			if (!v) { return; }
			if (priceEl && v.price_html) { priceEl.innerHTML = v.price_html; }
			else if (!priceEl && v.price_html) {
				var vp = modal.querySelector('.nexvio-sp__varprice');
				if (vp) { vp.innerHTML = v.price_html; vp.hidden = false; }
			}
			if (stockEl) {
				var txt = textFromHtml(v.availability_html) || (v.is_in_stock ? stockEl.getAttribute('data-default') : '');
				stockEl.textContent = txt;
				stockEl.className = 'nexvio-sp__stock ' + (v.is_in_stock ? 'is-in' : 'is-out');
			}
			if (skuEl && v.sku) { skuEl.textContent = v.sku; }
			qLim.min = Math.max(cfg.product.min, num(v.min_qty, 1));
			var vmax = v.max_qty === '' || v.max_qty === null || v.max_qty === undefined ? Infinity : num(v.max_qty, Infinity);
			var pmax = cfg.product.max > 0 ? cfg.product.max : Infinity;
			qLim.max = Math.min(vmax, pmax);
			clampQty();
			showVariationImage(v);
		}

		function updateVariation(fromUser) {
			refreshOptions();
			setMsg('');
			if (!isVar) { return; }
			if (!allChosen()) {
				current = null;
				form.elements.variation_id.value = 0;
				resetInfo();
				if (fromUser) { resetGallery(); }
				setButtonsEnabled(true);
				return;
			}
			var found = variations.filter(function (v) { return vMatches(v, selected); })[0] || null;
			if (!found || !vAvailable(found) || found.is_purchasable === false) {
				current = null;
				form.elements.variation_id.value = 0;
				resetInfo();
				setButtonsEnabled(false);
				setMsg(esc(cfg.texts.unavailable), true);
				return;
			}
			setButtonsEnabled(true);
			applyVariation(found);
		}

		function pickOption(opt) {
			if (opt.classList.contains('is-disabled')) { return; }
			var g = opt.closest('.nexvio-sp__var');
			var name = g.getAttribute('data-name');
			var val = opt.getAttribute('data-value');
			if (g.getAttribute('data-value') === val) {
				g.setAttribute('data-value', '');
				delete selected[name];
			} else {
				g.setAttribute('data-value', val);
				selected[name] = val;
			}
			updateVariation(true);
		}

		function clearAll() {
			selected = {};
			groups().forEach(function (g) { g.setAttribute('data-value', ''); });
			updateVariation(true);
		}

		/* every attribute needs a choice before buying */
		function validate() {
			if (!isVar) { return true; }
			var missing = groups().filter(function (g) { return !g.getAttribute('data-value'); });
			if (missing.length) {
				missing.forEach(function (g) { g.classList.add('is-missing'); });
				setMsg(esc(cfg.texts.pick), true);
				var first = missing[0].querySelector('.nexvio-sp__opt:not(.is-disabled)');
				if (first) { first.focus(); }
				return false;
			}
			if (!current || !(parseInt(form.elements.variation_id.value, 10) > 0)) {
				setMsg(esc(cfg.texts.unavailable), true);
				return false;
			}
			return true;
		}

		/* ================= purchase actions ================= */
		function variationLabel() {
			var parts = [];
			groups().forEach(function (g) {
				var cur = g.querySelector('.nexvio-sp__opt.is-selected');
				var lab = g.querySelector('.nexvio-sp__varlabel');
				if (cur) { parts.push((lab ? lab.textContent + ': ' : '') + cur.getAttribute('data-label')); }
			});
			return parts.join(', ');
		}
		function priceText() {
			if (priceEl) {
				var ins = priceEl.querySelector('ins');
				return (ins ? ins.textContent : priceEl.textContent).replace(/\s+/g, ' ').trim();
			}
			return cfg.product.price;
		}

		function body() {
			var b = new URLSearchParams();
			b.append('nonce', cfg.nonce);
			b.append('product_id', cfg.product.id);
			b.append('quantity', getQty());
			if (isVar) {
				b.append('variation_id', form.elements.variation_id.value);
				Object.keys(selected).forEach(function (k) {
					b.append(k, selected[k]);
				});
				/* "any" attributes of the matched variation are filled from the choice already; send every choice */
			}
			return b;
		}

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

		var busy = false;
		function request(kind, btn) {
			if (busy || (btn && btn.classList.contains('is-loading'))) { return; }
			if (btn && btn.classList.contains('is-unavailable')) { setMsg(esc(cfg.texts.unavailable), true); return; }
			if (!validate()) { return; }
			busy = true;
			var label = btn.querySelector('.nexvio-sp__label');
			var oldText = label ? label.textContent : '';
			btn.classList.add('is-loading');
			btn.setAttribute('aria-busy', 'true');
			allBtns().forEach(function (b) { b.classList.add('is-locked'); });
			if (label && kind === 'add' && cfg.texts.adding) { label.textContent = cfg.texts.adding; }
			setMsg('');
			var b = body();
			if (kind === 'buy') { b.append('mode', cfg.buyMode); }
			var $ = jq();
			if ($ && kind === 'add') { $(document.body).trigger('adding_to_cart', [$(btn), { product_id: cfg.product.id, quantity: getQty() }]); }

			fetch(cfg.ajax[kind], {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: b.toString()
			})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || res.error) {
						throw new Error(res && res.message ? res.message : cfg.texts.error);
					}
					if (kind === 'buy') {
						window.location.href = res.redirect;
						return;
					}
					if (label) { label.textContent = cfg.texts.added || oldText; }
					btn.classList.add('is-added');
					var link = cfg.texts.viewCart && cfg.cartUrl ? ' <a href="' + esc(cfg.cartUrl) + '">' + esc(cfg.texts.viewCart) + '</a>' : '';
					setMsg(esc(cfg.texts.added || '') + link);
					if ($) {
						$(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, $(btn)]);
						if (!window.wc_add_to_cart_params) { applyFragments(res.fragments); }
					} else {
						applyFragments(res.fragments);
					}
					setTimeout(function () {
						btn.classList.remove('is-added');
						if (label) { label.textContent = oldText; }
					}, 2500);
					if (cfg.closeAfter && cfg.popup) { setTimeout(close, 700); }
				})
				.catch(function (err) {
					if (label) { label.textContent = oldText; }
					setMsg(esc(err && err.message && err.message !== 'Failed to fetch' ? err.message : cfg.texts.error), true);
				})
				.then(function () {
					busy = false;
					btn.classList.remove('is-loading');
					btn.removeAttribute('aria-busy');
					allBtns().forEach(function (x) { x.classList.remove('is-locked'); });
				});
		}

		/* ================= popup open / close ================= */
		function focusables() {
			return $$(dialog, 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select,textarea,[tabindex]:not([tabindex="-1"])').filter(function (el) {
				return el.offsetParent !== null || el === document.activeElement;
			});
		}
		function dur() { return reduce.matches ? 0 : cfg.dur; }

		function openPopup(from) {
			if (!cfg.popup || open) { return; }
			clearTimeout(closeTimer);
			open = true;
			opener = from || document.activeElement;
			modal.hidden = false;
			modal.setAttribute('data-anim', cfg.animIn);
			if (cfg.lock) { lockScroll(); modal.setAttribute('data-locked', '1'); }
			void modal.offsetWidth; /* start the transition from the closed state */
			modal.classList.add('is-open');
			$$(root, '[data-sp-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
			var scroller = modal.querySelector('.nexvio-sp__scroll');
			if (scroller) { scroller.scrollTop = 0; }
			setTimeout(function () { dialog.focus({ preventScroll: true }); }, dur() ? 30 : 0);
			if (!thumbs().length && img) { /* nothing to prepare */ }
		}

		function close() {
			if (!cfg.popup || !open) { return; }
			open = false;
			modal.setAttribute('data-anim', cfg.animOut);
			modal.classList.remove('is-open');
			closeTimer = setTimeout(function () {
				modal.hidden = true;
				if (modal.getAttribute('data-locked') === '1') { unlockScroll(); modal.removeAttribute('data-locked'); }
				$$(root, '[data-sp-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
				if (opener && opener.focus && document.contains(opener)) { opener.focus({ preventScroll: true }); }
			}, dur() + 30);
		}

		/* ================= events ================= */
		modal.addEventListener('click', function (e) {
			var t = e.target;
			if (!t.closest) { return; }
			var c = t.closest('[data-sp-close]');
			if (c && cfg.popup) {
				if (c.classList.contains('nexvio-sp__overlay') && !cfg.closeOverlay) { return; }
				close();
				return;
			}
			var th = t.closest('.nexvio-sp__thumb');
			if (th) { showThumb(th, false); return; }
			var nv = t.closest('[data-sp-nav]');
			if (nv) { nav(num(nv.getAttribute('data-sp-nav'), 1)); return; }
			var opt = t.closest('.nexvio-sp__opt');
			if (opt) { pickOption(opt); return; }
			if (t.closest('.nexvio-sp__clear')) { clearAll(); return; }
			var st = t.closest('.nexvio-sp__step');
			if (st) {
				e.preventDefault();
				qty.value = num(qty.value, qLim.min) + num(st.getAttribute('data-step'), 0) * qLim.step;
				clampQty();
				return;
			}
			var ab = t.closest('.nexvio-sp__btn');
			if (ab) {
				var act = ab.getAttribute('data-act');
				if (act === 'add') { request('add', ab); }
				else if (act === 'buy') { request('buy', ab); }
			}
		});

		modal.addEventListener('keydown', function (e) {
			/* keyboard for the option groups */
			var opt = e.target.closest ? e.target.closest('.nexvio-sp__opt') : null;
			if (opt && (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === 'ArrowLeft' || e.key === 'ArrowUp')) {
				e.preventDefault();
				var list = $$(opt.parentNode, '.nexvio-sp__opt:not(.is-disabled)');
				var i = list.indexOf(opt);
				if (i < 0) { return; }
				var n = list[(i + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : -1) + list.length) % list.length];
				n.focus();
				pickOption(n);
				return;
			}
			if (e.target.classList && e.target.classList.contains('nexvio-sp__thumb') && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) {
				e.preventDefault();
				nav(e.key === 'ArrowRight' ? 1 : -1);
				var t = thumbs()[activeIndex()];
				if (t) { t.focus(); }
				return;
			}
			if (!cfg.popup || !open) { return; }
			if (e.key === 'Escape' && cfg.closeEsc) { e.preventDefault(); close(); return; }
			if (e.key === 'Tab') {
				var f = focusables();
				if (!f.length) { e.preventDefault(); dialog.focus(); return; }
				var first = f[0], last = f[f.length - 1];
				if (e.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			}
		});

		if (qty) {
			qty.addEventListener('change', clampQty);
			qty.addEventListener('blur', clampQty);
		}
		form.addEventListener('submit', function (e) { e.preventDefault(); });

		/* the 3 buttons on the card: simple product = do it now, variable product = choose options in the popup */
		root.addEventListener('click', function (e) {
			var ab = e.target.closest ? e.target.closest('.nexvio-sp__cbtns .nexvio-sp__btn') : null;
			if (!ab) { return; }
			e.preventDefault();
			e.stopPropagation();
			if (isVar || cfg.cardAction === 'popup') {
				if (cfg.popup) { openPopup(ab); }
				else if (isVar) { window.location.href = cfg.productUrl; }
				else { runAction(ab); }
				return;
			}
			runAction(ab);
		}, true);
		function runAction(ab) {
			var act = ab.getAttribute('data-act');
			if (act === 'add') { request('add', ab); }
			else if (act === 'buy') { request('buy', ab); }
		}

		if (cfg.popup) {
			/* the card image and the card title open the popup, not the single product page */
			var card = root.closest ? root.closest('.nexvio-ps__card') : null;
			if (card) {
				card.addEventListener('click', function (e) {
					if (e.defaultPrevented || e.button > 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
					var lk = e.target.closest ? e.target.closest('a.nexvio-ps__media, .nexvio-ps__name a') : null;
					if (!lk || !card.contains(lk) || modal.contains(lk)) { return; }
					e.preventDefault();
					e.stopPropagation();
					openPopup(lk);
				}, true);
			}
			/* ESC also when the focus is outside the dialog */
			document.addEventListener('keydown', function (e) {
				if (open && e.key === 'Escape' && cfg.closeEsc && !modal.contains(e.target)) { close(); }
			});
			root.addEventListener('click', function (e) {
				var b = e.target.closest ? e.target.closest('[data-sp-open]') : null;
				if (b) { e.preventDefault(); openPopup(b); }
			});
			if (cfg.selector) {
				document.addEventListener('click', function (e) {
					var hit = null;
					try { hit = e.target.closest ? e.target.closest(cfg.selector) : null; } catch (err) { hit = null; }
					if (hit && !modal.contains(hit)) { e.preventDefault(); openPopup(hit); }
				});
			}
			root.nexvioSP = { open: openPopup, close: close };
		}

		/* keep the box at the ratio of the first image (only when the ratio is "Original") */
		function lockRatio() {
			if (!img || modal.classList.contains('nexvio-sp--ar-lock')) { return; }
			var cs = window.getComputedStyle ? window.getComputedStyle(img) : null;
			if (cs && cs.aspectRatio && cs.aspectRatio.indexOf('auto') !== 0) { return; }
			var w = num(img.getAttribute('width'), 0) || img.naturalWidth;
			var h = num(img.getAttribute('height'), 0) || img.naturalHeight;
			if (!w || !h) { return; }
			modal.style.setProperty('--nexvio-sp-ar', w + ' / ' + h);
			modal.classList.add('nexvio-sp--ar-lock');
		}
		if (img) {
			lockRatio();
			if (!modal.classList.contains('nexvio-sp--ar-lock')) { img.addEventListener('load', lockRatio, { once: true }); }
		}

		clampQty();
		if (isVar) { refreshOptions(); }
	}

	function initAll(scope) {
		$$(scope || document, '.nexvio-sp[data-sp]').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	/* cards that appear later (AJAX filter, load more) */
	if (window.MutationObserver) {
		var pending = false;
		new MutationObserver(function () {
			if (pending) { return; }
			pending = true;
			setTimeout(function () { pending = false; initAll(); }, 60);
		}).observe(document.documentElement, { childList: true, subtree: true });
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_smart_purchase.default', function ($scope) {
			if ($scope && $scope[0]) { initAll($scope[0]); }
		});
	});
})();
