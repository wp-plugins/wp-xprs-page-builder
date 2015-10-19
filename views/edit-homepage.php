<div class="wrap">
	<form action="options-general.php?page=xprs-homepage" method="post">
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
			<label style="display: block;"><?php _e( 'Check for VBID code in the URL of your website while in the editor', $this->text_domain ) ?></label>
		</div>
		<?php submit_button(); ?>
	</form>
</div>

<script>
	
    function open_tab( url ) {
        var edit_url = "<?php echo $edit_url ?>";
        var win = window.open( edit_url + '&iframe_url=' + url, '_blank' );
        win.focus();
    }

    function vbid() {
        function s4() {
            return Math.floor( ( 1 + Math.random() ) * 0x10000 )
                .toString( 16 )
                .substring( 1 );
        }
        return 'vbid-' + s4() + '-' + s4() + '-' + s4();
    }

    jQuery( function ( ) {

        jQuery( '#wp_xprs_edit_content' ).click( function () {
            open_tab( 'http://imxprs.com/dual/' + jQuery( '#wp_xprs_vbid' ).val() );
            return false;
        } );
        jQuery( '#wp_xprs_create_content' ).click( function ( )
        {
            var code = vbid();
            var mode = jQuery( '#wp_xprs_insert_mode' ).val();
            jQuery( '#wp_xprs_vbid' ).val( code );
            jQuery( '#wp_xprs_vbid' ).trigger( 'change' );
            open_tab( 'http://www.imxprs.com/wpxprs?vbid=' + code + '&mode=' + 5 );
            return false;
        } );
        jQuery( '#wp_xprs_vbid' ).change( function () {
            jQuery( '#wp_xprs_edit_content' ).css( 'display', jQuery( this ).val().length ? 'inline-block' : 'none' );
        } );
        jQuery( '#wp_xprs_vbid' ).trigger( 'change' );
    } );
</script>