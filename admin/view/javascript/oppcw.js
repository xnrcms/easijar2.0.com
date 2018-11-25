(function($) {
	
	
	var disableElements = function() {

		// Enable all
		$('.oppcw-control-box').each(function () {
			$(this).find('input').prop('disabled', false);
			$(this).find('select').prop('disabled', false);
			$(this).find('textarea').prop('disabled', false);
		});
		
		// Disable selected
		$('.oppcw-use-default .oppcw-control-box').each(function () {
			$(this).find('input').prop('disabled', true);
			$(this).find('select').prop('disabled', true);
			$(this).find('textarea').prop('disabled', true);
		});
	};

	$(document).ready(function() {
		$(".oppcw-default-box input").click(function() {
			$(this).parents(".control-box-wrapper").toggleClass('oppcw-use-default');
			disableElements();
		});
		disableElements();
	});

})(jQuery);
