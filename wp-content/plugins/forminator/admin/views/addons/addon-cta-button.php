<?php
/**
 * Template admin/views/addons/addon-cta-button.php
 *
 * @package Forminator
 */

if ( empty( $addons ) || empty( $addons->pid ) ) {
	return;
}
$addons_slug = Forminator_Admin_Addons_Page::get_addon_slug( $addons->pid );

$is_network_active      = $addons->is_network_admin && is_plugin_active_for_network( $addons->filename );
$free_addon             = ! empty( $addons->is_free );
$connected_free_addon   = $free_addon && Forminator_Hub_Connector::hub_connector_connected();
$unconnected_free_addon = $free_addon && ! Forminator_Hub_Connector::hub_connector_connected();

if ( FORMINATOR_PRO || $connected_free_addon ) {

	if ( $addons->is_installed ) {

		// BUTTON: Blue.
		if ( $addons->has_update ) {
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/addons/action-button',
				array(
					'compound' => true,
					'label'    => esc_html__( 'Update', 'forminator' ),
					'icon'     => 'update',
					'color'    => 'blue',
					'class'    => 'addons-actions',
					'attrs'    => array(
						'data-action'  => 'addons-update',
						'data-addon'   => esc_attr( $addons->pid ),
						'data-nonce'   => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
						'data-version' => /* translators: %s: Latest version. */ sprintf( esc_html__( 'Version %s', 'forminator' ), esc_html( $addons->version_latest ) ),
					),
				)
			);
		}

		if ( is_plugin_active( $addons->filename ) ) {

			// BUTTON: Configure.
			if ( $addons->has_config ) {
				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/addons/action-button',
					array(
						'compound' => true,
						'label'    => esc_html__( 'Configure', 'forminator' ),
						'icon'     => 'wrench-tool',
						'id'       => 'addons-configure__' . $addons_slug,
						'class'    => 'addons-configure',
						'attrs'    => array(
							'data-action'         => esc_attr( $addons_slug . '-connect-modal' ),
							'data-slug'           => esc_attr( $addons_slug ),
							'data-addon'          => esc_attr( $addons->pid ),
							'data-nonce'          => esc_attr( wp_create_nonce( 'forminator_' . $addons_slug . '_settings_modal' ) ),
							'data-modal-nonce'    => esc_attr( wp_create_nonce( 'forminator_' . $addons_slug . '_settings_modal' ) ),
							'data-modal-image'    => esc_url( forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo.png' ),
							'data-modal-image-x2' => esc_url( forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo@2x.png' ),
							'data-modal-title'    => /* translators: %s: Add-on slug */ sprintf( esc_html__( 'Connect %s Account', 'forminator' ), ucfirst( $addons_slug ) ),
						),
					)
				);
			}

			// BUTTON: Deactivate.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/addons/action-button',
				array(
					'compound' => true,
					'label'    => ( $is_network_active && is_super_admin() ? esc_html__( 'Network Active', 'forminator' ) : esc_html__( 'Deactivate', 'forminator' ) ),
					'icon'     => 'power-on-off',
					'ghost'    => true,
					'class'    => 'wpmudev-open-modal',
					'disabled' => ( $is_network_active && is_super_admin() ),
					'attrs'    => array(
						'data-action'        => 'addons-deactivate',
						'data-addon'         => esc_attr( $addons->pid ),
						'data-nonce'         => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
						'data-modal'         => 'addons-deactivate',
						'data-modal-title'   => esc_html__( 'Deactivate Add-ons', 'forminator' ),
						'data-modal-content' => /* translators: %s: Add-on name */ sprintf( esc_html__( 'You are trying to deactivate %s which is being used by the following forms. This can break the functionality of the forms. Are you sure you want to proceed?', 'forminator' ), '<strong>' . esc_html( $addons->name ) . '</strong>' ),
						'data-addon-slug'    => esc_attr( $addons_slug ),
						'data-is_network'    => $is_network_active,
					),
				)
			);
		} else {

			// BUTTON: Activate.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/addons/action-button',
				array(
					'compound' => true,
					'label'    => ( $is_network_active && is_super_admin() ? esc_html__( 'Network Activate', 'forminator' ) : esc_html__( 'Activate', 'forminator' ) ),
					'icon'     => 'power-on-off',
					'color'    => 'blue',
					'class'    => 'addons-actions',
					'attrs'    => array(
						'data-action' => 'addons-activate',
						'data-addon'  => esc_attr( $addons->pid ),
						'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
					),
				)
			);

			// BUTTON: Delete.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/addons/action-button',
				array(
					'compound' => true,
					'label'    => esc_html__( 'Delete', 'forminator' ),
					'icon'     => 'trash',
					'ghost'    => true,
					'class'    => 'addons-actions',
					'attrs'    => array(
						'data-action' => 'addons-delete',
						'data-addon'  => esc_attr( $addons->pid ),
						'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
					),
				)
			);
		}
	} else {

		Forminator_Admin_Addons_Page::get_instance()->render_template(
			'admin/views/addons/action-button',
			array(
				'label' => esc_html__( 'Install', 'forminator' ),
				'icon'  => 'download',
				'color' => 'blue',
				'class' => 'addons-actions',
				'attrs' => array(
					'data-action' => 'addons-install',
					'data-addon'  => esc_attr( $addons->pid ),
					'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
				),
			)
		);

	}
} elseif ( $unconnected_free_addon ) { ?>
	<a
		href="<?php echo esc_url( Forminator_Hub_Connector::get_hub_connect_url( $addons_slug ) ); ?>"
		class="sui-button sui-button-bright-blue"
	>
		<span class="sui-icon-plug-connected sui-sm" aria-hidden="true"></span>
		<?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_cta_text() ); ?>
	</a>
<?php } else { ?>
	<a
		href="<?php echo esc_url( 'https://wpmudev.com/project/forminator-pro/?utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_' . $addons_slug . '-addon' ); ?>"
		target="_blank"
		class="sui-button sui-button-ghost sui-button-purple"
	>
		<?php esc_html_e( 'Upgrade to PRO', 'forminator' ); ?>
	</a>
	<?php
}
