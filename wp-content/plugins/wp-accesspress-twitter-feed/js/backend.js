(function ($) {

  function aptf_update_twitter_auth(arg) {
    $("input[name='consumer_key']").prop('disabled', arg);
    $("input[name='consumer_secret']").prop('disabled', arg);
    $("input[name='access_token']").prop('disabled', arg);
    $("input[name='access_token_secret']").prop('disabled', arg);
  }

  $(function () {
   //All the backend js for the plugin 
     
     /*
     Settings Tabs Switching 
     */
      $('.aptf-tabs-trigger').click(function(){
        $('.aptf-tabs-trigger').removeClass('aptf-active-trigger');
        $(this).addClass('aptf-active-trigger');
        var attr_id = $(this).attr('id');
        var arr_id = attr_id.split('-');
        var id = arr_id[1];
        $('.aptf-single-board-wrapper').hide();
        $('#aptf-'+id+'-board').show();
      });

      if($("input[name='loklak_api']").prop('checked')){
        aptf_update_twitter_auth(true);
      }

      $("input[name='loklak_api']").live('change', function() {
        if($(this).is(':checked')){
          aptf_update_twitter_auth(true);
        }
        else {
          aptf_update_twitter_auth(false);
        }
      });
          
	});
}(jQuery));