<?php
/**
 * Forminator Settings Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Settings_Page
 *
 * @since 1.0
 */
class Forminator_Settings_Page extends Forminator_Admin_Page {

	/**
	 * Addons data that will be sent to settings page
	 *
	 * @var array
	 */
	private $addons_data = array();

	/**
	 * Addon list
	 *
	 * @var array
	 */
	public $addons_list = array();

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		$this->process_request();
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Hook name.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		add_filter( 'forminator_data', array( $this, 'change_forminator_data' ) );
		parent::enqueue_scripts( $hook );
		wp_localize_script( 'forminator-admin', 'forminator_addons_data', $this->addons_data );

		wp_enqueue_script(
			'forminator-settings-page',
			forminator_plugin_url() . 'build/settings.js',
			array(
				'jquery',
				'wp-color-picker',
				'react',
				'react-dom',
				'wp-element',
			),
			FORMINATOR_VERSION,
			true
		);
	}

	/**
	 * Add select forms for creating new Appearance Preset
	 *
	 * @param array $data Data.
	 * @return array
	 */
	public function change_forminator_data( $data ) {
		ob_start();
		Forminator_Entries_Page::render_form_switcher();
		$forms_select         = ob_get_clean();
		$data['forms_select'] = $forms_select;
		$data['presetNonce']  = wp_create_nonce( 'forminator_appearance_preset' );
		$preset_id            = Forminator_Core::sanitize_text_field( 'preset' );
		if ( empty( $preset_id ) ) {
			$preset_id = 'default';
		}
		$data['currentPreset'] = self::get_preset_settings( $preset_id );

		return $data;
	}

	/**
	 * Add new item in presets list.
	 *
	 * @param string $id ID.
	 * @param string $name Name.
	 */
	public static function save_preset_list( $id, $name ) {
		$key                 = 'forminator_appearance_presets';
		$preset_names        = get_option( $key, array() );
		$preset_names[ $id ] = $name;
		update_option( $key, $preset_names );
	}

	/**
	 * Get preset settings for apply to a form
	 *
	 * @param string $preset_id Preset ID.
	 * @return array
	 */
	public static function get_preset( $preset_id ) {
		$data = self::get_preset_settings( $preset_id );

		return $data['settings'];
	}

	/**
	 * Get preset settings.
	 *
	 * @param string $id ID.
	 * @return array
	 */
	private static function get_preset_settings( $id ) {
		$settings = get_option( 'forminator_appearance_preset_' . $id, array() );

		if ( empty( $settings ) ) {
			$settings = Forminator_Custom_Form_Admin::get_default_settings( '' );
		}

		$data = array(
			'settings' => self::only_appearance_settings( $settings ),
			'wrappers' => null, // to show all possible settings.
		);

		return $data;
	}

	/**
	 * Leave only Appearance settings
	 *
	 * @param array $all_settings All settings.
	 * @return array
	 */
	public static function only_appearance_settings( $all_settings ) {
		// todo: refactor - it's better to use separate array `appearance` in the modal settings,
		// which includes all appearance settings. And then we can remove this method.
		$consist = array(
			'-font-family',
			'-custom-family',
			'-font-size',
			'-font-weight',
			'-border',
			'-background',
			'-color',
			'-placeholder',
			'-bg',
			'-icon',
			'-label',
			'spacing',
			'-static',
			'-active',
			'-hover',
			'multiupload-',
			'calendar-dweek',
			'timeline-text',
		);
		$props   = array(
			'form-style',
			'form-substyle',

			'cform-color-settings',

			'form-font-family',

			'form-padding',
			'form-padding-top',
			'form-padding-bottom',
			'form-padding-left',
			'form-padding-right',

			'form-border',
			'form-border-radius',
			'form-border-width',
			'form-border-style',

			'field-border',
			'field-border-radius',
			'field-border-width',

			'fields-style',

			'field-image-size',
			'field-image-width',
			'field-image-height',

			'use-custom-css',
			'custom_css',

			'slider-track',
			'slider-track-fill',
			'slider-handle',
			'slider-selected-value',
			'slider-steps-value',
			'slider-handle-drag',
		);

		$settings = array();

		foreach ( $all_settings as $key => $value ) {
			$apply = false;
			if ( in_array( $key, $props, true ) || 'basic-' === substr( $key, 0, 6 ) ) {
				$apply = true;
			}
			if ( ! $apply ) {
				foreach ( $consist as $end ) {
					if ( false !== strpos( $key, $end ) ) {
						$apply = true;
						break;
					}
				}
			}

			if ( ! $apply ) {
				continue;
			}

			$settings[ $key ] = $value;
		}

		return apply_filters( 'forminator_only_appearance_settings', $settings, $all_settings );
	}

	/**
	 * Get Appearance Preset names
	 *
	 * @return array
	 */
	public static function get_preset_names() {
		$preset_names = get_option( 'forminator_appearance_presets', array() );
		if ( ! isset( $preset_names['default'] ) ) {
			self::save_preset_list( 'default', esc_html__( 'Default Preset', 'forminator' ) );
			$preset_names = get_option( 'forminator_appearance_presets', array() );
		}

		return $preset_names;
	}

	/**
	 * Get Appearance preset selectbox HTML
	 *
	 * @return string
	 */
	public static function get_preset_selectbox() {
		$presets = self::get_preset_names();

		ob_start();
		echo '<select name="appearance_preset" class="sui-select">';

		foreach ( $presets as $key => $peset_name ) {
			echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $peset_name ) . '</option>';
		}

		echo '</select>';

		$select = ob_get_clean();

		return $select;
	}

	/**
	 * Before render
	 *
	 * @return void
	 */
	public function before_render() {
		if ( Forminator::is_addons_feature_enabled() ) {
			$this->prepare_addons();
		}

		// Add js data for Permissions.
		add_filter( 'forminator_data', array( $this, 'add_permissions_js_data' ) );
	}

	/**
	 * Prepare addons
	 *
	 * @return void
	 */
	private function prepare_addons() {
		// cleanup activated addons.
		Forminator_Integration_Loader::get_instance()->cleanup_activated_addons();

		Forminator_Integration_Admin_Ajax::get_instance()->generate_nonce();

		$addons_list = forminator_get_registered_addons_list();

		$this->addons_data = array(
			'addons_list' => $addons_list,
			'nonce'       => Forminator_Integration_Admin_Ajax::get_instance()->get_nonce(),
		);

		$this->addons_list = forminator_get_registered_addons_list();
	}

	/**
	 * Add js data
	 *
	 * @param mixed $data Data to add.
	 *
	 * @return mixed
	 */
	public function add_permissions_js_data( $data ) {
		if ( ! current_user_can( forminator_get_admin_cap() ) ) {
			return $data;
		}

		$permissions = get_option( 'forminator_permissions', array() );

		$data['mainSettings']     = array(
			'permissions' => $permissions,
			'modal'       => array(),
		);
		$data['permission_nonce'] = wp_create_nonce( 'forminator_permission_nonce' );

		return $data;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function process_request() {
		$nonce = Forminator_Core::sanitize_text_field( 'forminatorNonce' );
		if ( ! $nonce ) {
			return;
		}

		if ( ! wp_verify_nonce( $nonce, 'forminatorSettingsRequest' ) ) {
			return;
		}

		$is_redirect = true;
		$action      = Forminator_Core::sanitize_text_field( 'forminator_action' );
		switch ( $action ) {
			case 'reset_plugin_settings':
				forminator_reset_settings();
				$query_args = array(
					'section'           => 'data',
					'forminator_notice' => 'settings_reset',
				);
				break;
			case 'disconnect_stripe':
				if ( class_exists( 'Forminator_Gateway_Stripe' ) ) {
					Forminator_Gateway_Stripe::store_settings( array() );
				}
				break;
			case 'disconnect_paypal':
				if ( class_exists( 'Forminator_PayPal_Express' ) ) {
					Forminator_PayPal_Express::store_settings( array() );
				}
				break;
			default:
				break;
		}

		if ( $is_redirect ) {
			$to_referer = true;

			$args = array(
				'page' => $this->get_admin_page(),
			);
			if ( ! empty( $query_args ) ) {
				$args       = array_merge( $args, $query_args );
				$to_referer = false;
			}
			$fallback_redirect = add_query_arg(
				$args,
				admin_url( 'admin.php' )
			);

			$this->maybe_redirect_to_referer( $fallback_redirect, $to_referer );
		}

		exit;
	}
}
