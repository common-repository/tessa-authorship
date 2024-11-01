var tasrpasFindAuthors;
(function($){
	tasrpasFindAuthors = {
		open : function() {
			$('.authorship-box-search .spinner').hide();
			var st = document.documentElement.scrollTop || $(document).scrollTop();

			$('#authorship').show().draggable({
				handle: '#authorship-box-head'
			}).css({'top':st + 50 + 'px','left':'50%','marginLeft':'-250px'});

			$('#authorship-input').focus().keyup(function(e){
				if (e.which == 27) { tasrpasFindAuthors.close(); } // close on Escape
			});

			return false;
		},

		close : function() {
			$('.authorship-box-search .spinner').hide();
			$('#authorship').draggable('destroy').hide();
		},

		send : function() {
			$('.authorship-box-search .spinner').show();
			$pt = 'authorship';
			var post = {
				ps: $('#authorship-input').val(),
				pid: $('#tasrpas_pid').val(),
				ppt: $('#tasrpas_ppt').val(),
				action: 'tasrpas_ajax_find_authors',
				_ajax_nonce: $('#_tasrpas_ajax_nonce').val(),
				post_type: $pt
			};

			$.ajax({
				type : 'POST',
				url : ajaxurl,
				data : post,
				success : function(x) { tasrpasFindAuthors.show(x); },
				error : function(r) { tasrpasFindAuthors.error(r); }
			});				
		},

		show : function(x) {

			$('.authorship-box-search .spinner').hide();
			if ( typeof(x) == 'string' ) {
				this.error({'responseText': x});
				return;
			}

			var r = wpAjax.parseAjaxResponse(x);

			if ( r.errors ) {
				this.error({'responseText': wpAjax.broken});
			}
			r = r.responses[0];
			$('#authorship-response').html(r.data);
		},

		error : function(r) {
			$('.authorship-box-search .spinner').hide();
			var er = r.statusText;

			if ( r.responseText ) {
				er = r.responseText.replace( /<.[^<>]*?>/g, '' );
			}
			if ( er ) {
				$('#authorship-response').html(er);
			}
		}
	};
})(jQuery);  

function tasrpas_find_authors_dialog( event )
{
	event.preventDefault();
	tasrpasFindAuthors.open(); 
}
			
jQuery( document ).ready( function() {

	jQuery('#authorship-submit').click(function(e) {
		if ( '' == jQuery('#authorship-response').html() )
			e.preventDefault();
	});
	jQuery( '#authorship .authorship-box-search :input' ).keypress( function( event ) {
		if ( 13 == event.which ) {
			tasrpasFindAuthors.send();
			return false;
		}
	} );
	jQuery( '#authorship-search' ).click( tasrpasFindAuthors.send );
	jQuery( '#authorship-close' ).click( tasrpasFindAuthors.close );
		
	jQuery( '#tasrpas_open_button' ).on( 'click', tasrpas_find_authors_dialog );
	
	jQuery( '#tasrpas_delete_related_author' ).click( function(){
		jQuery( '#ul_tasrpas' ).animate( {opacity: 0 }, 500, function() { 
																jQuery( this ).html( '' ) ;
																jQuery( '#tasrpas_ids' ) .val( '' );
																jQuery( this ).css( 'opacity', '1' ) ;
															}
										);
	} );
			
	jQuery( 'body:first' ).prepend( jQuery( '.authorship-box-search input#_ajax_nonce' ) );
	
	jQuery( "#ul_tasrpas" ).sortable({
		'update' : function(event, ui) {
			var ids = [];
			jQuery('#ul_tasrpas li').each(function(i, item){
				ids.push(jQuery(item).attr('data-id'));
			});
			jQuery('#tasrpas_ids').val(ids.join(','));
		},
		'revert': true,
		'placeholder': 'sortable-placeholder',
		'tolerance': 'pointer',
		'axis': 'y',
		'containment': 'parent',
		'cursor': 'move',
		'forcePlaceholderSize': true,
		'dropOnEmpty': false,
	});
	
	jQuery( '#authorship-submit' ).click( function(e) {
		e.preventDefault();
		if( jQuery( 'input[name="found_author_id[]"]:checked' ).length == 0)
			return false;
		jQuery( 'input[name="found_author_id[]"]:checked' ).each( function(id){
			var selectedID = jQuery(this).val();
			var tasrpas_ids = new Array();
			tasrpas_ids = jQuery( '#tasrpas_ids' ).val() != '' ? jQuery( '#tasrpas_ids' ).val().split( ',' ) : [];
			if( jQuery.inArray( selectedID, tasrpas_ids ) == "-1"){
				tasrpas_ids.push( selectedID );
				jQuery( '#tasrpas_ids' ).val( tasrpas_ids );
				jQuery( this ).parent().parent().css( 'background', '#FF0000' ).fadeOut( 500, function(){ jQuery( this ).remove() } );
				var label = jQuery( this ).parent().next().text();
				label = label.replace(/</g, "&lt;");
				label = label.replace(/>/g, "&gt;");
				var elem_li = '<li data-id="' + selectedID + '"><span style="float:none;"><a class="erase_tasrpas">X</a>&nbsp;&nbsp;' + label + '</span></li>';
				jQuery( '#ul_tasrpas' ).append( elem_li );
			}
		});
		tasrpasFindAuthors.close;
		return false;			
	});
	setInterval( function() {
		if (jQuery( '#authorship-response input:checkbox' ).length > 0) {
			var $forbidden_ids = jQuery( '#tasrpas_ids' ).val().split( ',' );
			
			jQuery( '#authorship-response input' ).each( function(i) { 
				if (jQuery.inArray(jQuery(this).val(),$forbidden_ids)>-1)
					jQuery(this).attr('disabled', 'disabled').attr('checked', 'checked');
			});
			
		}
	}, 100);
	
	jQuery( '.erase_tasrpas' ).live( 'click', function() {
		var id = jQuery( this ).parent().parent().attr( 'data-id' );
		jQuery( this ).parent().parent().fadeOut( 500, function(){ jQuery( this ).remove() } );
		var tasrpas_ids = ',' + jQuery( '#tasrpas_ids' ).val() + ',';
		tasrpas_ids = tasrpas_ids.replace( ','+id+',', ',' );
		jQuery( '#tasrpas_ids' ).val( tasrpas_ids.length>1 ? tasrpas_ids.substring( 1, tasrpas_ids.length-1 ) : '' );
	});
	
});