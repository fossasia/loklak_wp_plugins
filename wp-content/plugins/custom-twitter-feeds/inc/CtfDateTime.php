<?php
/**
 * Class CtfDateTime
 *
 * Workaround for PHP 5.2
 */
// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfDateTime extends DateTime
{
    public function setTimestamp( $timestamp )
    {
        $date = getdate( ( int ) $timestamp );
        $this->setDate( $date['year'] , $date['mon'] , $date['mday'] );
        $this->setTime( $date['hours'] , $date['minutes'] , $date['seconds'] );
    }

    public function getTimestamp()
    {
        return $this->format( 'U' );
    }
}
