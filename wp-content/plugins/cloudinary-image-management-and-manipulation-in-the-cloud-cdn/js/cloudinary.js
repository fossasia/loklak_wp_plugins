jQuery(function() {  
  var xdmConfig = jQuery('#cloudinary-library-config');
  if (xdmConfig.length == 0) return;

  function close_media_library() {
  jQuery('#cloudinary-library').hide();
    jQuery('#wpcontent').css('margin-left', '165px');
    jQuery('#wpbody').css('height', 'auto').css('overflow', 'visible');
  }        
    
  function add_to_gallery(json) {
    var ajaxurl = xdmConfig.data("ajaxurl");
    var data = {
      action: 'cloudinary_register_image',
      url: json.src,
      width: json.width,
      height: json.height,
      post_id: jQuery('#post_ID').val()
    };
    jQuery.post(ajaxurl, data, function(response) {
      var json = JSON.parse(response);
      if (json.error) {
        alert(json.message);
      } else {
        jQuery('.cloudinary_message').html('Image successfully added to gallery');
      }
    });    
  }
  
  function insert_into_post(json) {
  	var src = json.src;  	
  	var href = json.href;  	
  	delete json.message;
  	delete json.src;
  	delete json.href;
  	var image = jQuery('<img/>').attr('src', src);  	
  	if (json.align && json.align != '') {
  		image.addClass('align' + json.align);
  		delete json.align;
  	}
  	jQuery.each(json, function(key, value) {
  		if (value != null && value != "") {
  			image.attr(key, value);
  		}	
  	});
    var ajaxurl = xdmConfig.data("ajaxurl");
    var data = {
      action: 'cloudinary_register_image',
      url: src,
      width: json.width,
      height: json.height,
      post_id: jQuery('#post_ID').val()
    };
    if (tinyMCE && tinyMCE.activeEditor && tinyMCE.activeEditor.selection) {
      var html = tinyMCE.activeEditor.selection.getContent({format : 'html'});
      var match = html.match(/wp-image-(\d+)/);
      if (match) data.attachment_id = match[1];      
    }
    
    jQuery.post(ajaxurl, data, function(response) {
      var json = JSON.parse(response);
      if (json.error) {
        alert(json.message);
        if (data.attachment_id) {
          image.addClass('wp-image-' + data.attachment_id);
        }
      } else {
        image.addClass('wp-image-' + json.attachment_id);
      }
      if (href && href != '') {
        image = jQuery('<a/>').attr('href', href).append(image);
      }
      if (tinyMCE && tinyMCE.activeEditor && tinyMCE.activeEditor.selection) {
        tinyMCE.activeEditor.selection.setContent(jQuery('<div/>').append(image).html()); 
      } else {
        send_to_editor(jQuery('<div/>').append(image).html());  
      }        
    });
  } 

  function update_window_dimensions() {  	
  	var footer = jQuery('#footer').size() > 0 ? jQuery('#footer') : jQuery('#wpfooter');
  	var body_height = jQuery('body').height() - jQuery(footer).outerHeight(true);
  	jQuery('#wpcontent').css('margin-left', '156px');
  	jQuery('#wpbody').css('height', body_height).css('overflow', 'hidden');
  	jQuery('#cloudinary-library, #cloudinary-library iframe').css('height', body_height);              	
  }
  
  var controller = {
    socket: new easyXDM.Socket({
      name: xdmConfig.data("base") + "/easyXDM.name.html",
      swf: xdmConfig.data("base") + "/easyxdm.swf",
      remote: xdmConfig.data("remote"),      
      remoteHelper: xdmConfig.data("remotehelper"),
      container: "cloudinary-library",
      props: {style: {width: "100%", height: "80%"}},
      onMessage: function(message, origin){
        var json = JSON.parse(message);
        switch (json.message) {
        case "insert_into_post":
          close_media_library();
	  	    insert_into_post(json);
	  	    break;
        case "add_to_gallary":
        case "add_to_gallery":
          close_media_library();
          add_to_gallery(json);
          break;        	
        case "done": 
          close_media_library();
          break;
        }        
      },
      onReady: function() {
        controller.resizeWatcher();
      }
    }),
    currentWidth: 0,
    currentHeight: 0,
    resizeWatcher: function() {      
      jQuery(window).resize(update_window_dimensions);
    }
  };  

  function register_edit_image() {
    var buttons = jQuery('#wp_editbtns');    
    if (buttons.length > 0) {
      var img = jQuery('<img />').attr({src: xdmConfig.data("base")+"/images/edit_icon.png" , id: "cld_editbtn", width: "24", height: "24", title: "Cloudinary Edit Image"}).appendTo(buttons);
      img.mousedown(function() {                             	
      	var html = tinyMCE.activeEditor.selection.getContent({format : 'html'});
      	jQuery(this).parents('#wp_editbtns').hide();
      		controller.socket.postMessage(JSON.stringify({
          message: "edit_image",
          html: html
        }));                
        update_window_dimensions();
        jQuery('#cloudinary-library').show();        
      });
    } else {
      setTimeout(register_edit_image, 10);  
    }
  }
  if (typeof(tinyMCE) != 'undefined')
    register_edit_image();
  
  jQuery('.cloudinary_add_media').click(function() {
    jQuery('.cloudinary_message').html('');
  	update_window_dimensions();
  	jQuery('#cloudinary-library').show();
    return false;
  });
  
  var div = jQuery('<div id="cloudinary-library"></div>').hide().appendTo(jQuery('#wpbody-content'));
  if (xdmConfig.data("autoshow")) {
  	div.show();
  	setTimeout(function() { update_window_dimensions()}, 1);
  } 
});
