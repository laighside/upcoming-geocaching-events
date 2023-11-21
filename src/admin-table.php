<?php
/**
 * GC_Event_Table class. Used to create the table of planned events in the admin page.
 * Extends the WP_List_Table class.
 */

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class GC_Event_Table extends WP_List_Table {

   /**
    * Constructor
    */
    function __construct() {
        parent::__construct( array(
            'singular'=> "wp_list_gc_event",
            'plural' => "wp_list_gc_events",
            'ajax'   => true
        ));
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $columns = array(
            'event_name'=>'Name',
            'event_url'=>'URL',
            'event_date'=>'Date',
            'location'=>'Location'
        );
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'event_name' => array('event_name', false),
            'event_date' => array('event_date', false),
        );
        return $sortable_columns;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'event_name':
            case 'event_url':
            case 'event_date':
            case 'location':
                return $item->$column_name;
            default:
                return print_r( $item, true ) ; // Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . "gc_events"; 
        $query = "SELECT id,event_name,event_url,event_date,location FROM $table_name";

        // Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? $wpdb->_real_escape($_GET["orderby"]) : 'ASC';
        $order = !empty($_GET["order"]) ? $wpdb->_real_escape($_GET["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $wpdb->get_results($query);
    }

    public function get_event( $id ) {
        foreach ( $this->items as $event ) {
            if ($event->id === $id) {
                return $event;
            }
        }
    }

    public function display_rows( $events = array(), $level = 0 ) {
        global $wp_query, $per_page;

        if ( empty( $events ) ) {
            $events = $this->items;
        }

        foreach ( $events as $event ) {
            $this->single_row( $event );
        }
    }

    public function single_row( $event ) {
        echo "<tr id=\"event_" . $event->id . "\" class=\"event-tr\">";
        $this->single_row_columns( $event );
        $this->get_inline_data( $event );
        echo "</tr>";
    }

    function handle_row_actions( $item, $column_name, $primary ) : string {

        if ( $primary !== $column_name) {
            return '';
        }

        $actions = [
            'edit'      => '',
            'delete'    => '',
        ];

        $format = '<button type="button" data-event-id="%d" data-action="%s" class="button-link %s-inline" aria-expanded="false" aria-label="%s">%s</button>';

        $actions['edit'] = sprintf(
            $format,
            esc_attr( $item->id ),
            'edit',
            'edit',
            esc_attr__( 'Edit this event' ),
            esc_html__( 'Edit' )
        );

        $actions['delete'] = sprintf(
            $format,
            esc_attr( $item->id ),
            'delete',
            'delete',
            esc_attr__( 'Delete this event' ),
            esc_html__( 'Delete' )
        );

        $always_visible = 'excerpt' === get_user_setting( 'posts_list_mode', 'list' );

        $output = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

        $i = 0;
        foreach ( array_filter( $actions ) as $action => $link ) {
            ++$i;

            if ( 1 === $i ) {
                $sep = '';
            } else {
                $sep = ' | ';
            }

            $output .= "<span class='$action'>$sep$link</span>";
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Adds hidden fields with the data for use in the inline editor for posts and pages.
     */
    function get_inline_data( $event ) {
        $name = esc_textarea( trim( $event->event_name ) );

	echo '<td class="hidden" id="inline_' . $event->id . '">';
	echo '<div class="event_name">' . $name . '</div>';
	echo '<div class="event_url">' . esc_html($event->event_url) . '</div>';
	echo '<div class="location">' . esc_html($event->location) . '</div>';
	echo '<div class="jj">' . mysql2date( 'd', $event->event_date, false ) . '</div>';
	echo '<div class="mm">' . mysql2date( 'm', $event->event_date, false ) . '</div>';
	echo '<div class="aa">' . mysql2date( 'Y', $event->event_date, false ) . '</div>';
	echo '</td>';
    }

    /**
     * Outputs date selector (day, month, year boxes)
     */
    function date_field() {
        global $wp_locale;
        
        $jj = current_time( 'd' );
        $mm = current_time( 'm' );
        $aa = current_time( 'Y' );
        
        $month = '<label><span class="screen-reader-text">' . __( 'Month' ) . '</span><select class="form-required" name="mm"' . ">\n";
        for ( $i = 1; $i < 13; $i = $i + 1 ) {
            $monthnum  = zeroise( $i, 2 );
            $monthtext = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
            $month    .= '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected( $monthnum, $mm, false ) . '>';
            /* translators: 1: Month number (01, 02, etc.), 2: Month abbreviation. */
            $month .= sprintf( __( '%1$s-%2$s' ), $monthnum, $monthtext ) . "</option>\n";
        }
        $month .= '</select></label>';
        
        $day = '<label><span class="screen-reader-text">' . __( 'Day' ) . '</span><input type="text" name="jj" value="' . $jj . '" size="2" maxlength="2" autocomplete="off" class="form-required" /></label>';
        $year = '<label><span class="screen-reader-text">' . __( 'Year' ) . '</span><input type="text" name="aa" value="' . $aa . '" size="4" maxlength="4" autocomplete="off" class="form-required" /></label>';
        
        echo '<div class="timestamp-wrap" style="margin-left:10em;">';
        /* translators: 1: Month, 2: Day, 3: Year */
        printf( __( '%1$s %2$s %3$s' ), $day, $month, $year );
        echo '</div>';
    }

    /**
     * Outputs the hidden row displayed when inline editing
     */
    public function inline_edit() {
        ?>
        <form method="get">
            <table style="display: none"><tbody id="inlineedit">
                <tr id="inline-edit" class="inline-edit-row quick-edit-row" style="display: none">
                    <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                        <div class="inline-edit-wrapper" role="region" aria-labelledby="quick-edit-legend">
                            <fieldset class="inline-edit-col-left">
                                <legend class="inline-edit-legend" id="quick-edit-legend">Quick Edit</legend>
                                <div class="inline-edit-col">
                                    <label>
                                        <span class="title" style="width:10em;">Name</span>
                                        <span class="input-text-wrap" style="margin-left:10em;">
                                            <input type="text" name="event_name" class="ptitle" value="" />
                                        </span>
                                    </label>
                                    <label>
                                        <span class="title" style="width:10em;">URL&nbsp;(optional)</span>
                                        <span class="input-text-wrap" style="margin-left:10em;">
                                            <input type="text" name="event_url" class="ptitle" value="" />
                                        </span>
                                    </label>
                                    <fieldset class="inline-edit-date">
                                        <legend><span class="title" style="width:10em;">Date</span></legend>
                                        <?php $this->date_field(); ?>
                                    </fieldset>
                                    <label>
                                        <span class="title" style="width:10em;">Location</span>
                                        <span class="input-text-wrap" style="margin-left:10em;">
                                            <input type="text" name="location" class="ptitle" value="" />
                                        </span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="submit inline-edit-save">
                                <?php wp_nonce_field( 'inlineeditnonce', '_inline_edit', false ); ?>
                                <button type="button" class="button button-primary save">Save</button>
                                <button type="button" class="button cancel">Cancel</button>
                                <span class="spinner"></span>

                                <div class="notice notice-error notice-alt inline hidden">
                                    <p class="error"></p>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody></table>
        </form>
        <?php
    }
}

?>
