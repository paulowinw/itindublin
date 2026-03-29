<?php
/**
 * Template admin/views/dashboard/promote-free-plan.php
 *
 * @package Forminator
 */

?>


<script type="text/javascript">
	// Remind me later.
	jQuery( '#forminator-promote-remind-later' ).on( 'click', function( e ) {
		e.preventDefault();

		var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';
		var $notice = jQuery( '[data-notice-slug="forminator_promote_free_plan"]' );

		jQuery.post(
			ajaxUrl,
			{
				action: 'forminator_promote_remind_later',
				_ajax_nonce: jQuery( this ).data('nonce')
			}
		).always( function() {
			$notice.hide();
		});
	});
</script>
