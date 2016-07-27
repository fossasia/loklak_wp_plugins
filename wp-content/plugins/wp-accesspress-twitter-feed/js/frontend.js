function aptf_popitup(url) {
	newwindow=window.open(url,'name','height=400,width=650');
	if (window.focus) {newwindow.focus()}
	return false;
}

(function ($) {
    $(function () {
	   //All the frontend js for the plugin 
       
       $('.aptf-tweets-slider-wrapper').each(function(){
          var controls = $(this).attr('data-slide-controls');
          var auto = $(this).attr('data-slide-controls');
          var slide_duration = $(this).attr('data-slide-duration');
          $(this).bxSlider({
              auto:auto,
              controls:controls,
              pause:slide_duration,
              pager:false,
              speed:1500,
              adaptiveHeight:true
          });
       });
       
	   
	});//document.ready close
}(jQuery));