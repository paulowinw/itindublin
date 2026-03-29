<?php
/**
 * Forminator Admin Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Page
 *
 * @since 1.0
 */
abstract class Forminator_Admin_Page {

	/**
	 * Current page ID
	 *
	 * @var number
	 */
	public $page_id = null;

	/**
	 * Current page slug
	 *
	 * @var string
	 */
	protected $page_slug = '';

	/**
	 * Path to view folder
	 *
	 * @var string
	 */
	protected $folder = '';

	/**
	 * Forminator_Admin_Page Constructor
	 *
	 * @since 1.0
	 *
	 * @param string $page_slug  Page slug.
	 * @param string $folder     Folder.
	 * @param string $page_title Page title.
	 * @param string $menu_title Menu title.
	 * @param bool   $parent_page Parent or not.
	 * @param bool   $render     Render the page.
	 */
	public function __construct(
		$page_slug,
		$folder,
		$page_title,
		$menu_title,
		$parent_page = false,
		$render = true
	) {
		$this->page_slug = $page_slug;
		$this->folder    = $folder;

		if ( ! $parent_page ) {
			$this->page_id = add_menu_page(
				$page_title,
				$menu_title,
				forminator_get_permission( $page_slug ),
				$page_slug,
				$render ? array( $this, 'render' ) : null,
				$this->get_menu_icon()
			);
		} else {
			$this->page_id = add_submenu_page(
				$parent_page,
				$page_title,
				$menu_title,
				forminator_get_permission( $page_slug ),
				$page_slug,
				$render ? array( $this, 'render' ) : null
			);
		}

		if ( $render ) {
			$this->render_page_hooks();
		}

		$this->init();

		add_filter( 'removable_query_args', array( $this, 'remove_notice_params' ) );
	}

	/**
	 * Use that method instead of __construct
	 *
	 * @todo  : deperecate this, since its not correct way to execute action on page,
	 * instead this function will executed everywhere on all pages,
	 *        unless you are really wanna do that?!
	 *
	 * @since 1.0
	 */
	public function init() {
	}

	/**
	 * Hooks before content render
	 *
	 * @since 1.0
	 */
	public function render_page_hooks() {
		add_action( 'load-' . $this->page_id, array( $this, 'before_render' ) );
		add_action( 'load-' . $this->page_id, array( $this, 'trigger_before_render_action' ) );
		add_filter( 'load-' . $this->page_id, array( $this, 'add_page_hooks' ) );

		if ( $this->is_edit_page() ) {
			add_filter( 'forminator_data', array( $this, 'add_forminator_data' ) );
			// Suppress admin notices on edit page.
			add_action( 'in_admin_header', array( $this, 'suppress_wp_admin_notices' ), PHP_INT_MAX );
		}
	}

	/**
	 * Suppress WP admin notices.
	 *
	 * @return void
	 */
	public function suppress_wp_admin_notices() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
	}

	/**
	 * Check if current page is edit page
	 *
	 * @return bool
	 */
	public function is_edit_page() {
		global $plugin_page;
		return ! empty( $plugin_page ) && $this->page_slug === $plugin_page &&
			in_array( $this->page_slug, array( 'forminator-cform-wizard', 'forminator-poll-wizard', 'forminator-knowledge-wizard', 'forminator-nowrong-wizard' ), true );
	}

	/**
	 * Add logo info in Forminator data.
	 *
	 * @param array $data Data.
	 *
	 * @return array
	 */
	public function add_forminator_data( $data ) {
		$data['statusBarLogoClass'] = $this->get_box_summary_classes();

		return $data;
	}

	/**
	 * Return page slug
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_the_slug() {
		return $this->page_slug;
	}

	/**
	 * Called when page is loaded and content not rendered yet
	 *
	 * @since 1.0
	 */
	public function before_render() {
	}

	/**
	 * Trigger an action before this screen is rendered
	 *
	 * @since 1.0
	 */
	public function trigger_before_render_action() {
		do_action( 'forminator_loaded_admin_page_' . $this->get_the_slug() );
	}

	/**
	 * Add page screen hooks
	 *
	 * @since 1.0
	 */
	public function add_page_hooks() {
		$page_action = filter_input( INPUT_GET, 'page_action' );
		if ( ! forminator_cloud_templates_disabled() && 'hub_connection' === $page_action
				&& ! Forminator_Hub_Connector::hub_connector_connected() ) {
			return;
		}
		add_filter( 'user_can_richedit', '__return_true' ); // Confirms wp editor script is loaded on Forminator admin pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_classes' ) );
		add_action( 'init', array( $this, 'init_scripts' ) );
	}

	/**
	 * Remove Get parameters for Forminator notices
	 *
	 * @param string[] $vars An array of query variables to remove from a URL.
	 * @return array
	 */
	public function remove_notice_params( $vars ) {
		$vars[] = 'forminator_notice';
		$vars[] = 'forminator_text_notice';
		$vars[] = 'forminator_error_notice';
		$vars[] = 'forminator_open_addon';
		$vars[] = 'forminator_install_addon';
		$vars[] = 'page_referral';
		if ( Forminator_Hub_Connector::hub_connector_connected() ) {
			$vars[] = 'page_action';
			$vars[] = 'feature';
		}

		return $vars;
	}

	/**
	 * Add page screen hooks
	 *
	 * @since 1.0
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_scripts( $hook ) {
		// Load admin scripts.
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'requirejs/main.js',
			array(
				'backbone',
				'underscore',
				'jquery',
				'wp-color-picker',
			),
			FORMINATOR_VERSION,
			true
		);
		forminator_common_admin_enqueue_scripts();
	}

	/**
	 * Init Admin scripts
	 *
	 * @since 1.0
	 *
	 * @param string $hook Hook name.
	 */
	public function init_scripts( $hook ) {
		// Init jquery ui.
		forminator_admin_jquery_ui_init();
	}

	/**
	 * Render page header
	 *
	 * @since 1.0
	 */
	protected function render_header() {
		$this->show_css_warning();
		$this->show_stripe_migration_notice();

		if ( $this->template_exists( $this->folder . '/header' ) ) {
			$this->template( $this->folder . '/header' );
		} else {
			?>
			<header class="sui-header">
				<h1 class="sui-header-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			</header>
			<?php
		}
	}

	/**
	 * Render page footer
	 *
	 * @since 1.0
	 */
	protected function render_footer() {
		$hide_footer = false;
		$footer_text = sprintf(
		/* translators: %s: SUI Icon . */
			esc_html__( 'Made with %s by WPMU DEV', 'forminator' ),
			' <i class="sui-icon-heart"></i>'
		);

		$hide_footer = apply_filters( 'wpmudev_branding_change_footer', $hide_footer );
		$footer_text = apply_filters( 'wpmudev_branding_footer_text', $footer_text );

		if ( $this->template_exists( $this->folder . '/footer' ) ) {
			$this->template( $this->folder . '/footer' );
		}
		?>
		<div class="sui-footer"><?php echo wp_kses_post( $footer_text ); ?></div>

		<?php
		do_action( 'forminator_before_admin_footer_links', $hide_footer );

		if ( ! FORMINATOR_PRO ) {
			?>

			<ul class="sui-footer-nav">
				<li><a href="https://profiles.wordpress.org/wpmudev#content-plugins" target="_blank"><?php esc_html_e( 'Free Plugins', 'forminator' ); ?></a></li>
				<li><a href="https://wpmudev.com/features/" target="_blank"><?php esc_html_e( 'Membership', 'forminator' ); ?></a></li>
				<li><a href="https://wpmudev.com/roadmap/" target="_blank"><?php esc_html_e( 'Roadmap', 'forminator' ); ?></a></li>
				<li><a href="https://wordpress.org/support/plugin/forminator" target="_blank"><?php esc_html_e( 'Support', 'forminator' ); ?></a></li>
				<li><a href="https://wpmudev.com/docs/" target="_blank"><?php esc_html_e( 'Docs', 'forminator' ); ?></a></li>
				<li><a href="https://wpmudev.com/hub-welcome/" target="_blank"><?php esc_html_e( 'The Hub', 'forminator' ); ?></a></li>
				<li><a href="https://wpmudev.com/terms-of-service/" target="_blank"><?php esc_html_e( 'Terms of Service', 'forminator' ); ?></a></li>
				<li><a href="https://incsub.com/privacy-policy/" target="_blank"><?php esc_html_e( 'Privacy Policy', 'forminator' ); ?></a></li>
			</ul>

		<?php } ?>

		<?php if ( ! $hide_footer ) : ?>
			<ul class="sui-footer-social">
				<li><a href="https://www.facebook.com/wpmudev" target="_blank">
					<i class="sui-icon-social-facebook" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Facebook', 'forminator' ); ?></span>
				</a></li>
				<li><a href="https://twitter.com/wpmudev" target="_blank">
					<i class="sui-icon-social-twitter" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'X', 'forminator' ); ?></span>
				</a></li>
				<li><a href="https://www.instagram.com/wpmu_dev/" target="_blank">
					<i class="sui-icon-instagram" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Instagram', 'forminator' ); ?></span>
				</a></li>
			</ul>
		<?php endif; ?>

		<?php
	}

	/**
	 * Render page container
	 *
	 * @since 1.0
	 */
	public function render() {

		$accessibility_enabled = get_option( 'forminator_enable_accessibility', false );
		?>

		<main id="<?php echo esc_attr( 'wpmudev-page-' . $this->page_slug ); ?>" class="sui-wrap <?php echo $accessibility_enabled ? 'sui-color-accessible' : ''; ?> <?php echo esc_attr( 'wpmudev-forminator-' . $this->page_slug ); ?>">

			<?php

			$page_action    = filter_input( INPUT_GET, 'page_action' );
			$hub_connecting = ! forminator_cloud_templates_disabled() && 'hub_connection' === $page_action;
			if ( $hub_connecting && ! Forminator_Hub_Connector::hub_connector_connected() ) {
				do_action( 'wpmudev_hub_connector_ui', 'forminator' );
			} else {
				if ( ! $this->is_edit_page() ) {
					$this->render_header();
				}

				$this->render_page_content();

				if ( ! $this->is_edit_page() ) {
					$this->render_footer();
				}

				if ( $hub_connecting && Forminator_Hub_Connector::hub_connector_connected() ) {
					self::hub_connected_successfully_modal();
				}
			}
			?>

		</main>

		<?php
	}

	/**
	 * Show modal when hub connected successfully
	 */
	public static function hub_connected_successfully_modal() {
		$page    = filter_input( INPUT_GET, 'page' );
		$feature = filter_input( INPUT_GET, 'feature' );
		?>
		<div class="sui-modal sui-modal-md">
			<div
				role="dialog"
				id="forminator-hub-connected-successfully-modal"
				class="sui-modal-content forminator-hub-modal"
				aria-modal="true"
				aria-labelledby="forminator-hub-connected-successfully-modal-title"
				aria-describedby="forminator-hub-connected-successfully-modal-description"
				data-esc-close="true"
			>
				<div class="sui-box">
					<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
						<span class="sui-icon-check-tick sui-success sui-xl" aria-hidden="true"></span>
						<h3 id="forminator-hub-connected-successfully-modal-title" class="sui-box-title sui-lg">
							<?php esc_html_e( 'Site connected successfully!', 'forminator' ); ?>
						</h3>
						<p id="forminator-hub-connected-successfully-modal-description" class="sui-description">
							<?php
							if ( 'forminator-settings' === $page ) {
								esc_html_e( 'Your site is now connected to your WPMU DEV account, unlocking powerful tools to help you work faster and manage forms across sites, including:', 'forminator' );
							} else {
								?>
								<b><?php esc_html_e( 'Congratulations!', 'forminator' ); ?></b>
								<?php
								if ( 'preset-template' === $feature ) {
									esc_html_e( 'Your site is now connected to The Hub, and preset templates are unlocked. Start building forms faster with any of our ready-made templates.', 'forminator' );
								} elseif ( 'extension-pack' === $feature ) {
									esc_html_e( 'Your site is now connected to The Hub, and the following features are now unlocked.', 'forminator' );
								} else {
									esc_html_e( 'Your site is connected to The Hub. You can now save your forms to The Hub cloud.', 'forminator' );
								}
							}
							?>
						</p>
					</div>
					<?php
					if ( 'forminator-settings' === $page ) {
						?>
						<div class="sui-box-body sui-flatten">
							<div class="sui-notice">
								<div class="sui-notice-content">
									<div class="sui-notice-message">
										<ul>
											<li class="sui-icon-check-tick"><?php esc_html_e( 'Save form templates to the cloud for easy reuse', 'forminator' ); ?></li>
											<li class="sui-icon-check-tick"><?php esc_html_e( 'Access your cloud templates from any connected site', 'forminator' ); ?></li>
											<li class="sui-icon-check-tick"><?php esc_html_e( 'Discover additional ready-made form templates', 'forminator' ); ?></li>
											<li class="sui-icon-check-tick"><?php esc_html_e( 'Manage your site using The Hub’s site management tools', 'forminator' ); ?></li>
											<li class="sui-icon-check-tick"><?php printf( /* translators: 1: Open Italic tag, 2: Close Italic tag */ esc_html__( 'Enable add-ons via The Hub %1$s(coming soon)%2$s', 'forminator' ), '<i>', '</i>' ); ?></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<?php
					} elseif ( 'extension-pack' === $feature ) {
						?>
						<div class="sui-box-body">
							<div class="sui-box-body-item forminator-hub-modal-item">
								<div class="forminator-hub-modal-box">
									<span class="sui-icon-check-tick sui-sm sui-success" aria-hidden="true"></span>
									<strong>
										<?php esc_html_e( 'Preset and Cloud Templates', 'forminator' ); ?>
									</strong>
									<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Unlocked', 'forminator' ); ?></span>
								</div>
							</div>
							<div class="sui-box-body-item forminator-hub-modal-item">
								<div class="forminator-hub-modal-box">
									<span class="sui-icon-check-tick sui-sm sui-success" aria-hidden="true"></span>
									<strong>
										<?php esc_html_e( 'Extension Pack Add-on', 'forminator' ); ?>
									</strong>
									<button
										class="addons-actions sui-button sui-button-blue sui-button-sm"
										data-action="addons-install-activate"
										data-addon="<?php echo esc_attr( Forminator_Admin_Addons_Page::EXTENSION_PACK_PID ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ); ?>"
									>
										<span class="sui-icon-download" aria-hidden="true"></span>
										<?php esc_html_e( 'Install & Activate', 'forminator' ); ?>
									</button>
								</div>

								<ul class="forminator-hub-modal-list">
									<li>
										<span class="sui-icon-check sui-sm sui-success" aria-hidden="true"></span>
										<strong><?php esc_html_e( 'Form Abandonment', 'forminator' ); ?></strong>
									</li>
									<li>
										<span class="sui-icon-clock sui-sm sui-success" aria-hidden="true"></span>
										<strong><?php esc_html_e( 'More features coming soon', 'forminator' ); ?></strong>
									</li>
								</ul>
							</div>
						</div>
					<?php } ?>
					<div class="sui-box-footer sui-flatten sui-content-center">
						<button class="sui-button forminator-close-hub-connected-modal" data-feature="<?php echo esc_attr( $feature ); ?>" data-modal-close>
							<?php esc_html_e( 'Close', 'forminator' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render actual page template
	 *
	 * @since 1.0
	 */
	protected function render_page_content() {
		$this->template( $this->folder . '/content' );
	}

	/**
	 * Load an admin template
	 *
	 * @since 1.0
	 *
	 * @param string $path Template path.
	 * @param array  $args Arguments.
	 * @param bool   $echo_content Echo.
	 *
	 * @return string
	 */
	public function template( $path, $args = array(), $echo_content = true ) {
		$file    = forminator_plugin_dir() . "admin/views/$path.php";
		$content = '';

		if ( is_file( $file ) ) {
			ob_start();

			if ( isset( $args['id'] ) ) {
				$template_class  = $args['class'];
				$template_id     = $args['id'];
				$title           = $args['title'];
				$header_callback = $args['header_callback'];
				$main_callback   = $args['main_callback'];
				$footer_callback = $args['footer_callback'];
			}

			include $file;

			$content = ob_get_clean();
		}

		if ( $echo_content ) {
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $content;
	}

	/**
	 * Check if template exist
	 *
	 * @since 1.0
	 *
	 * @param string $path Template path.
	 *
	 * @return bool
	 */
	protected function template_exists( $path ) {
		$file = forminator_plugin_dir() . "admin/views/$path.php";

		return is_file( $file );
	}

	/**
	 * Generates the admin body class required for WPMU DEV Shared UI
	 *
	 * @since 1.0.2
	 * @return string $sui_body_class
	 */
	public function get_sui_body_class() {
		$sanitize_version = str_replace( '.', '-', FORMINATOR_SUI_VERSION );
		$sui_body_class   = "sui-$sanitize_version";

		return $sui_body_class;
	}

	/**
	 * Add admin body classes
	 *
	 * @since 1.0.2
	 *
	 * @param string $classes Classes.
	 *
	 * @return string $classes
	 */
	public function admin_body_classes( $classes ) {

		$screen = get_current_screen();

		// Do nothing if not a forminator page.
		if ( strpos( $screen->base, '_page_forminator' ) === false ) {
			return $classes;
		}

		$classes = $this->get_sui_body_class();

		// if accessibility enabled add sui select accessible class.
		if ( get_option( 'forminator_enable_accessibility', false ) ) {
			$classes .= ' sui-elements-accessible';
		}

		return $classes;
	}

	/**
	 * Get admin page param
	 *
	 * @since 1.5.4
	 * @return string
	 */
	protected function get_admin_page() {
		return Forminator_Core::sanitize_text_field( 'page' );
	}

	/**
	 * Redirect to referer if available
	 *
	 * @since 1.6
	 *
	 * @param string $fallback_redirect url if referer not found.
	 * @param bool   $to_referer Referer.
	 */
	protected function maybe_redirect_to_referer( $fallback_redirect = '', $to_referer = true ) {
		$referer = wp_get_referer();
		$referer = ! empty( $referer ) ? $referer : wp_get_raw_referer();
		$referer = remove_query_arg( array( 'export', 'delete', 'forminator_notice', 'forminator_text_notice', 'forminator_error_notice' ), $referer );

		if ( $referer && $to_referer ) {
			wp_safe_redirect( $referer );
		} elseif ( $fallback_redirect ) {
			wp_safe_redirect( $fallback_redirect );
		} else {
			$admin_url = admin_url( 'admin.php' );
			$admin_url = add_query_arg(
				array(
					'page' => $this->get_admin_page(),
				),
				$admin_url
			);
			wp_safe_redirect( $admin_url );
		}

		exit();
	}

	/**
	 * Get css class used for box summary on admin page
	 *
	 * @since 1.6
	 * @return string
	 */
	public function get_box_summary_classes() {
		$classes = '';
		if ( Forminator::is_wpmudev_member() ) {
			$hide_branding         = false;
			$hide_branding         = apply_filters( 'wpmudev_branding_hide_branding', $hide_branding );
			$custom_branding_image = '';
			$custom_branding_image = apply_filters( 'wpmudev_branding_hero_image', $custom_branding_image );
			if ( $hide_branding && ! empty( $custom_branding_image ) ) {
				$classes .= ' sui-rebranded';
			} elseif ( $hide_branding && empty( $custom_branding_image ) ) {
				$classes .= ' sui-unbranded';
			}
		}

		return $classes;
	}

	/**
	 * Get image url for summary box
	 *
	 * @since 1.6
	 * @return string
	 */
	public function get_box_summary_image_url() {
		$image_url = '';
		if ( Forminator::is_wpmudev_member() ) {
			$image_url = apply_filters( 'wpmudev_branding_hero_image', $image_url );
		}

		return $image_url;
	}

	/**
	 * Get inline style for box summary-image div
	 *
	 * @since 1.6
	 * @return string
	 */
	public function get_box_summary_image_style() {
		$image_url = $this->get_box_summary_image_url();
		if ( ! empty( $image_url ) ) {
			return 'background-image:url(' . esc_url( $image_url ) . ')';
		}

		return '';
	}

	/**
	 * Show migration notice for stripe
	 *
	 * @return void
	 */
	public function show_stripe_migration_notice() {
		$active_forms = Forminator_Form_Model::model()->get_models_with_old_stripe();
		if ( ! $active_forms ) {
			return;
		}
		?>


		<div
			role="alert"
			class="sui-notice sui-notice-yellow sui-active"
			style="display: block; text-align: left;"
			aria-live="assertive"
		>

			<div class="sui-notice-content">

				<div class="sui-notice-message">

					<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

					<p>
						<b><?php esc_html_e( 'Migrate to new Forminator Stripe field', 'forminator' ); ?></b>
					</p>
					<p>
						<?php
						$stripe_link = 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#stripe-field';
						printf(
							/* Translators: 1. 'Learn more' link */
							esc_html__( 'You are using the Stripe simple card payment element, which is being deprecated by Stripe. %1$sTo ensure seamless transactions, update the Stripe field in the form(s) below to the new Stripe payment element.', 'forminator' ),
							forminator_is_show_documentation_link() ?
								'<a href="' . esc_url( $stripe_link ) . '" target="_blank">' . esc_html__( 'Learn more.', 'forminator' ) . '</a> '
								: ''
						);
						?>
					</p>

					<?php foreach ( $active_forms as $form ) : ?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-cform-wizard&id=' . intval( $form['id'] ) ) ); ?>"
								class="">
								<?php echo esc_html( $form['title'] ); ?>
								<i class="sui-icon-pencil" aria-hidden="true"></i>
							</a>
						</li>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Show warning if frontend is loaded in https but the WordPress address url setting uses http only
	 *
	 * @since 1.15.1
	 */
	public function show_css_warning() {
		$home_url        = wp_parse_url( home_url() );
		$site_url_option = wp_parse_url( get_option( 'siteurl' ) ); // WordPress Address (URL).

		if (
			( 'https' === $home_url['scheme'] && 'https' === $site_url_option['scheme'] ) ||
			( 'http' === $home_url['scheme'] && 'http' === $site_url_option['scheme'] )
		) {
			return;
		}
		?>
			<div
				role="alert"
				class="sui-notice sui-notice-yellow sui-active"
				style="display: block; text-align: left;"
				aria-live="assertive"
			>

				<div class="sui-notice-content">

					<div class="sui-notice-message">

						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

						<p><?php esc_html_e( 'Forminator\'s CSS style cannot be loaded because your website\'s address is configured in WordPress to use HTTP instead of HTTPS. This may cause some web content, including Forminator forms, to display incorrectly.', 'forminator' ); ?></p>

					</div>

				</div>

			</div>
		<?php
	}

	/**
	 * Forminator icon svg image.
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M12.5067 1.79874H16.2222C16.6937 1.79874 17.1459 1.99053 17.4793 2.33187C17.8127 2.67321 18 3.13614 18 3.61887V18.1799C18 18.6626 17.8127 19.1255 17.4793 19.4669C17.1459 19.8082 16.6937 20 16.2222 20H3.77778C3.30628 20 2.85412 19.8082 2.52072 19.4669C2.18733 19.1255 2 18.6626 2 18.1799V3.61887C2 3.13614 2.18733 2.67321 2.52072 2.33187C2.85412 1.99053 3.30628 1.79874 3.77778 1.79874H7.49333C7.68017 1.27168 8.02098 0.816284 8.46946 0.494469C8.91793 0.172654 9.45234 0 10 0C10.5477 0 11.0821 0.172654 11.5305 0.494469C11.979 0.816284 12.3198 1.27168 12.5067 1.79874ZM10.4938 1.9521C10.3476 1.8521 10.1758 1.79874 10 1.79874C9.76425 1.79874 9.53817 1.89464 9.37147 2.0653C9.20477 2.23597 9.11111 2.46744 9.11111 2.7088C9.11111 2.8888 9.16323 3.06472 9.2609 3.21438C9.35858 3.36404 9.49741 3.48072 9.65983 3.5496C9.82225 3.61848 10.001 3.63648 10.1734 3.60137C10.3458 3.56625 10.5042 3.47958 10.6285 3.3523C10.7528 3.22503 10.8375 3.06286 10.8718 2.88633C10.9061 2.70979 10.8885 2.52682 10.8212 2.36053C10.754 2.19424 10.64 2.0521 10.4938 1.9521ZM3.77778 3.61887V18.1799H16.2222V3.61887H13.5556V5.43899H6.44444V3.61887H3.77778ZM6.44442 10.8987H13.5555C13.7913 10.8987 14.0174 10.9946 14.1841 11.1653C14.3508 11.3359 14.4444 11.5674 14.4444 11.8087C14.4444 12.0501 14.3508 12.2816 14.1841 12.4522C14.0174 12.6229 13.7913 12.7188 13.5555 12.7188H6.44442C6.20867 12.7188 5.98259 12.6229 5.8159 12.4522C5.6492 12.2816 5.55553 12.0501 5.55553 11.8087C5.55553 11.5674 5.6492 11.3359 5.8159 11.1653C5.98259 10.9946 6.20867 10.8987 6.44442 10.8987ZM13.5555 8.16849H6.44442C6.20867 8.16849 5.98259 8.26438 5.8159 8.43505C5.6492 8.60572 5.55553 8.83719 5.55553 9.07855C5.55553 9.31992 5.6492 9.55138 5.8159 9.72205C5.98259 9.89272 6.20867 9.98862 6.44442 9.98862H13.5555C13.7913 9.98862 14.0174 9.89272 14.1841 9.72205C14.3508 9.55138 14.4444 9.31992 14.4444 9.07855C14.4444 8.83719 14.3508 8.60572 14.1841 8.43505C14.0174 8.26438 13.7913 8.16849 13.5555 8.16849ZM10 13.6289H13.5556C13.7913 13.6289 14.0174 13.7248 14.1841 13.8954C14.3508 14.0661 14.4444 14.2976 14.4444 14.5389C14.4444 14.7803 14.3508 15.0118 14.1841 15.1824C14.0174 15.3531 13.7913 15.449 13.5556 15.449H10C9.76425 15.449 9.53817 15.3531 9.37148 15.1824C9.20478 15.0118 9.11111 14.7803 9.11111 14.5389C9.11111 14.2976 9.20478 14.0661 9.37148 13.8954C9.53817 13.7248 9.76425 13.6289 10 13.6289Z" fill="#F0F6FC"/>
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 encode the img content.
	}

	/**
	 * Forminator modules
	 */
	public function populate_modules() {

		$modules[] = array(
			'name'  => esc_html__( 'Forms', 'forminator' ),
			'model' => Forminator_Form_Model::model(),
			'slug'  => 'form',
		);
		$modules[] = array(
			'name'  => esc_html__( 'Polls', 'forminator' ),
			'model' => Forminator_Poll_Model::model(),
			'slug'  => 'poll',
		);
		$modules[] = array(
			'name'  => esc_html__( 'Quizzes', 'forminator' ),
			'model' => Forminator_Quiz_Model::model(),
			'slug'  => 'quiz',
		);

		return $modules;
	}

	/**
	 * Modules form type
	 *
	 * @return array
	 */
	public function modules_form_type() {
		$form_types = array();
		$modules    = $this->populate_modules();
		foreach ( $modules as $module ) {
			/**
			 * Forminator_Base_Form_Model
			 *
			 * @var Forminator_Base_Form_Model $model */
			$model = $module['model'];
			$name  = $module['name'];

			$form_types[ $model->get_post_type() ] = $name;
		}

		return $form_types;
	}

	/**
	 * Get Form Model if current requested form_id available and matched form_type
	 *
	 * @return bool|Forminator_Base_Form_Model|null
	 */
	public function get_form_model() {
		if ( $this->get_current_form_id() ) {
			$form_model = forminator_get_model_from_id( $this->get_current_form_id() );
			if ( ! $form_model instanceof Forminator_Base_Form_Model ) {
				return null;
			}
			if ( $form_model->get_post_type() !== $this->get_current_form_type() ) {
				return null;
			}

			return $form_model;
		}

		return null;
	}
}
