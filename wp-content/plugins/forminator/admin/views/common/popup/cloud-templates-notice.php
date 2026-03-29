<?php
/**
 * Template admin/views/common/popup/cloud-templates-notice.php
 *
 * @package Forminator
 */

$slug              = isset( $args['slug'] ) ? $args['slug'] : 'form';
$save_to_cloud_url = 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#saving-forms-as-cloud-templates';
$learn_more_url    = 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#cloud-templates';
?>

<div
	role="alert"
	class="sui-notice sui-notice-blue sui-active"
	style="display: block; text-align: left;"
	aria-live="assertive"
>

	<div class="sui-notice-content">

		<div class="sui-notice-message">

			<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

			<p>
				<?php
				$message = sprintf(
					/* translators: 1. Opening <b> tag, 2. Closing </b> tag, 3. Module slug, 4. Opening <a> tag for Save To Cloud link, 5. Closing </a> tag, 6. Opening <a> tag for Learn more link, 7. Closing </a> tag */
					__( '%1$sWant to use this %3$s on another site?%2$s Use %4$sSave To Cloud%5$s to save it as a template and reuse it on any site you manage via The Hub — for free. %6$sLearn more%7$s', 'forminator' ),
					'<b>',
					'</b>',
					esc_html( $slug ),
					'<a href="' . esc_url( $save_to_cloud_url ) . '" target="_blank" rel="noopener">',
					'</a>',
					'<a href="' . esc_url( $learn_more_url ) . '" target="_blank" rel="noopener">',
					'</a>'
				);
				$message = apply_filters( 'forminator_cloud_templates_notice_message', $message, $slug, $save_to_cloud_url, $learn_more_url );
				echo wp_kses_post( $message );
				?>
			</p>

		</div>

	</div>

</div>

