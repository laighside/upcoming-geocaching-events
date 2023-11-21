<?php
/**
 * Functions for the admin page
 */

require_once untrailingslashit( dirname( __FILE__ ) ) . '/admin-table.php';
require_once untrailingslashit( dirname( __FILE__ ) ) . '/ajax.php';

function gc_events_feed_url_field_render() {
    $options = get_option( 'gc_events_settings' );
    echo "<input type='text' name='gc_events_settings[gc_events_feed_url]' value='" . esc_attr($options['gc_events_feed_url']) ."'>";
}

function gc_events_state_field_render() {
    $options = get_option( 'gc_events_settings' );
    $states = ['nsw', 'vic', 'sa', 'qld', 'wa', 'nt', 'tas', 'act'];
    
    echo '<select name="gc_events_settings[gc_events_state]">';
    foreach ( $states as $state ) {
        echo '<option value="' . $state . '" ' . (($options['gc_events_state'] === $state) ? ' selected': '') . '>';
        echo esc_html(get_state_full_name($state));
        echo '</option>';
    }
    echo '</select>';
}

function gc_events_owner_id_field_render() {
    $options = get_option( 'gc_events_settings' );
    echo "<input type='text' name='gc_events_settings[gc_events_owner_id]' value='" . esc_attr($options['gc_events_owner_id']) . "'>";
}

function gc_events_owner_logo_url_field_render() {
    $options = get_option( 'gc_events_settings' );
    echo "<input type='text' name='gc_events_settings[gc_events_owner_logo_url]' value='" . esc_attr($options['gc_events_owner_logo_url']) . "'>";
}

/**
 * Main function for creating the admin page HTML
 */
function gc_events_admin_page() {
    echo "<h1>Upcoming Geocaching Events</h1>";
    echo "<p>Use the shortcode [gc-events-page] to insert a list of upcoming events into your webpage</p>";

    // Basic settings
    echo "<form action='options.php' method='post'>";
    settings_fields('gc_events');
    do_settings_sections('gc_events');
    submit_button();
    echo "</form>";

    // Planned events table
    $add_new_button = '<button type="button" data-action="add-event" class="add-event-button page-title-action" aria-expanded="false" aria-label="Creat new event">Add New</button>';
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Planned Events</h1>';
    echo $add_new_button;

    $wp_event_table = new GC_Event_Table();
    $wp_event_table->prepare_items();
    $wp_event_table->display();
    $wp_event_table->inline_edit();

    echo $add_new_button;
    echo '</div>';
}

function gc_events_settings_section_callback() {
    // Do nothing
}

/**
 * Add the "GC Events" page to the admin menu
 */
function gc_events_admin_menu() {
    add_menu_page(
        'Upcoming Geocaching Events',
        'GC Events',
        'edit_pages',
        'gc-events',
        'gc_events_admin_page'
    );
}

add_action('admin_menu', 'gc_events_admin_menu');

function gc_events_admin_init() {
    register_setting( 'gc_events', 'gc_events_settings' );
    add_settings_section(
        'gc_events_settings_section',
        'Settings',
        'gc_events_settings_section_callback',
        'gc_events'
    );

    add_settings_field(
        'gc_events_feed_url_field',
        'Event Feed URL',
        'gc_events_feed_url_field_render',
        'gc_events',
        'gc_events_settings_section'
    );

    add_settings_field(
        'gc_events_state_field',
        'State',
        'gc_events_state_field_render',
        'gc_events',
        'gc_events_settings_section'
    );

    add_settings_field(
        'gc_events_owner_id_field',
        'Your Owner ID',
        'gc_events_owner_id_field_render',
        'gc_events',
        'gc_events_settings_section'
    );

    add_settings_field(
        'gc_events_owner_logo_url_field',
        'Your Logo URL',
        'gc_events_owner_logo_url_field_render',
        'gc_events',
        'gc_events_settings_section'
    );
}

add_action('admin_init', 'gc_events_admin_init');

/**
 * Load JS for AJAX requests
 */
function gc_events_enqueue_scripts() {
    wp_enqueue_script(
        'gc_events_inline_edit',
        plugins_url( 'inline-edit-event.js', __FILE__ ),
        array( 'jquery' ),
        '1.0.0',
        true
    );

    wp_localize_script(
        'gc_events_inline_edit',
        'gc_ajax_obj',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'gc_event' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'gc_events_enqueue_scripts' );

?>
