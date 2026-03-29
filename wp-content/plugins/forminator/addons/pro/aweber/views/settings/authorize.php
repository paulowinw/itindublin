<?php
/**
 * Template Authorize.
 *
 * @package Forminator
 */

$vars = array(
	'account_id'   => 0,
	'auth_url'     => '',
	'is_connected' => false,
);

/**
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: 1: Add-on name */
			printf( esc_html__( 'Connect %1$s', 'forminator' ), 'AWeber' );
		?>
	</h3>

	<?php if ( ! empty( $vars['account_id'] ) ) : ?>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
		echo Forminator_Admin::get_green_notice(
			/* translators: 1: Add-on name */
			sprintf( esc_html__( 'Your %1$s account is already authorized.', 'forminator' ), 'AWeber' )
		);
		?>
	<?php else : ?>
		<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Authorize Forminator to connect with your AWeber account in order to send data from your forms.', 'forminator' ); ?></p>
	<?php endif; ?>

</div>

<?php if ( empty( $vars['account_id'] ) ) : ?>

	<div class="sui-form-field" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value=""
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>

	</div>

	<div class="forminator-integration-popup__footer-temp">

		<div class="sui-actions-right">
			<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
				target="_blank"
				class="sui-button sui-button-blue forminator-addon-connect">
				<?php esc_html_e( 'Authorize', 'forminator' ); ?>
			</a>
		</div>

	</div>

	<script>
		(function ($) {
			$('input[name="identifier"]').on( 'change', function (e) {
				var parent = $(this).closest('.sui-box-body'),
					val = $(this).val(),
					link = $( '.forminator-addon-connect', parent.next() ),
					href = link.prop('href');
				if ( href ) {
					var index = href.indexOf('identifier');

					if ( -1 !== index ) {
						href = href.slice(0, index);
					} else {
						href += encodeURIComponent( '&' );
					}
					href += encodeURIComponent( 'identifier=' + encodeURIComponent( val ) );
					link.prop('href', href);
				}
			});
		})(jQuery);
	</script>

<?php endif; ?>

<?php if ( $vars['is_connected'] ) : ?>
	<div style="text-align: center;">
		<button class="sui-button sui-button-ghost forminator-addon-disconnect forminator-integration-popup__close"><?php esc_html_e( 'Disconnect', 'forminator' ); ?></button>
	</div>
<?php endif; ?>
