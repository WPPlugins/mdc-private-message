$ = new jQuery.noConflict();
$(document).ready(function($) {
    // toggle help texts
    $(".mdc_help_icon").click(function(){
        var par = $(this).parent();
        $(".mdc_help", par).slideToggle();
    })

    $("#mdc_msg_enable_frontend").change(function(){
    	if($(this).is(":checked")){
    		$(".enable_frontend_option").show();
    	}
    	else{
    		$(".enable_frontend_option").hide();
    	}
    })
    
    $("#mdc_msg_enable_tinymce").change(function(){
    	if($(this).is(":checked")){
    		$(".enable_teeny").show();
    	}
    	else{
    		$(".enable_teeny").hide();
    	}
    })

    $(".mdc_settings_saved").fadeOut(10000);

});