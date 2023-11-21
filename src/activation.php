<?php
/**
 * Function for plugin activation
 */

function gc_events_activated() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "gc_events"; 

    $sql = "CREATE TABLE $table_name (
      `id` INT NOT NULL AUTO_INCREMENT,
      `gc_code` varchar(10),
      `event_name` text NOT NULL,
      `event_url` text DEFAULT NULL,
      `event_type` text DEFAULT NULL,
      `event_date` date DEFAULT NULL,
      `location` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}

 ?>
