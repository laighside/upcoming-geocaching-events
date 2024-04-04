<?php
/**
 * Functions that create the HTML for the public events page
 * Implemented as a Wordpress shortcode [gc-events-page]
 */

/**
 * Sorts an array of events by date
 */
function sort_events_by_date(&$events) {
    foreach($events as $item) {
        $item->unix_time = strtotime($item->event_date);
    }
    $time_column = array_column($events, 'unix_time');
    array_multisort($time_column, SORT_ASC, $events);
}

/**
 * Gets the URL of the event type icon
 */
function get_event_icon($event_type) {
    switch( $event_type ) {
    case 'Event':
        return plugins_url( 'img/event.gif', __FILE__ );
    case 'CITO':
        return plugins_url( 'img/cito.gif', __FILE__ );
    case 'Mega':
        return plugins_url( 'img/mega.gif', __FILE__ );
    case 'CCE':
        return plugins_url( 'img/cce.gif', __FILE__ );
    case 'Block':
        return plugins_url( 'img/block.gif', __FILE__ );
    default:
        return "";
    }
}

/**
 * Gets the full name of the event type
 */
function get_event_full_type($event_type) {
    switch( $event_type ) {
    case 'Event':
        return "Event";
    case 'CITO':
        return "Cache In Trash Out";
    case 'Mega':
        return "Mega Event";
    case 'CCE':
        return "Community Celebration Event";
    case 'Block':
        return "Block Party";
    default:
        return "";
    }
}

function get_events_json($options) {
    if (strlen($options['gc_events_feed_url']) > 0) {
        $url_base = $options['gc_events_feed_url'];
        $url = $url_base . (str_contains($url_base, '?') ? '&' : '?') . 'state=' . $options['gc_events_state'];
        $body = wp_remote_retrieve_body(wp_remote_get($url));
        $event_data = json_decode($body);
        return $event_data;
    }
    return null;
}

function print_event_icon_cell($event) {
    echo "<td><img src=\"" . get_event_icon($event->event_type) . "\" alt=\"" . esc_attr(get_event_full_type($event->event_type)) . "\"/></td>";
}

function print_event_name_cell($event, $options, $is_own_event = false, $show_other_name = false) {
    $logo_url = $options['gc_events_owner_logo_url'];
    $archivedHtml = " <span style=\"font-weight:bold;color:red;\">(Archived)</span>";
    $placedByHtml = $event->placed_by ? ("<br /><span style=\"font-size:12px;\">by " . esc_html($event->placed_by) . "</span>") : "";
    if ($is_own_event && $event->placed_by && strlen($logo_url) > 0) {
        $img_element = "<img src=\"" . esc_url($logo_url) . "\" alt=\"" . esc_attr($event->placed_by) . "\" style=\"height:20px;\">";
        $placedByHtml = "<br /><span style=\"font-size:12px;\">by " . $img_element . "</span>";
    }
    $isArchived = ($event->event_status == "A");
    $nameHtml = "<a href='". esc_url("https://coord.info/" . $event->gc_code) . "'" . ($isArchived ? " style=\"color:red;\"" : "") . ">" . esc_html($event->gc_code) . " - " . esc_html($event->event_name) . ($isArchived ? $archivedHtml : "") . "</a>";
    $hasOtherName = isset($event->other_name) && (strlen($event->other_name) > 0);
    $otherNameHtml = $hasOtherName ? "<span style=\"font-size:12px;\">" . esc_html($event->other_name) . "</span><br />" : "";
    echo "<td>";
    if ($show_other_name && $hasOtherName) {
        echo $otherNameHtml . $nameHtml;
    } else {
        echo $nameHtml . $placedByHtml;
    }
    echo "</td>";
}

function print_event_date_cell($event, $local_state_name) {
    $date = date_create($event->event_date);
    $date_str = ($event->event_date > 0) ? date_format($date,"jS M Y") : "";
    $has_state = isset($event->osm_state);
    $has_town = isset($event->osm_town);
    $town_name = "";
    if ($has_town) {
        $town_name = $event->osm_town;
        if ($has_state && $event->osm_state != $local_state_name) {
            $town_name = $event->osm_town . ", " . $event->osm_state;
        }
    } else {
        if ($has_state && $event->osm_state != $local_state_name) {
            $town_name = $event->osm_state;
        }
    }
    echo "<td style=\"text-align:right;\">";
    echo esc_html($date_str) . ((strlen($town_name) > 0) ? ("<br /><span style=\"font-size:12px;\">" . esc_html($town_name) . "</span>") : "");
    echo "</td>";
}

/**
 * The shortcode function for [gc-events-page]
 */
function gc_events_page() {
    wp_enqueue_style('gc-events');

    $options = get_option('gc_events_settings');
    $local_events = [];
    $mega_events = [];
    $other_events = [];
    $event_data = get_events_json($options);
    if ($event_data) {
        $local_events = $event_data->events;
        $mega_events = $event_data->megas;
        $other_events = $event_data->others;
    } else {
        echo "<p style=\"color:red;\">Error while fetching event list</p>";
        return;
    }

    // Make sure the event are ordered by date
    sort_events_by_date($local_events);

    // Merge megas and other interstate events all into one table/list
    $national_events = array_merge($mega_events, $other_events);
    sort_events_by_date($national_events);

    $local_state_name = get_state_full_name($options['gc_events_state']);

    // Local events table
    if (count($local_events) > 0) {
        echo "<h4>" . esc_html($local_state_name) . " Events</h4>";
        echo "<table class=\"gc-event-table\">";
        foreach($local_events as $item) {
            $is_own_event = boolval($item->owner_id === intval($options['gc_events_owner_id']));
            echo "<tr class=\"gc-event-table-row" . ($is_own_event ? " gc-event-highlight" : "") . "\">";

            print_event_icon_cell($item);
            print_event_name_cell($item, $options, $is_own_event, false);
            print_event_date_cell($item, $local_state_name);

            echo "</tr>";
            //echo "<tr class=\"gc-event-spacer\"></tr>";
        }
        echo '</table>';
    }

    // National Events table
    if (count($national_events) > 0) {
        echo "<h4>National Events</h4>";
        echo "<table class=\"gc-event-table\">";
        foreach($national_events as $item) {
            echo "<tr class=\"gc-event-table-row\">";

            print_event_icon_cell($item);
            print_event_name_cell($item, $options, false, true);
            print_event_date_cell($item, null);

            echo '</tr>';
        }
        echo '</table>';
    }

    // Planned events table
    global $wpdb;
    $table_name = $wpdb->prefix . "gc_events"; 
    $result = $wpdb->get_results ( "SELECT * FROM $table_name WHERE event_date >= CURDATE();" );
    if (count($result) > 0) {
        sort_events_by_date($result);
        $website_name = get_bloginfo("name");
        $logo_url = $options['gc_events_owner_logo_url'];
        $name_html = (strlen($logo_url) > 0) ? "<img src=\"" . esc_url($logo_url) . "\" alt=\"" . esc_attr($website_name) . "\" style=\"height:32px;\" />" : esc_html($website_name);
        echo "<h4>Planned events by " . $name_html . "</h4>";
        echo "<table>";
        foreach ( $result as $item ) {
            echo "<tr class=\"gc-event-table-row\">";
            if (strlen($item->event_url)) {
                echo "<td><a href=\"" . esc_url($item->event_url) . "\">" . esc_html($item->event_name) . "</a></td>";
            } else {
                echo "<td>" . esc_html($item->event_name) . "</td>";
            }
            echo "<td>" . esc_html($item->location) . "</td>";
            print_event_date_cell($item, null);
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<p style=\"font-style:italic;\">This list is updated once a day. New events may take up to 24hrs to appear on this page.</p>";
}

add_shortcode('gc-events-page', 'gc_events_page');

?>
