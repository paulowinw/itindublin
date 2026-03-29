<?php
/**
 * Template for setup worksheet.
 *
 * @package Forminator
 */

// Defaults.
$vars = array(
	'token'               => '',
	'error_message'       => '',
	'client_id'           => '',
	'client_secret'       => '',
	'client_secret_error' => '',
	'client_id_error'     => '',
	'redirect_url'        => '',
	'identifier'          => '',
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
		printf(
		/* translators: 1: Add-on name */
			esc_html__( 'Set Up %1$s Client', 'forminator' ),
			'Google Sheets'
		);
		?>
	</h3>

	<?php if ( ! empty( $vars['token'] ) ) : ?>
		<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Your Google Sheets account is already authorized. Edit info below to re-authorize.', 'forminator' ); ?></p>
	<?php else : ?>
		<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Set up your Google Sheets oAuth 2.0 client by entering your credentials below.', 'forminator' ); ?></p>
		<p class="sui-description">
			<?php if ( forminator_is_show_documentation_link() ) { ?>
				<?php
				printf(
				/* Translators: 1. Opening <a> tag with link to generate credential, 2. closing <a> tag. */
					esc_html__( '%1$sGuide to generate credentials%2$s.', 'forminator' ),
					'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#google-sheets" target="_blank">',
					'</a>'
				);
			}
			?>
		</p>
		<?php if ( ! empty( $vars['error_message'] ) ) : ?>
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
				echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
			?>
		<?php endif; ?>
	<?php endif ?>

</div>

<form>

	<div class="sui-form-field">

		<label class="sui-label"><?php esc_html_e( 'Authorized Redirect URI', 'forminator' ); ?></label>

		<div class="sui-with-button sui-with-button-icon">
			<input type="text" id="auth-redirect-uri" class="sui-form-control" value="<?php echo esc_html( ! empty( $vars['redirect_url'] ) ? $vars['redirect_url'] : '' ); ?>">
			<a class="sui-button-icon copy-clipboard-integration" data-shortcode="<?php echo esc_html( ! empty( $vars['redirect_url'] ) ? $vars['redirect_url'] : '' ); ?>">
				<span aria-hidden="true" class="sui-icon-copy"></span>
				<span class="sui-screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'forminator' ); ?></span>
			</a>
		</div>

		<span class="sui-description"><?php esc_html_e( 'Please use this redirect URI while generating your client credentials on the Google API console.', 'forminator' ); ?></span>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['client_id_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'Client ID', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="client_id"
				placeholder="<?php esc_attr_e( 'Client ID', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['client_id'] ); ?>"
				class="sui-form-control" />

			<i class="sui-icon-profile-male" aria-hidden="true"></i>

		</div>

		<?php if ( ! empty( $vars['client_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['client_id_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['client_secret_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'Client Secret', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="client_secret"
				placeholder="<?php esc_attr_e( 'Client Secret', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['client_secret'] ); ?>"
				class="sui-form-control" />

			<i class="sui-icon-key" aria-hidden="true"></i>

		</div>

		<?php if ( ! empty( $vars['client_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['client_secret_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['identifier'] ); ?>"
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>

	</div>

</form>

<script>
jQuery('.copy-clipboard-integration').on( "click", function ( e ) {
	e.preventDefault();

	copyToClipboardModal( jQuery( this ).prev( '#auth-redirect-uri' ) );

	Forminator.Notification.open( 'success', Forminator.l10n.options.uri_copied, 4000 );
});
</script>
