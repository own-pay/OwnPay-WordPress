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

			var currentUrl = window.location.href;

			if (currentUrl.indexOf("?") !== -1) {
				currentUrl +=
					"&key=" + filterData["key"] + "&value=" + filterData["value"];
			} else {
				currentUrl +=
					"?key=" + filterData["key"] + "&value=" + filterData["value"];
			}

			window.location.href = currentUrl;
		});
	}

	function getFilterKeyValue() {
		let filterData = [];

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
