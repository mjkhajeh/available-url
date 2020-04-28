(function($) {
	$(document).ready(function(){
		var select2_options = {
			width:	'300px',
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

		$( "#availableurl_select_options" ).on( "select2:select", function() {
			window.location.href = $( this ).val();
		} );

		// Create new row of url
		$( "#availableurl_add_url" ).click( function( e ) {
			e.preventDefault();
			var last_id = $( "#availableurl_table_urls tbody tr:last-child" ).attr( "data-id" );
			var id = Number(last_id)+1;
			var html = '<tr class="availableurl_user_url_row" id="availableurl_user_url_' + id + '" data-id="' + id + '">';
				html += '<td><input type="text" name="availableurl_title[]" id="availableurl_title_' + id + '"></td>';

				html += '<td><input type="url" name="availableurl_url[]" id="availableurl_url_' + id + '" placeholder="' + availableurl.i18n.url_placeholder + '"></td>';

				html += '<td>';
					html += '<select name="availableurl_settings_' + id + '[]" id="availableurl_settings_' + id + '" class="availableurl_select" data-placeholder="' + availableurl.i18n.settings_placeholder + '" multiple data-width="100%">';
						html += '<option value="frontend">' + availableurl.i18n.setting_frontend + '</option>';
						html += '<option value="exactly_url">' + availableurl.i18n.setting_exactly_url + '</option>';
					html += '</select>';
				html += '</td>';
			html += '</tr>';

			$( html ).appendTo( "#availableurl_table_urls tbody" );
			$( "#availableurl_settings_" + id ).select2( select2_options );
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