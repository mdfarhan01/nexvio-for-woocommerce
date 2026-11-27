/* Extra Options tab of the product screen: Enter inside the builder must not save the product */
(function ($) {
	'use strict';
	$(document).on('keydown', '#nexvio_epo_data input:not([type=button]):not([type=submit]):not(textarea)', function (e) {
		if (13 === e.which) {
			e.preventDefault();
		}
	});
})(jQuery);

/* Safety net: if the browser ever scrolls a box around the tab (focus, scrollIntoView), put it back to the top */
(function () {
	'use strict';
	document.addEventListener('scroll', function (e) {
		var el = e.target, panel = document.getElementById('nexvio_epo_data');
		if (panel && el && el.nodeType === 1 && el.scrollTop > 0 && el.contains(panel) && el !== document.body && el !== document.documentElement) {
			el.scrollTop = 0;
		}
	}, true);
})();
