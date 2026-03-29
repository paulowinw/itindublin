<?php
/**
 * Template for Authorize.
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'auth_url' => '',
	'token'    => '',
	'user'     => '',
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
			printf( esc_html__( 'Connect %1$s', 'forminator' ), 'HubSpot' );
		?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php
		if ( ! empty( $vars['token'] ) ) :
			esc_html_e( 'You are already connected to the HubSpot. You can disconnect your HubSpot Integration (if you need to) using the button below.', 'forminator' );
		else :
			esc_html_e( "Authenticate your HubSpot account using the button below. Note that you'll be taken to the HubSpot website to grant access to Forminator and then redirected back.", 'forminator' );
		endif;
		?>
	</p>

</div>

<?php if ( ! empty( $vars['token'] ) ) : ?>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_green_notice(
		sprintf(
		/* Translators: 1. Opening <strong> tag, 2. User 3. closing <strong> tag. */
			esc_html__( 'You are connected to %1$s%2$s%3$s.', 'forminator' ),
			'<strong>',
			esc_html( $vars['user'] ),
			'</strong>'
		)
	);
	?>

<?php endif; ?>

<?php if ( empty( $vars['token'] ) ) : ?>

	<div class="sui-form-field" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value=""
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>

	</div>

	<div class="forminator-integration-popup__footer-temp">
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button sui-button-primary forminator-addon-connect forminator-integration-popup__close"><?php esc_html_e( 'Authenticate', 'forminator' ); ?></a>
	</div>

	<script>
		(function ($) {
			$('input[name="identifier"]').on( 'change', function (e) {
				var parent = $(this).closest('.sui-box-body'),
					val = $(this).val(),
					link = $( '.forminator-addon-connect', parent.next() ),
					paramName = 'identifier',
					pattern = '',
					href = link.prop('href');
				if ( href ) {
					var index = href.indexOf( paramName );
					if ( -1 !== index ) {
						const regex = new RegExp( paramName + '[^ ]+global_id', 'g' );
						pattern = href.match(regex);
					} else {
						pattern = 'global_id';
					}
					href = href.replace( pattern, encodeURIComponent( encodeURIComponent( paramName + '=' + encodeURIComponent( val ) + '&global_id' ) ) );

					link.prop('href', href);
				}
			});
		})(jQuery);
	</script>

<?php endif; ?>
