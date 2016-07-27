<?php
defined('ABSPATH') or die("No script kiddies please!");
/**
 * Adds AccessPress Twitter Feed Widget
 */
class APTF_Slider_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
                'aptf_slider_widget', // Base ID
                __('AccessPress Tweets Slider', 'accesspress-twitter-feed'), // Name
                array('description' => __('AccessPress Tweets Slider Widget', 'accesspress-twitter-feed')) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance) {

        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        $controls = isset($instance['controls'])?$instance['controls']:false;
        $slide_duration = (isset($instance['slide_duration'])&& $instance['slide_duration']!='')?$instance['slide_duration']:'1500';
        $auto_slide = isset($instance['auto_slide'])?$instance['auto_slide']:false;
        $template = isset($instance['template'])?$instance['template']:'template-1';
        $follow_button = (isset($instance['follow_button']) && $instance['follow_button']==1)?'true':'false';
        echo do_shortcode('[ap-twitter-feed-slider auto_slide="'.$auto_slide.'" controls="'.$controls.'" slide_duration="'.$slide_duration.'" follow_button="'.$follow_button.'" template="'.$template.'"]');
      echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance) {
        $title = isset($instance['title'])?$instance['title']:'';
        $controls = (isset($instance['controls']))?$instance['controls']:0;
        $slide_duration = (isset($instance['slide_duration']))?$instance['slide_duration']:'';
        $auto_slide = (isset($instance['auto_slide']))?$instance['auto_slide']:0;
        $template = isset($instance['template'])?$instance['template']:'template-1';
        $follow_button = isset($instance['follow_button'])?$instance['follow_button']:0;
        
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'accesspress-twitter-feed'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('controls'); ?>"><?php _e('Slider Controls:', 'accesspress-twitter-feed'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('controls'); ?>" name="<?php echo $this->get_field_name('controls'); ?>" type="checkbox" value="1" <?php checked($controls,true);?>/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('slide_duration'); ?>"><?php _e('Slide Duration:', 'accesspress-twitter-feed'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('slide_duration'); ?>" name="<?php echo $this->get_field_name('slide_duration'); ?>" type="text" placeholder="e.g: 1000" value="<?php echo $slide_duration;?>"/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('auto_slide'); ?>"><?php _e('Auto Slide:', 'accesspress-twitter-feed'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('auto_slide'); ?>" name="<?php echo $this->get_field_name('auto_slide'); ?>" type="checkbox" value="1" <?php checked($auto_slide,true);?>/>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('template'); ?>"><?php _e('Template:', 'accesspress-twitter-feed'); ?></label> 
            <select class="widefat" id="<?php echo $this->get_field_id('template'); ?>" name="<?php echo $this->get_field_name('template'); ?>" >
                <?php for($i=1;$i<=3;$i++){
                    ?>
                    <option value="template-<?php echo $i;?>" <?php selected($template,'template-'.$i);?>>Template <?php echo $i;?></option>
                    <?php
                }?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('follow_button'); ?>"><?php _e('Display Follow Button:', 'accesspress-twitter-feed'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('follow_button'); ?>" name="<?php echo $this->get_field_name('follow_button'); ?>" type="checkbox" value="1" <?php checked($follow_button,true);?>/>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance) {
//        echo "<pre>";
//        die(print_r($new_instance,true));
        $instance = array();
        $instance['title'] = isset($new_instance['title'])?strip_tags($new_instance['title']):'';
        $instance['slide_duration'] = isset($new_instance['slide_duration'])?sanitize_text_field($new_instance['slide_duration']):'';
        $instance['template'] = isset($new_instance['template'])?$new_instance['template']:'';
        $instance['controls'] = isset($new_instance['controls'])?$new_instance['controls']:0;
        $instance['auto_slide'] = isset($new_instance['auto_slide'])?$new_instance['auto_slide']:0;
        $instance['follow_button'] = isset($new_instance['follow_button'])?$new_instance['follow_button']:0;
        
        return $instance;
    }

}

// class APS_PRO_Widget
?>