/* Now Regional Media: preset the enquiry type when a button points at the form. */
(function () {
	var select = document.getElementById('enq-type');
	if (!select) { return; }
	document.querySelectorAll('[data-enquiry]').forEach(function (link) {
		link.addEventListener('click', function () {
			var type = link.getAttribute('data-enquiry');
			if (type && select.querySelector('option[value="' + type + '"]')) {
				select.value = type;
			}
		});
	});
	// Keep the thank-you or error state out of the URL after the first view.
	if (window.history && /[?&]enquiry=/.test(window.location.search)) {
		window.history.replaceState(null, '', window.location.pathname + window.location.hash);
	}
})();
