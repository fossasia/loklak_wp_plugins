/*
 Script to cycle the rotating tweets
*/

jQuery(document).ready(function() {
	function rotatingtweets_runselect() {
		// Script to show or hide display options on start up
		jQuery('input.rtw_ad_official:checked').each(function() {
			var response = jQuery(this).attr('value');	
			if( response == 0) {
				jQuery(this).parent().find('.rtw_ad_tw_det').show('fast');
			} else {
				jQuery(this).parent().find('.rtw_ad_tw_det').hide('fast');
			}
		});
		// Script to show or hide display options when box is checked
		jQuery('.rtw_ad_official').change(function() {
			var response = jQuery(this).attr('value');
			if( response == 0) {
				jQuery(this).parent().find('.rtw_ad_tw_det').show('fast');
			} else {
				jQuery(this).parent().find('.rtw_ad_tw_det').hide('fast');			
			}
		});
		// Script to show or hide display options on start up
		jQuery('input.rtw_ad_type:checked').each(function() {
			var response = jQuery(this).attr('value');
			if( response == 3) {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_list_tag').show('fast');
			} else {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_list_tag').hide('fast');
			}
		});
		// Script to show or hide display options when box is checked
		jQuery('.rtw_ad_type').change(function() {
			var response = jQuery(this).attr('value');
			if( response == 3) {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_list_tag').show('fast');
			} else {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_list_tag').hide('fast');			
			}
			if( response == 2) {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_search').show();
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_not_search').hide();			
	//			jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_sf').hide('fast');
			} else {
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_search').hide();
				jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_not_search').show();			
	//			jQuery(this).closest('.widget-content,.panel-dialog').find('.rtw_ad_sf').show('fast');
			}
		});
	}
	rotatingtweets_runselect();
	
	jQuery(document).on('widget-updated widget-added', function(){ 
		rotatingtweets_runselect();		
	});
	jQuery(document).on('panelsopen', function() {
		rotatingtweets_runselect();		
	});
});
