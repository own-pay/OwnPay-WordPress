/**
 * OwnPay WooCommerce Blocks checkout integration.
 *
 * Registers the OwnPay payment method with the WooCommerce Blocks payment
 * registry so it is fully compatible with the block-based Cart and Checkout
 * blocks. Data is passed from PHP via AbstractPaymentMethodType::get_payment_method_data()
 * and made available here through getSetting('ownpay_data').
 */
( function() {
	'use strict';

	var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
	var getSetting            = window.wc.wcSettings.getSetting;
	var createElement         = window.wp.element.createElement;
	var decodeEntities        = window.wp.htmlEntities.decodeEntities;

	// Data passed from OPWC_Blocks::get_payment_method_data()
	var settings = getSetting( 'ownpay_data', {} );

	var title       = decodeEntities( settings.title       || 'OwnPay Payment' );
	var description = decodeEntities( settings.description || '' );
	var iconUrl     = settings.icon || '';

	/**
	 * Icon component shown next to the payment method label.
	 */
	function OpwcIcon() {
		if ( ! iconUrl ) {
			return null;
		}
		return createElement( 'img', {
			src:   iconUrl,
			alt:   title,
			style: { maxHeight: '24px', marginLeft: '6px', verticalAlign: 'middle' },
		} );
	}

	/**
	 * Label rendered in the payment method list.
	 * Accepts the PaymentMethodLabel component supplied by WC Blocks.
	 */
	function OpwcLabel( props ) {
		var PaymentMethodLabel = props.components && props.components.PaymentMethodLabel;

		if ( PaymentMethodLabel ) {
			return createElement( PaymentMethodLabel, {
				text: title,
				icon: createElement( OpwcIcon, null ),
			} );
		}

		// Fallback for older WC Blocks versions that don't inject components.
		return createElement(
			'span',
			null,
			title,
			createElement( OpwcIcon, null )
		);
	}

	/**
	 * Content rendered below the label when this method is selected.
	 */
	function OpwcContent() {
		if ( ! description ) {
			return null;
		}
		return createElement( 'p', { style: { margin: '8px 0 0' } }, description );
	}

	registerPaymentMethod( {
		name:          'ownpay',
		label:         createElement( OpwcLabel, null ),
		content:       createElement( OpwcContent, null ),
		edit:          createElement( OpwcContent, null ),
		/**
		 * canMakePayment is called on every cart update. We simply confirm the
		 * server-side is_active() check already filtered this method into the
		 * available list, so always return true here.
		 */
		canMakePayment: function() {
			return true;
		},
		ariaLabel: title,
		supports: {
			features: settings.supports || [ 'products' ],
		},
	} );
}() );
