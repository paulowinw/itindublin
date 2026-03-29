<?php
/**
 * Template admin/views/settings/tab-appearance-presets.php
 *
 * @package Forminator
 */

$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$style   = 'appearance-presets' !== $section ? 'display: none;' : '';
$presets = Forminator_Settings_Page::get_preset_names();
$preset  = Forminator_Core::sanitize_text_field( 'preset' );
if ( empty( $presets[ $preset ] ) ) {
	$preset = 'default';
}
$args = array(
	'presets' => $presets,
	'preset'  => $preset,
	'class'   => 'forminator-for-desktop',
);
?>

<div class="sui-box sui-box-sticky" data-nav="appearance-presets" style="<?php echo esc_attr( $style ); ?>">
	<?php $this->template( 'settings/appearance-presets-status-bar', $args ); ?>
	<?php $args['class'] = 'forminator-for-mobile'; ?>
	<?php $this->template( 'settings/appearance-presets-status-bar', $args ); ?>
</div>

<div class="sui-box" data-nav="appearance-presets" style="<?php echo esc_attr( $style ); ?>" id="forminator-appearance-preset">

</div>
