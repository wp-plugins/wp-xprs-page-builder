<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<div>
    <div style="width: 150px;float:left;" for="wp_xprs_insert_mode">
		<?php _e( 'How to insert content?:', $this->text_domain ); ?>
    </div>
    <select id="wp_xprs_insert_mode" name="wp_xprs_insert_mode" style="width: 200px;">
        <option <?= selected( 1, $mode ) ?> value="1"><?php _e( 'Replace Content', $this->text_domain ); ?></option>
        <option <?= selected( 2, $mode ) ?> value="2"><?php _e( 'Replace Page', $this->text_domain ); ?></option>
        <option <?= selected( 3, $mode ) ?> value="3"><?php _e( 'Insert Content Before', $this->text_domain ); ?></option>
        <option <?= selected( 4, $mode ) ?> value="4"><?php _e( 'Insert Content After', $this->text_domain ); ?></option>
		<?php if ( $front ): ?><option <?= selected( 5, $mode ) ?> value="5"><?php _e( 'Replace Entire Site', $this->text_domain ); ?></option><?php endif; ?>
    </select> &nbsp;&nbsp;
	<label class="wp_xprs_label"><input type="checkbox" <?= checked( $sidebar ) ?> id="wp_xprs_hide_sidebar" name="wp_xprs_hide_sidebar"/><?php _e( 'Hide SideBar ', $this->text_domain ); ?>&nbsp;</label> 
	<label class="wp_xprs_label" ><input type="checkbox" <?= checked( $comments ) ?> id="wp_xprs_hide_comments" name="wp_xprs_hide_comments"/><?php _e( 'Hide Comments ', $this->text_domain ); ?>&nbsp;</label>
</div>
<div>
    <div style="width: 150px;float:left;" for="wp_xprs_create_content">
		<?php _e( 'Create new content:', $this->text_domain ); ?>
    </div>
    <button class="button" id="wp_xprs_create_content"><?php _e( 'Create new content', $this->text_domain ); ?></button>
    <button class="button" id="wp_xprs_edit_content" style="display: none;"><?php _e( 'Edit current content', $this->text_domain ); ?></button>
</div>
<div>
    <div style="width: 150px;float:left;" >
		<?php _e( 'Insert Existing Content:', $this->text_domain ); ?>
    </div>
    <input id="wp_xprs_vbid" name="wp_xprs_vbid" style="width: 300px" type="text" value="<?php echo esc_attr( $vbid ); ?>" placeholder="<?php _e( 'Insert here your vbid: vbid-XXXX-XXXX-XXXX', $this->text_domain ); ?>"/>
</div>

<script>
	function open_tab( url ) {
		var win = window.open( url, '_blank' );
		win.focus();
	}

	function show_checkboxes() {
		if ( jQuery( '#wp_xprs_insert_mode' ).val() === '1' )
			jQuery( '.wp_xprs_label' ).show();
		else
			jQuery( '.wp_xprs_label' ).hide();
	}

	function vbid() {
		function s4() {
			return Math.floor( ( 1 + Math.random() ) * 0x10000 )
				.toString( 16 )
				.substring( 1 );
		}
		return 'vbid-' + s4() + '-' + s4() + '-' + s4();
	}

	jQuery( function( ) {
		show_checkboxes(); //to hide if it's necessary

		jQuery( '#wp_xprs_insert_mode' ).change( function() {
			show_checkboxes();
		} );

		jQuery( '#wp_xprs_edit_content' ).click( function() {
			open_tab( 'http://imxprs.com/dual/' + jQuery( '#wp_xprs_vbid' ).val() );
			return false;
		} );
		jQuery( '#wp_xprs_create_content' ).click( function( )
		{
			var code = vbid();
			var mode = jQuery( '#wp_xprs_insert_mode' ).val();
			jQuery( '#wp_xprs_vbid' ).val( code );
			jQuery( '#wp_xprs_vbid' ).trigger( 'change' );
			open_tab( 'https://www.imxprs.com/wpxprs?vbid=' + code + '&mode=' + mode );
			return false;
		} );
		jQuery( '#wp_xprs_vbid' ).change( function() {
			jQuery( '#wp_xprs_edit_content' ).css( 'display', jQuery( this ).val().length ? 'inline-block' : 'none' );
		} );
		jQuery( '#wp_xprs_vbid' ).trigger( 'change' );
	} );
</script>