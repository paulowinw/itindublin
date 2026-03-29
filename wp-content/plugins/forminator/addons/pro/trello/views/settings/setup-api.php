<?php
/**
 * Template for setup api
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'token'         => '',
	'error_message' => '',
	'api_key'       => '',
	'api_key_error' => '',
	'identifier'    => '',
);
/**
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: 1: Add-on name */
		printf( esc_html__( 'Set Up %1$s', 'forminator' ), 'Trello' );
		?>
	</h3>

	<?php if ( ! empty( $vars['token'] ) ) : ?>

		<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Your Trello account is already authorized. Edit info below to re-authorize.', 'forminator' ); ?> </p>

	<?php else : ?>

		<p id="forminator-integration-popup__description" class="sui-description">
			<?php
			printf(
			/* Translators: 1. Opening <a> tag with link to Trello API key, 2. closing <a> tag. */
				esc_html__( 'Please get your Trello API key %1$shere%2$s', 'forminator' ),
				'<a href="https://trello.com/app-key" target="_blank">',
				'</a>'
			);
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
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['api_key_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'API Key', 'forminator' ); ?></label>
		<div class="sui-control-with-icon">
			<input
					class="sui-form-control"
					name="api_key"
					placeholder="<?php /* translators: 1: Add-on name */ printf( esc_attr__( 'Enter %1$s API Key', 'forminator' ), 'Trello' ); ?>"
					value="<?php echo esc_attr( $vars['api_key'] ); ?>">
				<i class="sui-icon-key" aria-hidden="true"></i>
		</div>
		<?php if ( ! empty( $vars['api_key_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['api_key_error'] ); ?></span>
		<?php endif; ?>
	</div>
	<div class="sui-form-field" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
				placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['identifier'] ); ?>"
				class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>

	</div>
</form>
<script>
	(function ($) {
		$('input[name="identifier"]').on( 'change', function (e) {
			var parent = $(this).closest('.sui-box-body'),
				val = $(this).val(),
				link = $('.forminator-addon-connect', parent),
				href = link.prop('href');
			if ( href ) {
				var index = href.indexOf('identifier');

				if ( index ) {
					href = href.slice(0, index);
				}
				href += encodeURIComponent( 'identifier=' + val );
				link.prop('href', href);
			}
		});
	})(jQuery);
</script>
