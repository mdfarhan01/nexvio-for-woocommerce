/* Nexvio Product Tabs: tabs, review filter / sort / load more, star selector, AJAX review form. */
(function () {
	'use strict';

	function $(root, sel) { return root.querySelector(sel); }
	function $$(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
	function fmt(tpl, a, b) { return String(tpl).replace('%1$s', a).replace('%2$s', b).replace('%s', a); }

	function post(url, data) {
		var fd = new FormData();
		Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(url, { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) {
			return r.json().catch(function () { return { success: false, data: {} }; });
		});
	}

	function init(root) {
		if (root.getAttribute('data-nxt-ready') === '1') { return; }
		root.setAttribute('data-nxt-ready', '1');

		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-nxt') || '{}'); } catch (e) { cfg = {}; }
		var tpl = cfg.tpl || {};

		var tabs = $$(root, '.nxt__tab[role="tab"]');
		var modal = $(root, '[data-modal]');
		var lastFocus = null;
		var ink = $(root, '.nxt__ink');
		var current = root.getAttribute('data-start') || 'desc';

		/* ---------------- tabs ---------------- */
		function panel(key) { return $(root, '.nxt__panel[data-panel="' + key + '"]'); }

		function moveInk() {
			var active = $(root, '.nxt__tab[aria-selected="true"]');
			if (!active || !ink) { return; }
			ink.style.setProperty('--ink-x', active.offsetLeft + 'px');
			ink.style.setProperty('--ink-w', active.offsetWidth + 'px');
			var bar = active.closest('.nxt__tabs');
			if (bar && bar.scrollWidth > bar.clientWidth) {
				bar.scrollTo({ left: active.offsetLeft - (bar.clientWidth - active.offsetWidth) / 2, behavior: 'smooth' });
			}
		}

		function activate(key, opts) {
			opts = opts || {};
			if (!panel(key)) { return; }
			current = key;
			tabs.forEach(function (t) {
				var on = t.getAttribute('data-tab') === key;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.setAttribute('tabindex', on ? '0' : '-1');
			});
			$$(root, '.nxt__panel').forEach(function (p) {
				if (p.getAttribute('data-panel') === key) { p.removeAttribute('hidden'); } else { p.setAttribute('hidden', ''); }
			});
			moveInk();
			if (key === 'rev') { clampAll(); }
			if (opts.scroll) {
				var top = root.getBoundingClientRect().top;
				if (top < 0 || top > window.innerHeight * 0.5) {
					root.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			}
			if (opts.focus) { tabs.forEach(function (t) { if (t.getAttribute('data-tab') === key) { t.focus(); } }); }
		}

		tabs.forEach(function (t, i) {
			t.addEventListener('click', function () { activate(t.getAttribute('data-tab')); });
			t.addEventListener('keydown', function (e) {
				var n = -1;
				if (e.key === 'ArrowRight') { n = (i + 1) % tabs.length; }
				if (e.key === 'ArrowLeft') { n = (i - 1 + tabs.length) % tabs.length; }
				if (e.key === 'Home') { n = 0; }
				if (e.key === 'End') { n = tabs.length - 1; }
				if (n > -1) { e.preventDefault(); activate(tabs[n].getAttribute('data-tab'), { focus: true }); }
			});
		});

		/* ---------------- popup ---------------- */
		function openModal() {
			if (!modal) { return; }
			lastFocus = document.activeElement;
			modal.hidden = false;
			document.documentElement.classList.add('nxt-noscroll');
			void modal.offsetWidth;
			modal.classList.add('is-open');
			var dlg = $(modal, '.nxt-modal__dlg');
			setTimeout(function () { dlg.focus({ preventScroll: true }); }, 60);
		}
		function closeModal() {
			if (!modal || modal.hidden) { return; }
			modal.classList.remove('is-open');
			document.documentElement.classList.remove('nxt-noscroll');
			setTimeout(function () { modal.hidden = true; }, 280);
			if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
		}
		document.addEventListener('keydown', function (e) {
			if (!modal || modal.hidden) { return; }
			if (e.key === 'Escape') { closeModal(); return; }
			if (e.key === 'Tab') {
				var f = $$(modal, 'button, input, textarea, select, a[href]').filter(function (n) { return !n.disabled && n.offsetParent !== null; });
				if (!f.length) { return; }
				var first = f[0], last = f[f.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			}
		});

		root.addEventListener('click', function (e) {
			if (e.target.closest('[data-nxt-close]')) { closeModal(); return; }
			var go = e.target.closest('[data-nxt-goto]');
			if (go && root.contains(go)) {
				e.preventDefault();
				var key = go.getAttribute('data-nxt-goto');
				if (key === 'add' && modal) { openModal(); return; }
				if (modal && modal.contains(go)) { closeModal(); }
				activate(key, { scroll: true });
			}
		});

		/* WooCommerce links: #reviews and #review_form open the right tab */
		function fromHash() {
			var h = (window.location.hash || '').toLowerCase();
			if (h === '#reviews' || h.indexOf('#comment-') === 0 || h.indexOf('#nxt-review-') === 0) { activate('rev', { scroll: true }); }
			else if (h === '#review_form' || h === '#respond') { if (modal) { openModal(); } else { activate('add', { scroll: true }); } }
		}
		window.addEventListener('hashchange', fromHash);
		if (window.location.hash) { fromHash(); }

		window.addEventListener('resize', moveInk);
		if (document.fonts && document.fonts.ready) { document.fonts.ready.then(moveInk); }
		window.addEventListener('load', moveInk);
		activate(current);

		/* ---------------- reviews ---------------- */
		var state = { sort: 'newest', rating: 0, page: 1 };

		function rev() { return $(root, '.nxt-rev'); }

		function clampAll() {
			markOwn();
			$$(root, '.nxt-item__text').forEach(function (t) {
				var btn = t.parentNode.querySelector('.nxt-more');
				if (!btn || t.getAttribute('data-clamp-done') === '1') { return; }
				if (!t.offsetParent) { return; }
				t.setAttribute('data-clamp-done', '1');
				t.classList.add('is-clamped');
				if (t.scrollHeight > t.clientHeight + 2) { btn.hidden = false; } else { t.classList.remove('is-clamped'); }
			});
		}

		function status(shown, total) {
			var r = rev();
			if (!r) { return; }
			var s = $(r, '.nxt-bar__status');
			if (s) { s.textContent = fmt(tpl.showing || '', shown, total); }
			r.setAttribute('data-total', total);
		}

		function updateUI(total, shown, hasMore) {
			var r = rev();
			if (!r) { return; }
			status(shown, total);
			var more = $(r, '[data-nxt-more]');
			if (more) { more.hidden = !hasMore; }
			var nomatch = $(r, '.nxt-nomatch');
			if (nomatch) { nomatch.hidden = total > 0; }
			var chip = $(r, '[data-nxt-clear]');
			if (chip) {
				if (state.rating) { chip.textContent = fmt(tpl.filter || '', state.rating) + ' · ' + (tpl.clear || ''); chip.hidden = false; } else { chip.hidden = true; }
			}
			$$(r, '.nxt-dist__row').forEach(function (b) {
				b.setAttribute('aria-pressed', String(parseInt(b.getAttribute('data-rating'), 10) === state.rating));
			});
		}

		function load(append) {
			var r = rev();
			if (!r) { return; }
			var per = parseInt(r.getAttribute('data-per'), 10) || 5;
			r.classList.add('is-loading');
			post(cfg.ajax, {
				action: 'nexvio_reviews_load',
				product_id: cfg.product,
				page: state.page,
				per: per,
				sort: state.sort,
				rating: state.rating,
				verified: cfg.verified || '',
				helpful: cfg.helpful || 'yes',
				reply: cfg.reply || 'yes',
				reply_label: cfg.replyLbl || '',
				edit: cfg.edit || 'yes',
				edit_label: cfg.editLbl || ''
			}).then(function (res) {
				r.classList.remove('is-loading');
				if (!res || !res.success) { return; }
				var list = $(r, '.nxt-list');
				if (append) { list.insertAdjacentHTML('beforeend', res.data.html); } else { list.innerHTML = res.data.html; }
				updateUI(res.data.total, res.data.shown, res.data.has_more);
				clampAll();
				markHelpful();
				checkAvatars();
			}).catch(function () { r.classList.remove('is-loading'); });
		}

		function resetList() { state.page = 1; load(false); }

		root.addEventListener('change', function (e) {
			if (e.target.matches('[data-nxt-sort]')) { state.sort = e.target.value; resetList(); }
		});

		root.addEventListener('click', function (e) {
			var row = e.target.closest('.nxt-dist__row');
			if (row && root.contains(row)) {
				var v = parseInt(row.getAttribute('data-rating'), 10);
				state.rating = state.rating === v ? 0 : v;
				resetList();
				var r = rev();
				var list = r && $(r, '.nxt-bar');
				if (list && list.getBoundingClientRect().top < 0) { list.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
				return;
			}
			if (e.target.closest('[data-nxt-clear]')) { state.rating = 0; resetList(); return; }
			if (e.target.closest('[data-nxt-more]')) { state.page += 1; load(true); return; }
			var more = e.target.closest('.nxt-more');
			if (more && root.contains(more)) {
				openReview(more.closest('.nxt-item'));
				return;
			}
			var rep = e.target.closest('.nxt-rep');
			if (rep && root.contains(rep)) {
				var box = $(rep.closest('.nxt-item'), '.nxt-replies');
				if (box) {
					box.hidden = !box.hidden;
					rep.setAttribute('aria-expanded', box.hidden ? 'false' : 'true');
				}
				return;
			}
			var ebtn = e.target.closest('.nxt-edit');
			if (ebtn && root.contains(ebtn)) {
				toggleEditForm(ebtn);
				return;
			}
			var rbtn = e.target.closest('.nxt-reply-btn');
			if (rbtn && root.contains(rbtn)) {
				toggleReplyForm(rbtn);
				return;
			}
			var pin = e.target.closest('.nxt-pin');
			if (pin && root.contains(pin) && !pin.disabled) {
				var on = pin.getAttribute('data-pinned') === '1';
				pin.disabled = true;
				post(cfg.ajax, { action: 'nexvio_review_pin', comment_id: pin.getAttribute('data-id'), pin: on ? 0 : 1, nonce: pin.getAttribute('data-nonce') }).then(function (res) {
					pin.disabled = false;
					if (res && res.success) { resetList(); }
				}).catch(function () { pin.disabled = false; });
			}
		});

		/* ---------------- reply to a review ---------------- */
		var rt = cfg.rt || {};
		function esc(v) { return String(v).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }
		function replaceCard(id, html, showReplies) {
			var li = $(root, '#nxt-review-' + id);
			var tmp = document.createElement('ol');
			tmp.innerHTML = html;
			var fresh = tmp.firstElementChild;
			if (!fresh || !li) { return null; }
			li.parentNode.replaceChild(fresh, li);
			if (showReplies) {
				var rp = $(fresh, '.nxt-replies');
				if (rp) { rp.hidden = false; var rb = $(fresh, '.nxt-rep'); if (rb) { rb.setAttribute('aria-expanded', 'true'); } }
			}
			clampAll();
			markHelpful();
			checkAvatars();
			if (rmodal && !rmodal.hidden) { fillReview(fresh); }
			return fresh;
		}

		/* ---------------- edit my own review ---------------- */
		var EKEYS = 'nexvio_ekeys';
		function getKeys() { try { return JSON.parse(window.localStorage.getItem(EKEYS) || '{}') || {}; } catch (e) { return {}; } }
		function setKey(id, key) {
			try {
				var k = getKeys();
				k[id] = key;
				var ids = Object.keys(k);
				if (ids.length > 50) { delete k[ids[0]]; }
				window.localStorage.setItem(EKEYS, JSON.stringify(k));
			} catch (e) { /* storage blocked */ }
		}
		/* a guest who wrote the review in this browser gets the Edit button too */
		function markOwn() {
			if ((cfg.edit || 'yes') === 'no') { return; }
			var keys = getKeys();
			$$(root, '.nxt-list > .nxt-item').forEach(function (li) {
				var id = (li.id || '').replace('nxt-review-', '');
				if (!id || !keys[id] || $(li, '.nxt-edit')) { return; }
				var acts = $(li, '.nxt-item__acts');
				if (!acts) { return; }
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'nxt-edit';
				b.setAttribute('data-id', id);
				b.setAttribute('aria-expanded', 'false');
				b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 1-4L16.5 4.5a2 2 0 0 1 3 3L8 19l-4 1Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg><span></span>';
				$(b, 'span').textContent = cfg.editLbl || 'Edit';
				acts.insertBefore(b, acts.firstChild);
			});
		}
		function plainText(el) {
			if (!el) { return ''; }
			var c = el.cloneNode(true);
			$$(c, 'br').forEach(function (br) { br.replaceWith('\n'); });
			var ps = $$(c, 'p');
			if (ps.length) { return ps.map(function (p) { return p.textContent.trim(); }).join('\n\n'); }
			return c.textContent.trim();
		}
		function toggleEditForm(btn) {
			var item = btn.closest('.nxt-item');
			var main = item && $(item, '.nxt-item__main');
			if (!main) { return; }
			var old = $(main, '.nxt-eform');
			if (old) { old.remove(); btn.setAttribute('aria-expanded', 'false'); return; }
			var rf = $(main, '.nxt-rform');
			if (rf) { rf.remove(); }
			var id = btn.getAttribute('data-id');
			var cur = parseInt(item.getAttribute('data-rating'), 10) || 0;
			var form = document.createElement('form');
			form.className = 'nxt-rform nxt-eform';
			form.noValidate = true;
			var stars = '';
			if (cur > 0) {
				stars = '<div class="nxt-erate" role="group" aria-label="' + esc(rt.rate || 'Your rating') + '" data-v="' + cur + '">';
				for (var i = 1; i <= 5; i++) { stars += '<button type="button" class="nxt-epick' + (i <= cur ? ' is-on' : '') + '" data-v="' + i + '" aria-label="' + i + '">★</button>'; }
				stars += '</div>';
			}
			form.innerHTML = stars +
				'<textarea name="comment" rows="4" maxlength="5000"></textarea>' +
				'<p class="nxt-rform__msg" aria-live="polite"></p>' +
				'<div class="nxt-rform__btns"><button type="button" class="nxt-rform__cancel">' + esc(rt.cancel || 'Cancel') + '</button><button type="submit" class="nxt-rform__send">' + esc(rt.save || 'Save changes') + '</button></div>';
			$(form, 'textarea').value = plainText($(item, '.nxt-item__text'));
			main.appendChild(form);
			btn.setAttribute('aria-expanded', 'true');
			$(form, 'textarea').focus();
			var rate = $(form, '.nxt-erate');
			if (rate) {
				rate.addEventListener('click', function (ev) {
					var pick = ev.target.closest('.nxt-epick');
					if (!pick) { return; }
					var v = parseInt(pick.getAttribute('data-v'), 10);
					rate.setAttribute('data-v', v);
					$$(rate, '.nxt-epick').forEach(function (b) { b.classList.toggle('is-on', parseInt(b.getAttribute('data-v'), 10) <= v); });
				});
			}
			$(form, '.nxt-rform__cancel').addEventListener('click', function () { form.remove(); btn.setAttribute('aria-expanded', 'false'); });
			form.addEventListener('submit', function (ev) {
				ev.preventDefault();
				var ta = $(form, 'textarea');
				var msg = $(form, '.nxt-rform__msg');
				var send = $(form, '.nxt-rform__send');
				ta.classList.remove('is-bad');
				if (!ta.value.trim()) { ta.classList.add('is-bad'); return; }
				send.disabled = true;
				send.textContent = rt.saving || 'Saving…';
				post(cfg.ajax, {
					action: 'nexvio_review_edit',
					comment_id: id,
					comment: ta.value.trim(),
					rating: rate ? rate.getAttribute('data-v') : 0,
					ekey: getKeys()[id] || '',
					verified: cfg.verified || '',
					helpful: cfg.helpful || 'yes',
					reply: cfg.reply || 'yes',
					reply_label: cfg.replyLbl || '',
					edit: cfg.edit || 'yes',
					edit_label: cfg.editLbl || ''
				}).then(function (res) {
					send.disabled = false;
					send.textContent = rt.save || 'Save changes';
					if (!res || !res.success) {
						msg.className = 'nxt-rform__msg is-err';
						msg.textContent = (res && res.data && res.data.message) || tpl.error || '';
						return;
					}
					replaceCard(id, res.data.item, false);
					if (res.data.rating_changed) { refreshReviews().catch(function () { /* updated on the next visit */ }); }
				}).catch(function () {
					send.disabled = false;
					send.textContent = rt.save || 'Save changes';
					msg.className = 'nxt-rform__msg is-err';
					msg.textContent = tpl.error || '';
				});
			});
		}

		function toggleReplyForm(btn) {
			var item = btn.closest('.nxt-item');
			var main = item && $(item, '.nxt-item__main');
			if (!main) { return; }
			var old = $(main, '.nxt-rform');
			if (old) { old.remove(); btn.setAttribute('aria-expanded', 'false'); return; }
			var id = btn.getAttribute('data-id');
			var form = document.createElement('form');
			form.className = 'nxt-rform';
			form.noValidate = true;
			form.innerHTML =
				(cfg.loggedIn ? '' : '<div class="nxt-rform__row2"><input type="text" name="author" maxlength="80" autocomplete="name" placeholder="' + esc(rt.name || 'Your name') + '"><input type="email" name="email" maxlength="100" autocomplete="email" placeholder="' + esc(rt.email || 'Your email') + '"></div>') +
				'<textarea name="comment" rows="3" maxlength="1500" placeholder="' + esc(rt.placeholder || 'Write your reply…') + '"></textarea>' +
				'<input type="text" name="nexvio_hp" class="nxt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">' +
				'<p class="nxt-rform__msg" aria-live="polite"></p>' +
				'<div class="nxt-rform__btns"><button type="button" class="nxt-rform__cancel">' + esc(rt.cancel || 'Cancel') + '</button><button type="submit" class="nxt-rform__send">' + esc(rt.send || 'Send reply') + '</button></div>';
			main.appendChild(form);
			btn.setAttribute('aria-expanded', 'true');
			var ta = $(form, 'textarea');
			if (ta) { ta.focus({ preventScroll: false }); }
			$(form, '.nxt-rform__cancel').addEventListener('click', function () { form.remove(); btn.setAttribute('aria-expanded', 'false'); });
			form.addEventListener('submit', function (ev) {
				ev.preventDefault();
				submitReply(form, id, btn);
			});
		}
		function submitReply(form, id, btn) {
			var msg = $(form, '.nxt-rform__msg');
			var send = $(form, '.nxt-rform__send');
			var f = { comment: $(form, '[name="comment"]'), author: $(form, '[name="author"]'), email: $(form, '[name="email"]') };
			var bad = false;
			[f.comment, f.author, f.email].forEach(function (el) { if (el) { el.classList.remove('is-bad'); } });
			if (!f.comment.value.trim()) { f.comment.classList.add('is-bad'); bad = true; }
			if (f.author && !f.author.value.trim()) { f.author.classList.add('is-bad'); bad = true; }
			if (f.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.value.trim())) { f.email.classList.add('is-bad'); bad = true; }
			if (bad) { msg.className = 'nxt-rform__msg is-err'; msg.textContent = tpl.required || ''; return; }
			msg.className = 'nxt-rform__msg';
			msg.textContent = '';
			send.disabled = true;
			send.textContent = rt.sending || 'Sending…';
			post(cfg.ajax, {
				action: 'nexvio_review_reply',
				comment_post_ID: cfg.product,
				comment_parent: id,
				comment: f.comment.value.trim(),
				author: f.author ? f.author.value.trim() : '',
				email: f.email ? f.email.value.trim() : '',
				nexvio_hp: $(form, '[name="nexvio_hp"]').value,
				verified: cfg.verified || '',
				helpful: cfg.helpful || 'yes',
				reply: cfg.reply || 'yes',
				reply_label: cfg.replyLbl || '',
				edit: cfg.edit || 'yes',
				edit_label: cfg.editLbl || ''
			}).then(function (res) {
				send.disabled = false;
				send.textContent = rt.send || 'Send reply';
				if (!res || !res.success) {
					msg.className = 'nxt-rform__msg is-err';
					msg.textContent = (res && res.data && res.data.message) || tpl.error || '';
					return;
				}
				if (res.data.approved && res.data.item) { replaceCard(id, res.data.item, true); }
				if (form.isConnected) {
					msg.className = 'nxt-rform__msg';
					msg.textContent = res.data.message || '';
					form.reset();
				}
			}).catch(function () {
				send.disabled = false;
				send.textContent = rt.send || 'Send reply';
				msg.className = 'nxt-rform__msg is-err';
				msg.textContent = tpl.error || '';
			});
		}

		/* ---------------- "Read more": the whole review in a popup ---------------- */
		var rmodal = null;
		var rLast = null;
		function buildReviewModal() {
			rmodal = document.createElement('div');
			rmodal.className = 'nxt-modal nxt-rmodal';
			rmodal.hidden = true;
			rmodal.innerHTML = '<div class="nxt-modal__ov" data-nxt-rclose></div>' +
				'<div class="nxt-modal__dlg" role="dialog" aria-modal="true" tabindex="-1">' +
				'<button type="button" class="nxt-modal__x" data-nxt-rclose aria-label="Close"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 5 10 10M15 5 5 15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>' +
				'<div class="nxt-item nxt-item--full"></div></div>';
			root.appendChild(rmodal);
			rmodal.addEventListener('click', function (e) {
				if (e.target.closest('[data-nxt-rclose]')) { closeReview(); }
			});
		}
		function fillReview(li) {
			var body = $(rmodal, '.nxt-item--full');
			body.innerHTML = li.innerHTML;
			$$(body, '.nxt-more, .nxt-pin, .nxt-rep, .nxt-rform, .nxt-edit').forEach(function (n) { n.remove(); });
			var rb = $(body, '.nxt-reply-btn');
			if (rb) { rb.setAttribute('aria-expanded', 'false'); }
			var t = $(body, '.nxt-item__text');
			if (t) { t.classList.remove('is-clamped'); }
			var rp = $(body, '.nxt-replies');
			if (rp) { rp.hidden = false; }
			markHelpful();
		}
		function openReview(li) {
			if (!li) { return; }
			if (!rmodal) { buildReviewModal(); }
			fillReview(li);
			rLast = document.activeElement;
			rmodal.hidden = false;
			document.documentElement.classList.add('nxt-noscroll');
			void rmodal.offsetWidth;
			rmodal.classList.add('is-open');
			var dlg = $(rmodal, '.nxt-modal__dlg');
			setTimeout(function () { dlg.focus({ preventScroll: true }); }, 60);
		}
		function closeReview() {
			if (!rmodal || rmodal.hidden) { return; }
			rmodal.classList.remove('is-open');
			document.documentElement.classList.remove('nxt-noscroll');
			setTimeout(function () { if (rmodal) { rmodal.hidden = true; } }, 280);
			if (rLast && rLast.focus) { rLast.focus(); }
		}
		document.addEventListener('keydown', function (e) {
			if (rmodal && !rmodal.hidden && e.key === 'Escape') { closeReview(); }
		});

		(function firstStatus() {
			var r = rev();
			if (!r) { return; }
			var total = parseInt(r.getAttribute('data-total'), 10) || 0;
			var per = parseInt(r.getAttribute('data-per'), 10) || 5;
			status(Math.min(total, per), total);
		})();

		/* ---------------- avatars: a missing photo falls back to the initials ---------------- */
		function checkAvatars() {
			$$(root, '.nxt-avatar__img').forEach(function (img) {
				if (img.complete && img.naturalWidth === 0) { img.remove(); }
			});
		}
		root.addEventListener('error', function (e) {
			if (e.target && e.target.classList && e.target.classList.contains('nxt-avatar__img')) { e.target.remove(); }
		}, true);

		/* ---------------- "Helpful" votes ---------------- */
		var VOTES = 'nxt_helpful';
		function getVotes() { try { return JSON.parse(window.localStorage.getItem(VOTES) || '[]'); } catch (e) { return []; } }
		function setVotes(a) { try { window.localStorage.setItem(VOTES, JSON.stringify(a.slice(-500))); } catch (e) { /* storage blocked */ } }
		function paintHelp(btn, on, n) {
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			btn.classList.toggle('is-on', on);
			if (typeof n === 'number') {
				btn.setAttribute('data-n', n);
				var c = $(btn, '.nxt-help__n');
				if (c) { c.textContent = n.toLocaleString(); }
			}
		}
		function paintId(id, on, n) {
			$$(root, '.nxt-help').forEach(function (x) {
				if (parseInt(x.getAttribute('data-id'), 10) === id) { paintHelp(x, on, n); }
			});
		}
		function markHelpful() {
			var v = getVotes();
			$$(root, '.nxt-help').forEach(function (b) { paintHelp(b, v.indexOf(parseInt(b.getAttribute('data-id'), 10)) > -1); });
		}
		root.addEventListener('click', function (e) {
			var b = e.target.closest('.nxt-help');
			if (!b || !root.contains(b) || b.disabled) { return; }
			var id = parseInt(b.getAttribute('data-id'), 10);
			var was = b.getAttribute('aria-pressed') === 'true';
			var n0 = parseInt(b.getAttribute('data-n'), 10) || 0;
			paintId(id, !was, Math.max(0, n0 + (was ? -1 : 1)));
			b.disabled = true;
			post(cfg.ajax, { action: 'nexvio_review_helpful', comment_id: id, vote: was ? 0 : 1 }).then(function (res) {
				b.disabled = false;
				if (!res || !res.success) { paintId(id, was, n0); return; }
				var v = getVotes().filter(function (x) { return x !== id; });
				if (res.data.voted) { v.push(id); }
				setVotes(v);
				paintId(id, !!res.data.voted, res.data.count);
			}).catch(function () { b.disabled = false; paintId(id, was, n0); });
		});
		markHelpful();
		checkAvatars();

		/* ---------------- star selector ---------------- */
		var words = cfg.words || [];

		function paint(rate, upto) {
			$$(rate, '.nxt-pick').forEach(function (p) {
				p.classList.toggle('is-on', parseInt(p.getAttribute('data-v'), 10) <= upto);
			});
		}
		function word(rate, n) {
			var w = $(rate, '.nxt-rate__word');
			if (w) { w.textContent = n ? (words[n - 1] || n + ' / 5') : ''; }
		}

		function initForm() {
			var form = $(root, '.nxt-f');
			if (!form || form.getAttribute('data-ready') === '1') { return; }
			form.setAttribute('data-ready', '1');

			var rate = $(form, '.nxt-rate');
			var chosen = 0;

			$$(rate, '.nxt-pick').forEach(function (p) {
				var v = parseInt(p.getAttribute('data-v'), 10);
				p.addEventListener('mouseenter', function () { paint(rate, v); word(rate, v); });
				p.addEventListener('mouseleave', function () { paint(rate, chosen); word(rate, chosen); });
				$(p, 'input').addEventListener('change', function () {
					chosen = v;
					paint(rate, v);
					word(rate, v);
					rate.classList.remove('is-bad');
					$(rate, '.nxt-err').textContent = '';
					p.classList.add('is-pop');
					setTimeout(function () { p.classList.remove('is-pop'); }, 220);
				});
				$(p, 'input').addEventListener('focus', function () { paint(rate, v); word(rate, v); });
				$(p, 'input').addEventListener('blur', function () { paint(rate, chosen); word(rate, chosen); });
			});

			var ta = $(form, 'textarea');
			var counter = $(form, '[data-count]');
			ta.addEventListener('input', function () {
				if (counter) { counter.textContent = ta.value.length; }
				ta.closest('.nxt-field').classList.remove('is-bad');
				var er = $(ta.closest('.nxt-field'), '.nxt-err'); if (er) { er.textContent = ''; }
			});
			$$(form, 'input[type="text"], input[type="email"]').forEach(function (i) {
				i.addEventListener('input', function () {
					var f = i.closest('.nxt-field');
					f.classList.remove('is-bad');
					var er = $(f, '.nxt-err'); if (er) { er.textContent = ''; }
				});
			});

			function fieldError(name, msg) {
				var f = $(form, '[data-field="' + name + '"]');
				if (!f) { return false; }
				f.classList.add('is-bad');
				var er = $(f, '.nxt-err');
				if (er) { er.textContent = msg; }
				if (name === 'rating') {
					rate.classList.remove('is-shake'); void rate.offsetWidth; rate.classList.add('is-shake');
				}
				return true;
			}

			var msg = $(form, '.nxt-msg');
			var btn = $(form, '.nxt-btn[type="submit"]');

			form.addEventListener('submit', function (e) {
				e.preventDefault();
				msg.hidden = true;
				$$(form, '.is-bad').forEach(function (n) { n.classList.remove('is-bad'); });
				$$(form, '.nxt-err').forEach(function (n) { n.textContent = ''; });

				var ok = true, firstBad = null;
				if (form.getAttribute('data-required') === '1' && !chosen) { fieldError('rating', tpl.rate || ''); ok = false; firstBad = firstBad || rate; }
				var needed = { comment: ta };
				var au = $(form, 'input[name="author"]'), em = $(form, 'input[name="email"]');
				if (au) { needed.author = au; }
				if (em) { needed.email = em; }
				Object.keys(needed).forEach(function (k) {
					var el = needed[k];
					var bad = !el.value.trim() || (k === 'email' && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(el.value.trim()));
					if (bad) { el.closest('.nxt-field').classList.add('is-bad'); ok = false; firstBad = firstBad || el; }
				});
				if (!ok) {
					msg.textContent = (tpl.required || 'Please complete the highlighted fields.');
					if (!firstBad || firstBad !== rate) { msg.hidden = false; }
					if (firstBad && firstBad.focus && firstBad !== rate) { firstBad.focus(); }
					return;
				}

				var data = {};
				new FormData(form).forEach(function (v, k) { data[k] = v; });
				btn.disabled = true;
				form.classList.add('is-sending');
				$(btn, '.nxt-btn__t').textContent = cfg.sending || '…';

				post(cfg.ajax, data).then(function (res) {
					form.classList.remove('is-sending');
					btn.disabled = false;
					$(btn, '.nxt-btn__t').textContent = btn.getAttribute('data-label');
					if (res && res.success) { success(form, res.data); return; }
					var d = (res && res.data) || {};
					var text = d.message || tpl.error || '';
					if (d.field && fieldError(d.field, text)) { return; }
					msg.textContent = text;
					msg.hidden = false;
				}).catch(function () {
					form.classList.remove('is-sending');
					btn.disabled = false;
					$(btn, '.nxt-btn__t').textContent = btn.getAttribute('data-label');
					msg.textContent = tpl.error || '';
					msg.hidden = false;
				});
			});
		}

		function refreshReviews() {
			var url = window.location.href.split('#')[0];
			url += (url.indexOf('?') > -1 ? '&' : '?') + 'nxt=' + Date.now();
			return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var fresh = doc.getElementById(root.id);
				if (!fresh) { throw new Error('no widget'); }
				var np = fresh.querySelector('.nxt__panel[data-panel="rev"]');
				var op = panel('rev');
				if (np && op) { op.innerHTML = np.innerHTML; }
				var nc = fresh.querySelector('.nxt__count');
				var oc = $(root, '.nxt__count');
				if (nc && oc) { oc.textContent = nc.textContent; }
				state = { sort: 'newest', rating: 0, page: 1 };
				moveInk();
			});
		}

		function success(form, d) {
			if (d && d.id && d.ekey) { setKey(d.id, d.ekey); }
			var wrap = form.parentNode;
			var done = $(wrap, '.nxt-done');
			form.hidden = true;
			var intro = $(wrap, '.nxt-form__intro'); if (intro) { intro.hidden = true; }
			var title = $(wrap, '.nxt-form__title'); if (title) { title.hidden = true; }
			$(done, '.nxt-done__text').textContent = d.message || '';
			done.hidden = false;
			var viewBtn = $(done, '[data-nxt-goto]');
			if (viewBtn) { viewBtn.hidden = !d.approved; }
			if (d.approved) { refreshReviews().catch(function () { /* the list updates on the next visit */ }); }
			if (modal) { var dlg = $(modal, '.nxt-modal__dlg'); if (dlg) { dlg.scrollTo({ top: 0, behavior: 'smooth' }); } } else {
				var top = root.getBoundingClientRect().top;
				if (top < 0) { root.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
			}
		}

		markOwn();
		initForm();
	}

	function initAll(scope) {
		$$(scope || document, '.nxt[data-nxt]').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) { return; }
		window.elementorFrontend.hooks.addAction('frontend/element_ready/nexvio_product_tabs.default', function ($scope) {
			if ($scope && $scope[0]) {
				initAll($scope[0]); /* a widget that is already set up is skipped: listeners must not be added twice */
			}
		});
	});
})();
