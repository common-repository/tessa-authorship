var tasrpasFindContent;
(function($){
	tasrpasFindContent = {
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
			$pt = '';
			$('.find-box-search .spinner').show();
			$('input[name="find-what[]"]:checked').each(function(){
				$pt += $( this ).val() + ',';
			});
			var post = {
				ps: $('#authorship-input').val(),
				pid: $('#saprsat_pid').val(),
				ppt: $('#saprsat_ppt').val(),
				action: 'tasrpas_ajax_find_content',
				_ajax_nonce: $('#_saprsat_ajax_nonce').val(),
				post_type: $pt
			};

			$.ajax({
				type : 'POST',
				url : ajaxurl,
				data : post,
				success : function(x) { tasrpasFindContent.show(x); },
				error : function(r) { tasrpasFindContent.error(r); }
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

	$(document).ready(function() {
		
	});
})(jQuery);  

function tasrpas_find_content_dialog( event )
{
	event.preventDefault();
	tasrpasFindContent.open(); 
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
	jQuery( '#authorship-search' ).click( tasrpasFindContent.send );
	jQuery( '#authorship-close' ).click( tasrpasFindContent.close );
		
	jQuery( '#saprsat_open_button' ).on( 'click', tasrpas_find_content_dialog );
	
	jQuery( '#saprsat_delete_related_content' ).click( function(){
		jQuery( '#ul_saprsat' ).animate( {opacity: 0 }, 500, function() { 
																jQuery( this ).html( '' ) ;
																jQuery( '#saprsat_ids' ) .val( '' );
																jQuery( this ).css( 'opacity', '1' ) ;
															}
										);
	} );
			
	jQuery( 'body:first' ).prepend( jQuery( '.authorship-box-search input#_ajax_nonce' ) );
	
	jQuery( "#ul_saprsat" ).sortable({
		'update' : function(event, ui) {
			var ids = [];
			jQuery('#ul_saprsast li').each(function(i, item){
				ids.push(jQuery(item).attr('data-id'));
			});
			jQuery('#saprsat_ids').val(ids.join(','));
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
		if( jQuery( 'input[name="found_content_id[]"]:checked' ).length == 0)
			return false;
		jQuery( 'input[name="found_content_id[]"]:checked' ).each( function(id){
			var selectedID = jQuery(this).val();
			var saprsat_ids = new Array();
			saprsat_ids = jQuery( '#saprsat_ids' ).val() != '' ? jQuery( '#saprsat_ids' ).val().split( ',' ) : [];
			if( jQuery.inArray( selectedID, saprsat_ids ) == "-1"){
				saprsat_ids.push( selectedID );
				jQuery( '#saprsat_ids' ).val( saprsat_ids );
				jQuery( this ).parent().parent().css( 'background', '#FF0000' ).fadeOut( 500, function(){ jQuery( this ).remove() } );
				var label = jQuery( this ).parent().next().text();
				label = label.replace(/</g, "&lt;");
				label = label.replace(/>/g, "&gt;");
				var elem_li = '<li data-id="' + selectedID + '"><span style="float:none;"><a class="erase_saprsat">X</a>&nbsp;&nbsp;' + label + '</span></li>';
				jQuery( '#ul_saprsat' ).append( elem_li );
			}
		});
		tasrpasFindContent.close;
		return false;			
	});
	setInterval( function() {
		if (jQuery( '#authorship-response input:checkbox' ).length > 0) {
			var $forbidden_ids = jQuery( '#saprsat_ids' ).val().split( ',' );
			jQuery( '#authorship-response input' ).each( function(i) { 
				if (jQuery.inArray(jQuery(this).val(),$forbidden_ids)>-1)
					jQuery(this).attr('disabled', 'disabled').attr('checked', 'checked');
			});			
		}
	}, 100);
	
	jQuery( '.erase_saprsat' ).live( 'click', function() {
		var id = jQuery( this ).parent().parent().attr( 'data-id' );
		jQuery( this ).parent().parent().fadeOut( 500, function(){ jQuery( this ).remove() } );
		var saprsat_ids = ',' + jQuery( '#saprsat_ids' ).val() + ',';
		saprsat_ids = saprsat_ids.replace( ','+id+',', ',' );
		jQuery( '#saprsat_ids' ).val( saprsat_ids.length>1 ? saprsat_ids.substring( 1, saprsat_ids.length-1 ) : '' );
	});
	
});