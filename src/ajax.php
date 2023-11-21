<?php

/**
 * AJAX request handler for creating and updating events.
 */
function gc_events_ajax_save() {
    global $wpdb;
    
    if (!check_ajax_referer( 'gc_event', '_ajax_nonce', false )) {
        wp_die('Invalid Request');
    }
    
    if (!isset($_POST['event_id']) ||
        !isset($_POST['event_name']) ||
        !isset($_POST['event_url']) ||
        !isset($_POST['jj']) ||
        !isset($_POST['mm']) ||
        !isset($_POST['aa']) ||
        !isset($_POST['location'])) {
        wp_die('Invalid Request');
    }
    
    // get posted data
    $id = intval(wp_unslash( $_POST['event_id'] ));
    $name = wp_unslash( $_POST['event_name'] );
    $url = wp_unslash( $_POST['event_url'] );
    $jj = intval(wp_unslash( $_POST['jj'] ));
    $mm = intval(wp_unslash( $_POST['mm'] ));
    $aa = intval(wp_unslash( $_POST['aa'] ));
    $location = wp_unslash( $_POST['location'] );
    
    // save it to the database
    $table_name = $wpdb->prefix . "gc_events";
    $result = false;
    
    if ($id < 0) {
        // create new event
        $result = $wpdb->insert($table_name, array( 'event_name' => $name, 'event_url' => $url, 'event_date' => ($aa . '-' . $mm . '-' . $jj), 'location' => $location ) );
        $id = $wpdb->insert_id;
    } else {
        // update existing event
        $query = $wpdb->prepare("UPDATE $table_name SET event_name=%s,event_url=%s,event_date=DATE_ADD(DATE_ADD(MAKEDATE(%d, 1), INTERVAL (%d)-1 MONTH), INTERVAL (%d)-1 DAY),location=%s WHERE id=%d ", array( $name, $url, $aa, $mm, $jj, $location, $id ) );
        $result = $wpdb->query($query);
    }
    
    if (!$result) {
        wp_die('Error updating database');
    }
    
    // Get updated event to return
    $select_query = $wpdb->prepare("SELECT id,event_name,event_url,event_date,location FROM $table_name WHERE id=%d;", array($id) );
    $new_event = $wpdb->get_results($select_query);
    
    if (count($new_event) === 1) {
        $wp_event_table = new GC_Event_Table();
        $wp_event_table->display_rows( $new_event );
    } else {
        wp_die('Unable to create new event');
    }
    wp_die();
}

/**
 * AJAX request handler for deleting events.
 */
function gc_events_ajax_delete() {
    global $wpdb;
    
    if (!check_ajax_referer( 'gc_event', '_ajax_nonce', false )) {
        wp_send_json_error('Invalid Request');
    }
    $id = intval(wp_unslash( $_POST['event_id'] ));
    
    $table_name = $wpdb->prefix . "gc_events";
    $result = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
    
    if ($result) {
        wp_send_json_success( array("message" => "Event deleted", "id" => $id) );
    } else {
        wp_send_json_error("Error deleting event from database");
    }
  
    wp_die();
}

add_action( 'wp_ajax_gc_event_save', 'gc_events_ajax_save' );
add_action( 'wp_ajax_gc_event_delete', 'gc_events_ajax_delete' );

?>
