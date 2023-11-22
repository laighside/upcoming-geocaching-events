/**
 * This file contains the functions needed for the inline editing of events.
 */

window.wp = window.wp || {};

/**
 * Manages the quick edit editing events.
 *
 * @namespace inlineEditEvent
 *
 * @type {Object}
 *
 * @property {string} what The prefix before the event ID.
 *
 */
( function( $, wp ) {

    window.inlineEditEvent = {

    /**
     * Initializes the inline editor.
     *
     * Binds event handlers to the Escape key to close the inline editor
     * and to the save and close buttons. Changes DOM to be ready for inline
     * editing.
     *
     * @memberof inlineEditEvent
     *
     * @return {void}
     */
    init : function(){
        var t = this, qeRow = $('#inline-edit');

        // event ID prefix.
        t.what = '#event_';

        /**
         * Binds the Escape key to revert the changes and close the quick editor.
         *
         * @return {boolean} The result of revert.
         */
        qeRow.on( 'keyup', function(e){
            // Revert changes if Escape key is pressed.
            if ( e.which === 27 ) {
                return inlineEditEvent.revert();
            }
        });

        /**
         * Reverts changes and close the quick editor if the cancel button is clicked.
         *
         * @return {boolean} The result of revert.
         */
        $( '.cancel', qeRow ).on( 'click', function() {
            return inlineEditEvent.revert();
        });

        /**
         * Saves changes in the quick editor if the save(named: update) button is clicked.
         *
         * @return {boolean} The result of save.
         */
        $( '.save', qeRow ).on( 'click', function() {
            return inlineEditEvent.save(this);
        });
        
        /**
         * Opens the editor when the "Add new event" button is clicked
         *
         * @return {boolean} The result of save.
         */
        $('.add-event-button').on( 'click', function() {
            return inlineEditEvent.edit(-1);
        });

        /**
         * If Enter is pressed, and the target is not the cancel button, save the event.
         *
         * @return {boolean} The result of save.
         */
        $('td', qeRow).on( 'keydown', function(e){
            if ( e.which === 13 && ! $( e.target ).hasClass( 'cancel' ) ) {
                return inlineEditEvent.save(this);
            }
        });

        /**
         * Binds click event to the .edit-inline button which opens the quick editor.
         */
        $( '#the-list' ).on( 'click', '.edit-inline', function() {
            $( this ).attr( 'aria-expanded', 'true' );
            inlineEditEvent.edit( this );
        });

        /**
         * Binds click event to the .delete-inline button which deletes the event.
         */
        $( '#the-list' ).on( 'click', '.delete-inline', function() {
            $( this ).attr( 'aria-expanded', 'true' );
            inlineEditEvent.deleteEvent( this );
        });

    },

    /**
     * Creates a quick edit window for the event that has been clicked.
     *
     * @memberof inlineEditEvent
     *
     * @param {number|Object} id The ID of the clicked event or an element within a event table row.
     * @return {boolean} Always returns false at the end of execution.
     */
    edit : function(id) {
        var t = this, fields, editRow, rowData, f, val;
        t.revert();

        if ( typeof(id) === 'object' ) {
            id = t.getId(id);
        }

        fields = ['event_name', 'event_url', 'jj', 'mm', 'aa', 'location'];

        // Add the new edit row with an extra blank row underneath to maintain zebra striping.
        editRow = $('#inline-edit').clone(true);
        $( 'td', editRow ).attr( 'colspan', $( 'th:visible, td:visible', '.widefat:first thead' ).length );

        // Remove the ID from the copied row and let the `for` attribute reference the hidden ID.
        $( 'td', editRow ).find('#quick-edit-legend').removeAttr('id');
        $( 'td', editRow ).find('p[id^="quick-edit-"]').removeAttr('id');

        if (id >= 0) {
            $(t.what+id).removeClass('is-expanded').hide().after(editRow).after('<tr class="hidden"></tr>');

            // Populate fields in the quick edit window.
            rowData = $('#inline_'+id);

            for ( f = 0; f < fields.length; f++ ) {
                val = $('.'+fields[f], rowData);

                /**
                 * Replaces the image for a Twemoji(Twitter emoji) with it's alternate text.
                 */
                val.find( 'img' ).replaceWith( function() { return this.alt; } );
                val = val.text();
                $(':input[name="' + fields[f] + '"]', editRow).val( val );
            }
        } else {
            var lastRow = $(".event-tr:last");
            if (lastRow.length > 0) { // There's already an event in the table
                lastRow.after(editRow).after('<tr class="hidden"></tr>');
            } else { // No events in the table
                $("#the-list").append(editRow).after('<tr class="hidden"></tr>');
            }
        }

        $(editRow).attr('id', 'edit_'+id).addClass('inline-editor').show();

        return false;
    },

    /**
     * Saves the changes made in the quick edit window.
     *
     * @param {number} id The ID for the event that has been changed.
     * @return {boolean} False, so the form does not submit when pressing enter on a focused field.
     */
    save : function(id) {
        var params, fields;

        if ( typeof(id) === 'object' ) {
            id = this.getId(id);
        }

        $( 'table.widefat .spinner' ).addClass( 'is-active' );

        params = {
            _ajax_nonce: gc_ajax_obj.nonce,
            action: 'gc_event_save',
            event_id: id
        };

        fields = $('#edit_'+id).find(':input').serialize();

        params = fields + '&' + $.param(params);

        // Make Ajax request.
        $.post( gc_ajax_obj.ajax_url, params,
            function(r) {
                var $errorNotice = $( '#edit_' + id + ' .inline-edit-save .notice-error' ),
                    $error = $errorNotice.find( '.error' );

                $( 'table.widefat .spinner' ).removeClass( 'is-active' );

                if (r) {
                    if ( -1 !== r.indexOf( '<tr' ) ) {
                        $(inlineEditEvent.what+id).siblings('tr.hidden').addBack().remove();
                        $('#edit_'+id).before(r).remove();
                        $( inlineEditEvent.what + id ).hide().fadeIn( 400, function() {
                            // Move focus back to the Quick Edit button. $( this ) is the row being animated.
                            $( this ).find( '.editinline' )
                                .attr( 'aria-expanded', 'false' )
                                .trigger( 'focus' );
                        });
                    } else {
                        r = r.replace( /<.[^<>]*?>/g, '' );
                        $errorNotice.removeClass( 'hidden' );
                        $error.html( r );
                    }
                } else {
                    $errorNotice.removeClass( 'hidden' );
                    $error.text( 'Error while saving the changes.' );
                }
            },
        'html');

        // Prevent submitting the form when pressing Enter on a focused field.
        return false;
    },
    
    /**
     * Deletes an event, called when a delete button is clicked.
     *
     * @memberof inlineEditEvent
     *
     * @param {number|Object} id The ID of the clicked event or an element within a event table row.
     * @return {boolean} Always returns false at the end of execution.
     */
    deleteEvent : function(id) {
        var t = this
        if ( typeof(id) === 'object' ) {
            id = t.getId(id);
        }
        
        var rowData = $('#inline_'+id);
        var event_name = $('.event_name', rowData).text();
        
        if (confirm('Are you sure you want to delete "' + event_name + '"?')) {
        
            var params = {
                _ajax_nonce: gc_ajax_obj.nonce, //nonce
                action: 'gc_event_delete',
                event_id: id
            };
            
            // Make Ajax request.
            $.post( gc_ajax_obj.ajax_url, params,
                function(r) {
                    if (r) {
                        if (r.success) {
                            $('#event_'+r.data.id).remove();
                        } else {
                            alert(r.data);
                        }
                    } else {
                        alert('Error while deleting event');
                    }
                },
            'json');

        }
        return false;
    },

    /**
     * Hides and empties the Quick Edit window.
     *
     * @memberof inlineEditEvent
     *
     * @return {boolean} Always returns false.
     */
    revert : function(){
        var $tableWideFat = $( '.widefat' ),
            id = $( '.inline-editor', $tableWideFat ).attr( 'id' );

        if (id) {
            $( '.spinner', $tableWideFat ).removeClass( 'is-active' );

            // Remove both the inline-editor and its hidden tr siblings.
            $('#'+id).siblings('tr.hidden').addBack().remove();
            id = id.substr( id.lastIndexOf('_') + 1 );

            // Show the event row and move focus back to the Quick Edit button.
            $( this.what + id ).show().find( '.edit-inline' )
                .attr( 'aria-expanded', 'false' )
                .trigger( 'focus' );
        }

        return false;
    },

    /**
     * Gets the ID for the event that you want to quick edit from the row in the quick edit table.
     *
     * @memberof inlineEditEvent
     *
     * @param {Object} o DOM row object to get the ID for.
     * @return {string} The event ID extracted from the table row in the object.
     */
    getId : function(o) {
        var id = $(o).closest('tr').attr('id'),
            parts = id.split('_');
        return parts[parts.length - 1];
    }
};

$( function() { inlineEditEvent.init(); } );

})( jQuery, window.wp );

