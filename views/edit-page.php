
<div class="wrap" style="overflow:hidden">
    <iframe id="xprs" src="<?php echo $iframe_url ?>" frameborder="0" style="overflow:hidden;width:100%" height="600px" width="100%"></iframe>
</div>

<script>
    jQuery( document ).ready( function ( $ ) {
        $( window ).resize( function ( e ) {
            var height = $( window ).height()
            $( '#xprs' ).css( { height: height - 200 } )

        } )
        $( window ).resize()
    } )
</script>