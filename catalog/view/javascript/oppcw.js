(function ($) {
	
	var attachEventHandlers = function() {
		if (typeof oppcw_ajax_submit_callback != 'undefined') {
			$('.oppcw-confirmation-buttons input').each(function () {
				$(this).click(function() {
					OPPCwHandleAjaxSubmit();
				});
			});
		}
	};
	
	var getFieldsDataArray = function () {
		var fields = {};
		
		var data = $('#oppcw-confirmation-ajax-authorization-form').serializeArray();
		$(data).each(function(index, value) {
			fields[value.name] = value.value;
		});
		
		return fields;
	};
	
	var OPPCwHandleAjaxSubmit = function() {
		
		if (typeof cwValidateFields != 'undefined') {
			cwValidateFields(OPPCwHandleAjaxSubmitValidationSuccess, function(errors, valid){alert(errors[Object.keys(errors)[0]]);});
			return false;
		}
		OPPCwHandleAjaxSubmitValidationSuccess(new Array());
		
	};
	
	var OPPCwHandleAjaxSubmitValidationSuccess = function(valid) {
		
		if (typeof oppcw_ajax_submit_callback != 'undefined') {
			oppcw_ajax_submit_callback(getFieldsDataArray());

		}
		else {
			alert("No JavaScript callback function defined.");
		}
	}
		
	$( document ).ready(function() {
		attachEventHandlers();
		
		$('#oppcw_alias').change(function() {
			$('#oppcw-checkout-form-pane').css({
				opacity: 0.5,
			});
			$.ajax({
				type: 		'POST',
				url: 		'index.php?route=checkout/checkout/connect',
				data: 		'oppcw_alias=' + $('#oppcw_alias').val(),
				success: 	function( response ) {
					var htmlCode = '';
					try {
						var jsonObject = jQuery.parseJSON(response);
						htmlCode = jsonObject.output;
					}
					catch (err){
						htmlCode = response;
					}
					console.log(htmlCode);
					var newPane = $("#oppcw-checkout-form-pane", $(htmlCode));
					if (newPane.length > 0) {
						var newContent = newPane.html();
						$('#oppcw-checkout-form-pane').html(newContent);
						attachEventHandlers();
					}
					
					$('#oppcw-checkout-form-pane').animate({
						opacity : 1,
						duration: 100, 
					});
				},
			});
		});
		
	});
	
}(jQuery));