$ = new jQuery.noConflict();
$(document).ready(function(){
	$("#mdc_msg_form").submit(function(e){
		var from	= $("#mdc_msg_sndr").val();
		var to		= $("#mdc_msg_rcvr").val();
		var subject	= $("#mdc_msg_subj").val();
		var message	= $("#mdcmsgbody").val();
		if(message == ''){
			var message = '';
			var django = $("#mdcmsgbody_ifr").contents().find("#tinymce p");
			$(django).each(function(){
				message = message + "<br />" + $(this).html();
			});
		}
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: {'action' : 'mdc_send_msg', 'from' : from, 'to' : to, 'subject' : subject, 'message' : message},
			dataType: "JSON",
			success: function(data){
				if (data.status == 'Sent') {
					$('#mdc_msg_form')[0].reset();
					$(".mdc_msg_form_div .msg_success").show();
					$(".mdc_msg_form_div .msg_success").fadeOut(8000);
				}
				else if (data.status == 'Failed') {
					$(".mdc_msg_form_div .msg_error").show();
					$(".mdc_msg_form_div .msg_error").fadeOut(8000);
				}
			}
		})
		e.preventDefault();
	})

	$('.mdc_msg_table').dataTable({
        'order': [[ 3, 'asc' ]]
    });

	$(".msg_delete_rcvr").click(function(){
		var par = $(this).parent().parent();
		var msg_id = $(par).attr("msg_id");
		// alert(msg_id);
		if (confirm('Are you sure you want to delete this?')) {
			$.ajax({
				url: ajaxurl,
				data: {'action' : 'mdc_delete_msg_rcvr', 'msg_id' : msg_id},
				type: "POST",
				success: function(data){
					alert(data);
					par.hide();
				}
			})
		}
	})

	$(".msg_delete_sndr").click(function(){
		var par = $(this).parent().parent();
		var msg_id = $(par).attr("msg_id");
		// alert(msg_id);
		if (confirm('Are you sure you want to delete this?')) {
			$.ajax({
				url: ajaxurl,
				data: {'action' : 'mdc_delete_msg_sndr', 'msg_id' : msg_id},
				type: "POST",
				success: function(data){
					alert(data);
					par.hide();
				}
			})
		}
	})

})