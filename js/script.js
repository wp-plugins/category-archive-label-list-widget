function checkSelect (select) {
	if ( select.val() == 'archive') {
		jQuery(select).parents('.widget-content').find('.categories-options').hide();
		jQuery(select).parents('.widget-content').find('.archive-options').show();
	} else if ( select.val() == 'categories') {
		jQuery(select).parents('.widget-content').find('.archive-options').hide();
		jQuery(select).parents('.widget-content').find('.categories-options').show();
	}
}


function PostSortDisplay (check) {
	if ( check.find('input').is(':checked')) {
		jQuery(check).next('.post-sort').show();
	} else {
		jQuery(check).next('.post-sort').hide();
	}
}


function StylingOptionsDisplay (check) {
	if ( check.find('input').is(':checked')) {
		jQuery(check).next('.styling-options').show();
	} else {
		jQuery(check).next('.styling-options').hide();
	}
}




jQuery( document ).ready(function() {

	jQuery('.callw-posts-type').each(function() {
		checkSelect(jQuery(this));
	});

	jQuery('.callw-posts-type').change(function(){
		checkSelect(jQuery(this));
	});


	jQuery('.post-count-order').each(function(){
		PostSortDisplay(jQuery(this));
	});

	jQuery('.post-count-order').change(function(){
		PostSortDisplay(jQuery(this));
	});


	jQuery('.styling-options-title').each(function(){
		StylingOptionsDisplay(jQuery(this));
	});

	jQuery('.styling-options-title').change(function(){
		StylingOptionsDisplay(jQuery(this));
	});


	jQuery('.my-color-field').wpColorPicker();
});






jQuery( document ).ajaxComplete( function( event, XMLHttpRequest, ajaxOptions ) {
    var request = {}, pairs = ajaxOptions.data.split('&'), i, split, widget;
    for( i in pairs ) {
        split = pairs[i].split( '=' );
        request[decodeURIComponent( split[0] )] = decodeURIComponent( split[1] );
    }

    if( request.action && ( request.action === 'save-widget' ) ) {
        widget = jQuery('input.widget-id[value="' + request['widget-id'] + '"]').parents('.widget');
        if( !XMLHttpRequest.responseText )
            wpWidgets.save(widget, 0, 1, 0);
        else {
			jQuery('.callw-posts-type').each(function() {
				checkSelect(jQuery(this));
			});

			jQuery('.callw-posts-type').change(function(){
				checkSelect(jQuery(this));
			});


			jQuery('.post-count-order').each(function(){
				PostSortDisplay(jQuery(this));
			});

			jQuery('.post-count-order').change(function(){
				PostSortDisplay(jQuery(this));
			});


			jQuery('.styling-options-title').each(function(){
				StylingOptionsDisplay(jQuery(this));
			});

			jQuery('.styling-options-title').change(function(){
				StylingOptionsDisplay(jQuery(this));
			});


			jQuery('.my-color-field').wpColorPicker();
		}
    }
});
