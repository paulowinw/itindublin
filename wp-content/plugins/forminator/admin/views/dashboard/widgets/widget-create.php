<?php
/**
 * Template admin/views/dashboard/widgets/widget-create.php
 *
 * @package Forminator
 */

$icon_minus  = forminator_plugin_dir() . 'assets/icons/admin-icons/minus.php';
$hero_sample = forminator_plugin_dir() . 'assets/icons/forminator-icons/hero-sample.php';
?>

<div class="wpmudev-row">

	<div class="wpmudev-col col-12">

		<div id="forminator-dashboard-box--create" class="wpmudev-box wpmudev-box--split wpmudev-can--hide">

			<div class="wpmudev-box-header">

				<div class="wpmudev-header--text">

					<h2 class="wpmudev-title"><?php esc_html_e( 'Create Modules', 'forminator' ); ?></h2>

				</div>

				<div class="wpmudev-header--action">

					<button class="wpmudev-box--action" aria-hidden="true"><span class="wpmudev-icon--plus"></span></button>

					<button class="wpmudev-sr-only"><?php esc_html_e( 'Hide box', 'forminator' ); ?></button>

				</div>

			</div>

			<div class="wpmudev-box-section">

				<?php foreach ( $args ['modules'] as $key => $module ) : ?>

					<div class="wpmudev-split--item">

						<div class="wpmudev-sitem--header">

							<div class="wpmudev-sitem--icon" aria-hidden="true"><?php echo esc_html( $module->get_icon() ); ?></div>

							<h3 class="wpmudev-sitem--title"><?php echo esc_html( $module->get_name() ); ?></h3>

						</div>

						<div class="wpmudev-sitem--section">

							<p><?php echo wp_kses_post( $module->get_description() ); ?></p>

						</div>

						<div class="wpmudev-sitem--footer">

							<button href="/" class="wpmudev-button wpmudev-button-sm wpmudev-button-ghost wpmudev-open-modal" data-modal="<?php echo esc_attr( $module->get_id() ); ?>"><?php echo wp_kses_post( $module->get_label() ); ?></button>

						</div>

					</div>

				<?php endforeach; ?>

			</div>

		</div><?php // .wpmudev-box. ?>

	</div><?php // .wpmudev-col. ?>

</div><?php // .wpmudev-row. ?>
