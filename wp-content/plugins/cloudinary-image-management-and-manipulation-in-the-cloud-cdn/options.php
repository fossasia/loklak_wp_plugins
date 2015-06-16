<?php
  cloudinary_include_assets();
?>
<link href="<?php echo plugins_url('/css', __FILE__) ?>/cloudinary.css" media="screen" rel="stylesheet" type="text/css" />
<script>
jQuery(function() {
  jQuery('#cloudinary_options_form').submit(function() {
  	var form = jQuery(this);
  	jQuery(form).find('input[type=submit]').hide();
  	jQuery(form).find('.submit_progress').show();
    var data = jQuery(form).serializeArray();
    data.push({name: "action", value: 'cloudinary_update_options'});  
    jQuery('.settings-error').hide();
    jQuery.post(ajaxurl, data, function(response) {
	  jQuery(form).find('input[type=submit]').show();
	  jQuery(form).find('.submit_progress').hide();
    	
      if (response == 'success') {
        jQuery("#options-success").show(); 
      } else {
        jQuery("#options-error").html(response).show();
      }
    });
    
    return false;
  });
});

window.onload = function() {
  var arrInputs = document.getElementsByTagName("input");
  for (var i = 0; i < arrInputs.length; i++) {
    var curInput = arrInputs[i];
    var textstring = (curInput.value.length);
    if (textstring == "0" ) {  
      if (!curInput.type || curInput.type == "" || curInput.type == "text") HandlePlaceholder(curInput);
    }
  }
};

function HandlePlaceholder(oTextbox) {
  if (typeof oTextbox.placeholder == "undefined") {
    var curPlaceholder = oTextbox.getAttribute("placeholder");
    if (curPlaceholder && curPlaceholder.length > 0) {
      oTextbox.value = curPlaceholder;
      oTextbox.setAttribute("old_color", oTextbox.style.color);
      oTextbox.style.color = "#c0c0c0";
      oTextbox.onfocus = function() {
        this.style.color = this.getAttribute("old_color");
        if (this.value === curPlaceholder) this.value = "";
      };
      oTextbox.onblur = function() {
        if (this.value === "") {
          this.style.color = "#c0c0c0";
          this.value = curPlaceholder;
        }
      };
    }
  }
}
</script>
<div class="cloudinary_settings">
	<div class="top_part">
	  <div class="a_logo">
	    <a target="_blank" href="http://cloudinary.com/"><div class="logo"></div></a>
	  </div>
	  <h2>Welcome to Cloudinary</h2>
	</div>
	<div class="outer_opt wrap">	 
	  <div class="acc_link">		  
	    <p>Cloudinary supercharges your images! Upload images to the cloud, deliver via a lightning-fast CDN, optimized and using industry best practices. Perform smart resizing, add watermarks, apply effects, and much more without leaving your WordPress console or installing any software.</p>
	    <p>
	    	To start uploading photos to Cloudinary you'll need to sign up for your own private Cloudinary account. 
	    	Sign up is <strong>free</strong> and takes only a few seconds. <a target="_blank"href="https://cloudinary.com/users/register/free"><strong>Sign up now</strong></a>.
	    </p>
	    <p>After you've signed up for your Cloudinary account, please fill in the following details as they appear in your <a target="_blank" href="https://cloudinary.com/console">Cloudinary Dashboard</a>:</p>
	  </div>
	</div>
	<div style="clear: both;">
	  <div class="updated settings-error set_option" style="display:none;"  id="options-success">
	    <p><strong> Congratulations! You can now open Cloudinary's media library to start managing your images.<br/>Use the new "Cloudinary Upload/Insert" option to embed photos into your posts directly from the cloud.</strong></p>
	  </div>
    <div class="updated settings-error set_option" style="display:none;"  id="options-error">      
    </div>
	  <div class="cloudinary_config_in">
	    <form method="post" id="cloudinary_options_form">
        <?php wp_nonce_field('cloudinary_update_options'); ?>
	      <table class="cloudinary_config_tab">
	      <tr>
	          <td class="cloudinary_config_first_td">CLOUDINARY_URL:</td>
	          <td class="cloudinary_config_second_td">
	            <textarea name="cloudinary_url" placeholder="cloudinary://api_key:api_secret@cloud_name"><?php echo get_option('cloudinary_url', ''); ?></textarea>
	          </td>
	        </tr>
	        <tr>
	          <td>&nbsp;</td>
	          <td><p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Settings') ?>" /><span class="submit_progress" style="display:none;">Updating...</span></p></td>
	        </tr>
	      </table>
	    </form>
	  </div>
	</div>
</div>