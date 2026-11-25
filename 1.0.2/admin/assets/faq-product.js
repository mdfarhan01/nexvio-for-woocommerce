/* Nexvio: FAQ tab on the product screen. */
(function ($) {
	'use strict';
	var rows = $('#nexvio-faqp-rows');
	if (!rows.length) { return; }
	var tpl = $('#nexvio-faqp-tpl').html();

	function renumber() {
		rows.children('.nexvio-faqp__row').each(function (i) {
			$(this).find('.nexvio-faqp__n').text(i + 1);
			$(this).find('input, textarea').each(function () {
				this.name = this.name.replace(/nexvio_faq_own\[[^\]]*\]/, 'nexvio_faq_own[' + i + ']');
			});
		});
	}
	$('#nexvio-faqp-add').on('click', function () {
		var n = rows.children().length;
		var row = $(tpl.replace(/__i__/g, n));
		rows.append(row);
		renumber();
		row.find('.nexvio-faqp__q').trigger('focus');
	});
	rows.on('click', '[data-act]', function () {
		var row = $(this).closest('.nexvio-faqp__row');
		var act = $(this).attr('data-act');
		if (act === 'del') { row.remove(); }
		if (act === 'up' && row.prev().length) { row.insertBefore(row.prev()); }
		if (act === 'down' && row.next().length) { row.insertAfter(row.next()); }
		renumber();
	});
	renumber();
})(jQuery);
