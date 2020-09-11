(function($) {
	$(document).ready(function(){
		var select2_options = {
			width:	'25em',
			tags:	false,
			createTag: function (tag) {
				// Check if the option is already there
				var found = false;
				$(".availableurl_select option").each(function() {
					if ($.trim(tag.term).toUpperCase() === $.trim($(this).text()).toUpperCase()) {
						found = true;
					}
				});
		
				// Show the suggestion only if a match was not found
				if (!found) {
					return {
						id: tag.term,
						text: tag.term,
						isNew: true
					};
				}
			}
		};
		$( ".availableurl_select" ).select2( select2_options );

		$( "#availableurl_select_options,#availableurl_select_user" ).on( "select2:select", function() {
			window.location.href = $( this ).val();
		} );

		// Create new row of url
		$( "#availableurl_add_url" ).click( function( e ) {
			e.preventDefault();
			var last_id = $( "#availableurl_table_urls tbody tr:last-child" ).attr( "data-id" );
			var new_id = Number( last_id )+1;
			var template = $( "#availableurl_template_url_row table tbody" ).html();
			template = template.replace( /{{index}}/g, new_id );
			$( template ).appendTo( "#availableurl_table_urls tbody" );
			var new_row = "tr[data-id='" + new_id + "']";

			$( new_row + " input," + new_row + " select" ).removeAttr( "disabled" );
			$( new_row + " select" ).select2( select2_options );
			$( new_row ).fadeIn();
		} );

		// Remove row of url
		$( "#availableurl_options .dashicons-dismiss" ).click( function() {
			if( $( ".availableurl_user_url_row" ).length > 1 ) {
				var id = $( this ).attr( "data-id" );
				$( "#" + id ).remove();
			}
		} );
    });
})(jQuery)