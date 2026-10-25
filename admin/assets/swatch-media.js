/* Nexvio - swatch image picker on attribute term screens (WooCommerce > Products > Attributes > terms). */
/* Source: https://github.com/mdfarhan01/nexvio-for-woocommerce (this file is not minified; edit it directly). */
jQuery(function ($) {
	var frame;
	$(document).on('click', '.nexvio-swimg-pick', function (e) {
		e.preventDefault();
		var w = $(this).closest('.nexvio-swimg');
		frame = wp.media({ title: w.data('title'), multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
			w.find('input').val(a.id);
			w.find('.nexvio-swimg-preview')
				.empty()
				.append(
					$('<img alt="">')
						.attr('src', url)
						.css({ width: 48, height: 48, borderRadius: '50%', objectFit: 'cover' })
				);
			w.find('.nexvio-swimg-remove').show();
		});
		frame.open();
	});
	$(document).on('click', '.nexvio-swimg-remove', function (e) {
		e.preventDefault();
		var w = $(this).closest('.nexvio-swimg');
		w.find('input').val('');
		w.find('.nexvio-swimg-preview').empty();
		$(this).hide();
	});
	$(document).ajaxComplete(function (ev, x, o) {
		if (o && o.data && String(o.data).indexOf('action=add-tag') > -1) {
			$('.nexvio-swimg input').val('');
			$('.nexvio-swimg-preview').empty();
			$('.nexvio-swimg-remove').hide();
		}
	});
});
