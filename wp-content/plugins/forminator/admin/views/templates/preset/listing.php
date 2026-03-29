<?php
/**
 * Template admin/views/templates/preset/listing.php
 *
 * @package Forminator
 */

?>
<li>
	<?php
	if ( 'blank' === $id ) {
		?>
		<div class="sui-box-selector forminator-card forminator-blank-card create-blank-form">
	<?php } else { ?>
		<div class="sui-box-selector forminator-card">
	<?php } ?>

		<div class="forminator-card-image">
		<?php if ( ! empty( $thumbnail ) ) { ?>
			<img src="<?php echo esc_url( $thumbnail ); ?>"
				alt=""
				class="sui-image sui-image-center fui-image">
		<?php } else { ?>
			<span class="forminator-template-icon">
				<svg width="56" height="50" viewBox="0 0 56 50" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.22222 45.3125H25.5208L25.025 47.2852C24.8014 48.2031 24.85 49.1406 25.1611 50H6.22222C2.79028 50 0 47.1973 0 43.75V6.25C0 2.80273 2.79028 0 6.22222 0H22.3125C23.9653 0 25.55 0.654297 26.7167 1.82617L35.5153 10.6641C36.6819 11.8359 37.3333 13.4277 37.3333 15.0879V29.2578L32.6667 33.9453V15.625H24.8889C23.1681 15.625 21.7778 14.2285 21.7778 12.5V4.6875H6.22222C5.36667 4.6875 4.66667 5.39062 4.66667 6.25V43.75C4.66667 44.6094 5.36667 45.3125 6.22222 45.3125ZM53.4528 23.0176L54.8528 24.4238C56.3694 25.9473 56.3694 28.418 54.8528 29.9512L51.9944 32.8223L45.0917 25.8887L47.95 23.0176C49.4667 21.4941 51.9264 21.4941 53.4528 23.0176ZM30.3236 40.7227L42.8847 28.1055L49.7875 35.0391L37.2264 47.6465C36.8278 48.0469 36.3319 48.3301 35.7778 48.4668L29.9347 49.9316C29.4 50.0684 28.8458 49.9121 28.4569 49.5215C28.0681 49.1309 27.9125 48.5742 28.0486 48.0371L29.5069 42.168C29.6431 41.6211 29.925 41.1133 30.3236 40.7129V40.7227Z" fill="#888888"/>
				</svg>
			</span>
		<?php } ?>
		</div>
		<?php if ( ! FORMINATOR_PRO && $pro && ! Forminator_Hub_Connector::hub_connector_connected() ) { ?>
			<span class="sui-tag sui-tag-free-plan"><?php esc_html_e( 'Free plan', 'forminator' ); ?></span>
		<?php } ?>
		<div class="forminator-card-details forminator-card-for-<?php echo esc_attr( $id ); ?>">
			<h3><?php echo esc_html( $name ); ?></h3>
			<p><?php echo esc_html( $description ); ?></p>
			<div class="forminator-card-cta">
				<?php if ( ! empty( $screenshot ) ) { ?>
				<div>
					<button
							class="sui-button sui-button-ghost forminator-template-preview"
							data-modal-open="forminator-modal-template-preview"
							data-screenshot="<?php echo esc_url( $screenshot ); ?>"
							data-title="<?php echo esc_attr( $name ); ?>"
					>
						<?php esc_html_e( 'Preview', 'forminator' ); ?>
					</button>
				</div>
				<?php } ?>
				<div>
					<?php if ( $pro && ! Forminator_Hub_Connector::hub_connector_connected() ) { ?>
						<a class="sui-button sui-button-bright-blue"
							href="<?php echo esc_url( Forminator_Hub_Connector::get_hub_connect_url( 'preset-template' ) ); ?>">
							<?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_cta_text() ); ?>
						</a>
					<?php } else { ?>
						<button class="sui-button create-form sui-button-blue" data-id="<?php echo esc_html( $id ); ?>">
							<span class="sui-loading-text">
								<?php
								if ( 'blank' === $id ) {
									esc_html_e( 'Create Blank Form', 'forminator' );
								} else {
									esc_html_e( 'Create Form', 'forminator' );
								}
								?>
							</span>
							<!-- Spinning loading icon -->
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						</button>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</li>
