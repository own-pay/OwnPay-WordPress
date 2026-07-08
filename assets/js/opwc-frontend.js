jQuery(function ($) {
	// Classic checkout support
	$("form.checkout, form#order_review").on(
		"change",
		'input[name="payment_method"]',
		function () {
			$(document.body).trigger("update_checkout");
		}
	);

	// WooCommerce Blocks checkout support
	if ( window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function' ) {
		let currentPaymentMethod = null;
		window.wp.data.subscribe(function() {
			const select = window.wp.data.select;
			const checkoutStore = select( 'wc/store/checkout' );
			if ( checkoutStore && typeof checkoutStore.getSelectedPaymentMethod === 'function' ) {
				const paymentMethod = checkoutStore.getSelectedPaymentMethod();
				if ( paymentMethod !== currentPaymentMethod ) {
					currentPaymentMethod = paymentMethod;
					
					// Force a refresh via Store API by invalidating cart store cache
					const cartStoreKey = window.wc && window.wc.wcBlocksData ? window.wc.wcBlocksData.CART_STORE_KEY : 'wc/store/cart';
					const dispatch = window.wp.data.dispatch;
					if ( dispatch && typeof dispatch === 'function' && dispatch( cartStoreKey ) && typeof dispatch( cartStoreKey ).invalidateResolution === 'function' ) {
						dispatch( cartStoreKey ).invalidateResolution( 'getCartData' );
					}
				}
			}
		});
	}
});
