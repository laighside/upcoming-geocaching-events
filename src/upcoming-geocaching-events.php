<?php

/*
 * Plugin Name:       Upcoming Geocaching Events
 * Description:       Creates a page with an auto-updating list of upcoming geocaching events.
 * Version:           1.0.0
 * Author:            Laighside
 * Author URI:        https://github.com/laighside/upcoming-geocaching-events
 */

// Include the other files
require_once untrailingslashit(dirname( __FILE__ )) . '/events-page.php';
require_once untrailingslashit(dirname( __FILE__ )) . '/admin-page.php';
require_once untrailingslashit(dirname( __FILE__ )) . '/activation.php';

register_activation_hook( __FILE__, 'gc_events_activated' );

wp_register_style( 'gc-events', plugins_url( 'upcoming-geocaching-events.css', __FILE__ ) );

/**
 * Function to convert state abbreviations to their full name. Used in lots of places.
 */
function get_state_full_name($abbr) {
    $abbr = strtolower($abbr);
    if ($abbr === 'nsw') return 'New South Wales';
    if ($abbr === 'vic') return 'Victoria';
    if ($abbr === 'qld') return 'Queensland';
    if ($abbr === 'sa') return 'South Australia';
    if ($abbr === 'wa') return 'Western Australia';
    if ($abbr === 'nt') return 'Northern Territory';
    if ($abbr === 'tas') return 'Tasmania';
    if ($abbr === 'act') return 'Australian Capital Territory';
    return '';
}

 ?>
