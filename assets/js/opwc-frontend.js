jQuery(function ($) {
	$("form.checkout, form#order_review").on(
		"change",
		'input[name="payment_method"]',
		function () {
			$(document.body).trigger("update_checkout");
		}
	);
});
