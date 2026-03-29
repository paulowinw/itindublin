<?php
/**
 * Template for setup connect
 *
 * @package Forminator
 */

// Defaults.
$vars = array(
	'error_message' => '',
	'is_connected'  => false,
);

$activate_description = esc_html__( 'Activate Webhook to start using it with your forms, quizzes, and polls.', 'forminator' );

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
		printf( esc_html__( 'Activate %1$s', 'forminator' ), 'Webhook' );
		?>
	</h3>

	<?php if ( ! empty( $vars['is_connected'] ) || ! empty( $vars['error_message'] ) ) : ?>
		<p id="forminator-integration-popup__description" class="sui-description">
			<?php echo esc_html( $activate_description ); ?>
		</p>
	<?php endif; ?>

</div>

<?php if ( empty( $vars['is_connected'] ) && empty( $vars['error_message'] ) ) : ?>
	<p id="forminator-integration-popup__description" class="sui-description" style="margin: 0; text-align: center;">
		<?php echo esc_html( $activate_description ); ?>
	</p>
<?php endif; ?>

<?php if ( ! empty( $vars['is_connected'] ) ) : ?>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_green_notice(
		esc_html__(
			'Webhook is already active.',
			'forminator'
		)
	);
	?>
<?php endif; ?>

<?php if ( ! empty( $vars['error_message'] ) ) : ?>
	<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
		echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
	?>
<?php endif; ?>

<form>
	<input type="hidden" value="1" name="connect">
</form>
