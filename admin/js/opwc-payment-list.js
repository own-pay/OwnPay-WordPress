(function ($) {
	$(document).ready(function () {
		viewOptionalFilterField();
		initFilterSubmission();
	});
	
	function viewOptionalFilterField() {
		var selectField = $(
			".opwc-admin-page .payment-table-wrapper select#filter_key"
		);
		let selectedValue = selectField.val();

		if (0 < selectedValue.length && "" !== selectedValue) {
			let targetId = selectedValue + "_filter";

			$(selectField).siblings("select").hide();
			$(selectField)
				.siblings("select#" + targetId)
				.show();
		}

		selectField.change(function () {
			let targetId = $(this).val() + "_filter";

			$(this).siblings("select").hide();
			$(this)
				.siblings("select#" + targetId)
				.show();
		});
	}
	
	function initFilterSubmission() {
		$(".opwc-admin-page .payment-table-wrapper button#filter").click(function (
			event
		) {
			event.preventDefault();

			let filterData = getFilterKeyValue();

			if (!filterData["key"] || !filterData["value"]) {
				return;
			}

			// Build a clean URL using URLSearchParams to avoid duplicate params
			var url = new URL(window.location.href);
			var params = url.searchParams;

			// Preserve only the base 'page' param and nonce, strip old filter/paged params
			var cleanParams = new URLSearchParams();
			cleanParams.set("page", params.get("page") || "opwc");

			var nonce = $("#opwc_filter_nonce_field").val();
			if (nonce) {
				cleanParams.set("_opwc_nonce", nonce);
			}

			cleanParams.set("key", filterData["key"]);
			cleanParams.set("value", filterData["value"]);

			url.search = cleanParams.toString();
			window.location.href = url.toString();
		});
	}

	function getFilterKeyValue() {
		let filterData = {};

		filterData["key"] = $(
			".opwc-admin-page .payment-table-wrapper select#filter_key"
		).val();
		filterData["value"] = $(
			".opwc-admin-page .payment-table-wrapper select#" +
				filterData["key"] +
				"_filter"
		).val();

		return filterData;
	}
})(jQuery);
