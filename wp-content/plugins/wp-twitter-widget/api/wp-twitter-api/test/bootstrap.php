<?php
/**
 * Unit test bootstrapper.
 * This is nothing close to an accurate simulation of WordPress environment, it's just for testing utils.
 */
 
function is_admin(){
    return false;
} 

function esc_html( $text ){
    return htmlspecialchars( $text, ENT_COMPAT, 'UTF-8' );
}

function esc_attr( $text ){
    return esc_html( $text );
}

require __DIR__.'/../twitter-api.php';

twitter_api_include('utils','core','unicode');
