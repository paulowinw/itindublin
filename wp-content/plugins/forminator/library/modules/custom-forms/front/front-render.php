<?php
/**
 * The Forminator_CForm_Front class.
 *
 * @package Forminator
 */

/**
 * Front render class for custom forms
 *
 * @since 1.0
 */
class Forminator_CForm_Front extends Forminator_Render_Form {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Forminator_PayPal_Express
	 *
	 * @var null|Forminator_PayPal_Express
	 */
	private static $paypal = null;

	/**
	 * Paypal forms
	 *
	 * @var array
	 */
	private static $paypal_forms = array();

	/**
	 * Inline rules
	 *
	 * @var string
	 */
	private $inline_rules = '';

	/**
	 * Inline messages
	 *
	 * @var string
	 */
	private $inline_messages = '';

	/**
	 * All wrappers
	 *
	 * @var array
	 */
	public static $all_wrappers = array();

	/**
	 * Parent groups
	 *
	 * @var array
	 */
	private $parent_groups;

	/**
	 * Model data
	 *
	 * @var Forminator_Form_Model
	 */
	public $lead_model = null;

	/**
	 * Load wp_enqueue_editor or not
	 *
	 * @var bool
	 */
	public static $load_wp_enqueue_editor = false;

	/**
	 * Return class instance
	 *
	 * @since 1.0
	 * @return Forminator_CForm_Front
	 */
	public static function get_instance() {
		return new self();
	}

	/**
	 * Whether font key should be applied to the current form or not.
	 *
	 * @param string $font_setting_key Font settings key.
	 * @return bool
	 */
	private function has( $font_setting_key ) {
		switch ( $font_setting_key ) {

			case 'timeline':
				return $this->has_field_type( 'page-break' ) && $this->has_pagination_header()
					&& 'nav' === $this->get_pagination_type();

			case 'progress':
				return $this->has_field_type( 'page-break' ) && $this->has_pagination_header()
					&& 'bar' === $this->get_pagination_type();

			case 'title':
			case 'subtitle':
				return $this->has_field_type( 'section' );

			case 'input-prefix':
				return $this->has_field_type( 'calculation' );

			case 'input-suffix':
				return $this->has_field_type( 'calculation' ) || $this->has_field_type( 'currency' );

			case 'radio':
				return $this->has_field_type_with_setting_value( 'checkbox', 'value_type', 'checkbox' )
					|| $this->has_field_type_with_setting_value( 'radio', 'value_type', 'radio' )
					|| $this->has_field_type( 'gdprcheckbox' );

			case 'select':
			case 'dropdown':
				return $this->has_field_type_with_setting_value( 'select', 'value_type', 'single' )
					|| $this->has_field_type_with_setting_value( 'date', 'field_type', 'select' )
					|| $this->has_field_type_with_setting_value( 'time', 'field_type', 'select' )
					|| $this->has_field_type_with_setting_value( 'time', 'time_type', 'twelve' )
					|| $this->has_field_type_with_setting_value( 'address', 'address_country', 'true' )
					|| $this->has_field_type_with_setting_value( 'name', 'prefix', 'true' );

			case 'calendar':
				return $this->has_field_type_with_setting_value( 'date', 'field_type', 'picker' );

			case 'multiselect':
				return $this->has_field_type_with_setting_value( 'select', 'value_type', 'multiselect' );

			case 'upload-single-button':
			case 'upload-single-text':
				return $this->has_field_type_with_setting_value( 'upload', 'file-type', 'single' )
					|| $this->has_field_type_with_setting_value( 'postdata', 'post_image', 'false' );

			case 'upload-multiple-panel':
			case 'upload-multiple-file-name':
			case 'upload-multiple-file-size':
				return $this->has_field_type_with_setting_value( 'upload', 'file-type', 'multiple' );

			case 'esign-placeholder':
				return $this->has_field_type( 'signature' );

			case 'repeater-button':
				return $this->has_field_type( 'group' );

			case 'pagination-buttons':
				return $this->has_field_type( 'page-break' );
		}

		return true;
	}

	/**
	 * Display form method
	 *
	 * @since 1.0
	 *
	 * @param int   $id Id.
	 * @param bool  $is_preview Is preview.
	 * @param bool  $data Data.
	 * @param bool  $hide If true, display: none will be added on the form markup and later removed with JS.
	 * @param array $quiz_model Quiz model.
	 */
	public function display( $id, $is_preview = false, $data = false, $hide = true, $quiz_model = null ) {
		if ( $data && ! empty( $data ) ) {
			$this->model = Forminator_Form_Model::model()->load_preview( $id, $data );
			// its preview!
			$this->model->id = $id;
		} else {
			$this->model = Forminator_Base_Form_Model::get_model( $id );

			if ( ! $this->model instanceof Forminator_Form_Model ) {
				return;
			}
		}

		if ( isset( $this->model->settings['form-type'] ) && 'leads' === $this->model->settings['form-type'] && is_null( $quiz_model ) ) {
			return;
		}

		$is_ajax_load = $this->is_ajax_load( $is_preview );

		if ( $quiz_model ) {
			$this->lead_model = $quiz_model;
			$is_ajax_load     = isset( $this->lead_model->settings['use_ajax_load'] ) ? $this->lead_model->settings['use_ajax_load'] : false;
		}

		$this->maybe_define_cache_constants();

		// TODO: make preview and ajax load working similar.

		// preview force using ajax.

		// hide login/registration form if a user is already logged in.
		$hide_form           = false;
		$hidden_form_message = false;
		if ( isset( $this->model->settings['form-type'] ) && in_array( $this->model->settings['form-type'], array( 'login', 'registration' ), true ) && is_user_logged_in() ) {
			// Option 'Is a form hide?'.
			$hide_option = 'hide-' . $this->model->settings['form-type'] . '-form';
			$hide_form   = ( isset( $this->model->settings[ $hide_option ] ) && '1' === $this->model->settings[ $hide_option ] ) ? true : false;
			// Display message if a form is hidden.
			$hide_form_message_option = 'hidden-' . $this->model->settings['form-type'] . '-form-message';
			$hidden_form_message      = isset( $this->model->settings[ $hide_form_message_option ] ) && ! empty( $this->model->settings[ $hide_form_message_option ] )
				? $this->model->settings[ $hide_form_message_option ]
				: false;
		}

		if ( ! $this->is_displayable( $is_preview ) ) {
			return;
		}

		$this->generate_render_id( $id );

		if ( $hide_form ) {
			if ( $hidden_form_message ) {
				echo wp_kses_post( $this->render_hidden_form_message( $hidden_form_message ) );
			}
			return;
		}

		if ( $is_ajax_load ) {
			if ( ! $this->lead_model ) {
				$this->get_form_placeholder( esc_attr( $id ), true );
			}
			$this->enqueue_form_scripts( $is_preview, $is_ajax_load );

			return;
		}

		echo $this->get_html( $hide, $is_preview, self::$render_ids[ $id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( is_admin() || $is_preview ) {
			$this->print_styles();
		}

		if ( $is_preview ) {
			$this->forminator_render_front_scripts();
		}

		$this->enqueue_form_scripts( $is_preview );
	}

	/**
	 * Header message to handle error message
	 *
	 * @param string $maybe_error Error message.
	 *
	 * @since 1.0
	 */
	public function render_form_header( $maybe_error = '' ) {
		// if rendered on Preview, the array is empty and sometimes PHP notices show up.
		if ( ! isset( self::$render_ids[ $this->model->id ] ) ) {
			self::$render_ids[ $this->model->id ] = 0;
		}

		ob_start();
		do_action( 'forminator_form_post_message', $this->model->id, self::$render_ids[ $this->model->id ] ); // prints html, so we need to capture this.
		$error = ob_get_clean();

		if ( ! empty( $error ) ) {
			return $error;
		}

		if ( empty( $maybe_error ) ) {
			$wrapper = '<div role="alert" aria-live="polite" class="forminator-response-message forminator-error" aria-hidden="true"></div>';
		} else {
			$wrapper = '<div role="alert" aria-live="polite" class="forminator-response-message forminator-error" aria-hidden="false">' . esc_html( $maybe_error ) . '</div>';
		}

		return $wrapper;
	}

	/**
	 * Footer handle
	 *
	 * @since 1.12
	 */
	public function render_form_authentication() {

		$wrapper = '';
		// These are unique IDs.
		$module_id = 'forminator-module-' . $this->model->id . '-authentication';
		$title_id  = $module_id . '-title';
		$input_id  = $module_id . '-input';
		$notice_id = $module_id . '-notice';
		$token_id  = $module_id . '-token';

		$form_type = isset( $this->model->settings['form-type'] ) ? $this->model->settings['form-type'] : '';

		if ( 'login' !== $form_type ) {
			return '';
		}

		if ( is_multisite() ) {
			$login_header_url   = network_home_url();
			$login_header_title = get_network()->site_name;
		} else {
			$login_header_url   = esc_html__( 'https://wordpress.org/', 'forminator' );
			$login_header_title = esc_html__( 'Powered by WordPress', 'forminator' );
		}

		$defender_data  = forminator_defender_compatibility();
		$settings       = $defender_data['two_fa_settings'];
		$custom_graphic = ! $defender_data['is_free'] && $settings->custom_graphic
			? $settings->custom_graphic_url
			: $defender_data['img_dir_url'] . '2factor-disabled.svg';

		$providers = $this->get_2FA_poviders();

		$wrapper .= '<div class="forminator-authentication">';

		$wrapper .= '<div role="dialog" id="' . esc_attr( $module_id ) . '" class="forminator-authentication-content" aria-modal="true" aria-labelledby="' . esc_attr( $title_id ) . '">';

		$wrapper .= '<h1 id="' . esc_attr( $title_id ) . '"><a href="' . esc_url( $login_header_url ) . '" title="' . esc_attr( $login_header_title ) . '" style="background-image: url(' . esc_url( $custom_graphic ) . ');">' . esc_html__( 'Authenticate to login', 'forminator' ) . '</a></h1>';

		$wrapper .= '<div role="alert" aria-live="polite" id="' . esc_attr( $notice_id ) . '" class="forminator-authentication-notice" data-error-message="' . esc_html__( 'The passcode was incorrect.', 'forminator' ) . '"></div>';

		foreach ( $providers as $slug => $provider ) {
			$wrapper .= '<div class="forminator-authentication-box" id="forminator-2fa-' . esc_attr( $slug ) . '">';

			ob_start();

			$provider->authentication_form();

			$wrapper .= ob_get_clean();

			$wrapper .= '</div>';
		}
		$wrapper .= '<input type="hidden" class="forminator-auth-method" name="auth_method" value="' . esc_attr( $slug ) . '" id="' . esc_attr( $input_id ) . '" disabled />';
		$wrapper .= '<input type="hidden" class="forminator-auth-token" name="auth_token" id="' . esc_attr( $token_id ) . '" />';
		$wrapper .= '<div class="forminator-wrap-nav">';
		$wrapper .= esc_html__( 'Having problems? Try another way to log in', 'forminator' );
		$wrapper .= '<ul class="forminator-authentication-nav">';
		foreach ( $providers as $slug => $provider ) {
			$wrapper .= '<li class="forminator-2fa-link" id="forminator-2fa-link-' . esc_attr( $slug ) . '" data-slug="' . esc_attr( $slug ) . '">';
			$wrapper .= $provider->get_login_label();
			$wrapper .= '</li>';
		}
		$wrapper .= '</ul>';
		$wrapper .= '<img class="def-ajaxloader" src="' . esc_url( $defender_data['img_dir_url'] ) . 'spinner.svg"/>';
		$wrapper .= '<strong class="notification"></strong>';
		$wrapper .= '</div>';
		global $interim_login;
		if ( ! $interim_login ) {
			/* translators: 1. Blog title. */
			$link_back_to = sprintf( _x( '&larr; Back to %s', 'back link', 'forminator' ), get_bloginfo( 'title', 'display' ) );
			$wrapper     .= '<p class="forminator-authentication-backtolog"><a class="auth-back" href="#">' . esc_html( $link_back_to ) . '</a></p>';
		}

		$wrapper .= '</div>';

		$wrapper .= '</div>';

		return $wrapper;
	}

	/**
	 * Enqueue form scripts
	 *
	 * @since 1.0
	 *
	 * @param bool $is_preview Is preview.
	 * @param bool $is_ajax_load Is ajax load.
	 */
	public function enqueue_form_scripts( $is_preview, $is_ajax_load = false ) {
		$is_ajax_load = $is_preview || $is_ajax_load;

		// Load assets conditionally.
		$assets = new Forminator_Assets_Enqueue_Form( $this->model, $is_ajax_load );
		$assets->enqueue_styles( $this );
		$assets->enqueue_scripts( $this );

		// Load reCaptcha scripts.
		if ( $this->has_captcha() ) {
			$first_captcha = $this->find_first_captcha();
			$language      = Forminator_Captcha::get_captcha_language( $first_captcha );
			$ver           = FORMINATOR_VERSION;

			// Check captcha provider.
			if ( $this->is_recaptcha() ) {
				$method_onload = 'forminator_render_captcha';
				$src           = 'https://www.google.com/recaptcha/api.js?hl=' . $language . '&onload=' . $method_onload . '&render=explicit';
				$script_tag    = 'forminator-google-recaptcha';
				$script_load   = 'grecaptcha';
			} elseif ( $this->is_turnstile() ) {
				$method_onload = 'forminator_render_turnstile';
				$src           = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=' . $method_onload . '&render=explicit';
				$script_tag    = 'forminator-turnstile';
				$script_load   = 'turnstile';
				$ver           = null;
			} else {
				$method_onload = 'forminator_render_hcaptcha';
				$src           = 'https://js.hcaptcha.com/1/api.js?hl=' . $language . '&onload=' . $method_onload . '&render=explicit&recaptchacompat=off';
				$script_tag    = 'forminator-hcaptcha';
				$script_load   = 'hcaptcha';
			}

			if ( ! $is_ajax_load ) {
				wp_enqueue_script(
					$script_tag,
					$src,
					array( 'jquery' ),
					$ver,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts[ $script_tag ] = array(
					'src'  => $src,
					'on'   => 'window',
					'load' => $script_load,
				);
				if ( $is_preview ) {
					$this->script .= '<script type="text/javascript">
					if ( window["' . $script_load . '"]) {
							' . $method_onload . '();
					}
					</script>';
				}
			}
		}

		// Load Stripe scripts.
		if ( $this->has_stripe() ) {
			$src = 'https://js.stripe.com/v3/';

			if ( ! $is_ajax_load ) {
				wp_enqueue_script(
					'forminator-stripe',
					$src,
					array( 'jquery' ),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts['forminator-stripe'] = array(
					'src'  => $src,
					'on'   => 'window',
					'load' => 'StripeCheckout',
				);
			}
		}

		// load int-tels.
		if ( $this->has_phone() ) {
			$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
			$style_version = '4.0.3';

			$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
			$script_version = FORMINATOR_VERSION;

			if ( $is_ajax_load ) {
				// load later via ajax to avoid cache.
				$this->styles['intlTelInput-forminator-css'] = array( 'src' => add_query_arg( 'ver', $style_version, $style_src ) );
				$this->scripts['forminator-intlTelInput']    = array(
					'src'  => add_query_arg( 'ver', $style_version, $script_src ),
					'on'   => '$',
					'load' => 'intlTelInput',
				);
			}
		}

		// Load Paypal scripts.
		if ( $this->has_paypal() ) {
			$paypal_src = $this->paypal_script_argument( 'https://www.paypal.com/sdk/js?enable-funding=venmo' );

			// If there is more than 1 paypal field in a page, even if it's ajax loaded, enqueue script as usual to prevent paypal button errors.
			if ( ! $is_ajax_load || forminator_count_field_type_in_page( 'paypal' ) > 1 ) {
				wp_enqueue_script(
					'forminator-paypal-' . $this->model->id,
					$paypal_src,
					array( 'jquery' ),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts[ 'forminator-paypal-' . $this->model->id ] = array(
					'src'  => $paypal_src,
					'on'   => 'window',
					'id'   => $this->model->id,
					'load' => 'PayPalCheckout',
				);
			}

			add_action( 'wp_footer', array( $this, 'print_paypal_scripts' ), 9999 );
		}

		if ( $this->has_repeater() ) {
			$src = forminator_plugin_url() . 'assets/js/front/front.repeater.js';

			if ( ! $is_ajax_load ) {
				wp_enqueue_script(
					'forminator-repeater',
					$src,
					array(),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts['forminator-repeater'] = array(
					'src'  => $src,
					'on'   => 'window',
					'load' => 'Repeater',
				);
			}
		}

		if ( $this->has_formatting() ) {
			$base_url                                      = forminator_plugin_url() . 'assets/js/library/';
			$this->scripts['forminator-inputmask']         = array(
				'src'   => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'inputmask.min.js' ),
				'on'    => 'window',
				'load'  => 'inputmask',
				'async' => false,
			);
			$this->scripts['forminator-jquery-inputmask']  = array(
				'src'   => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'jquery.inputmask.min.js' ),
				'on'    => 'window',
				'load'  => 'jquery-inputmask',
				'async' => false,
			);
			$this->scripts['forminator-inputmask-binding'] = array(
				'src'   => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'inputmask.binding.js' ),
				'on'    => 'window',
				'load'  => 'inputmask-binding',
				'async' => false,
			);
		}

		$this->load_jquery_styles( $is_ajax_load );

		// todo: solve this.
		// load buttons css.
		wp_enqueue_style( 'buttons' );

		if ( $this->has_postdata() || $this->has_editor() ) {
			if ( ( $is_ajax_load || self::$load_wp_enqueue_editor )
					&& function_exists( 'wp_enqueue_editor' ) ) {
				wp_enqueue_editor();
			}
		}

		// Load selected google font.
		$fonts        = $this->get_google_fonts();
		$loaded_fonts = array();
		foreach ( $fonts as $setting_name => $font_name ) {
			if ( ! empty( $font_name ) ) {
				if ( in_array( sanitize_title( $font_name ), $loaded_fonts, true ) ) {
					continue;
				}

				$google_font_url = add_query_arg(
					array( 'family' => $font_name ),
					'https://fonts.bunny.net/css'
				);

				if ( ! $is_ajax_load ) {
					wp_enqueue_style( 'forminator-font-' . sanitize_title( $font_name ), 'https://fonts.bunny.net/css?family=' . $font_name, array(), '1.0' );
				} else {
					// load later via ajax to avoid cache.
					$this->styles[ 'forminator-font-' . sanitize_title( $font_name ) . '-css' ] = array( 'src' => $google_font_url );
				}
				$loaded_fonts[] = sanitize_title( $font_name );
			}
		}

		/**
		 * Filter enqueue form styles
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->styles = apply_filters( 'forminator_enqueue_form_styles', $this->styles, $is_preview, $is_ajax_load );

		/**
		 * Filter enqueue form scripts
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->scripts = apply_filters( 'forminator_enqueue_form_scripts', $this->scripts, $is_preview, $is_ajax_load );

		/**
		 * Filter enqueue form inline script
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->script = apply_filters( 'forminator_enqueue_form_script', $this->script, $is_preview, $is_ajax_load );

		// Load Front Render Scripts.
		// render front script of form front end initialization.
		if ( ! $is_ajax_load ) {
			add_action( 'wp_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
		}

		// Render front end submission behavior scripts.
		if ( ! $this->model->is_ajax_submit() ) {
			add_action( 'wp_footer', array( $this, 'forminator_render_front_submission_behavior_scripts' ), 9999 );
		}

		add_action( 'admin_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
	}

	/**
	 * Load jQuery ui styles for fields for None and basic design style.
	 *
	 * @param bool $is_ajax_load Is it loading via AJAX.
	 **/
	private function load_jquery_styles( bool $is_ajax_load ): void {
		$design = $this->get_form_design();

		// Check if design is not none or basic.
		if ( 'none' !== $design && 'basic' !== $design ) {
			return;
		}

		// Check if slider and datepicker field exists.
		if ( ! $this->has_field_type( 'slider' ) && ! $this->has_field_type( 'date' ) ) {
			return;
		}

		$src                 = apply_filters( 'forminator_jquery_ui_css', forminator_plugin_url() . 'assets/jquery-ui/css/jquery-ui.min.css' );
		$version             = apply_filters( 'forminator_jquery_ui_css_version', '1' );
		$src_slider_divi     = apply_filters( 'forminator_jquery_ui_slider_css', forminator_plugin_url() . 'assets/css/jquery-ui-slider.builder_divi.min.css' );
		$version_slider_divi = apply_filters( 'forminator_jquery_ui__slider_css_version', '1' );
		$is_divi             = Forminator_Assets_Enqueue::is_divi_active_or_preview();

		if ( ! $src ) {
			return;
		}

		if ( ! $is_ajax_load ) {
			wp_enqueue_style( 'forminator-jquery-ui-styles', $src, array(), $version );

			if ( $is_divi ) {
				wp_enqueue_style( 'forminator-jquery-ui-slider-styles', $src_slider_divi, array(), $version_slider_divi );
			}
		} else {
			// load later via ajax to avoid cache.
			$this->styles['forminator-jquery-ui-styles'] = array( 'src' => add_query_arg( 'ver', $version, $src ) );

			if ( $is_divi ) {
				$this->styles['forminator-jquery-ui-slider-styles'] = array( 'src' => add_query_arg( 'ver', $version_slider_divi, $src_slider_divi ) );
			}
		}
	}

	/**
	 * PayPal Script url parameters
	 *
	 * @param string $script Script URL.
	 *
	 * @return string
	 */
	public function paypal_script_argument( $script ) {
		$paypal_setting = $this->get_paypal_properties();
		if ( ! empty( $paypal_setting ) ) {
			$arg           = array();
			$funding_array = array(
				'card',
				'credit',
				'bancontact',
				'blik',
				'eps',
				'giropay',
				'ideal',
				'mercadopago',
				'mybank',
				'p24',
				'sepa',
				'sofort',
				'venmo',
			);
			if ( 'live' === $paypal_setting['mode'] ) {
				$arg['client-id'] = $paypal_setting['live_id'];
			} else {
				$arg['client-id'] = esc_html( $paypal_setting['sandbox_id'] );
			}
			if ( ! empty( $paypal_setting['currency'] ) ) {
				$arg['currency'] = $paypal_setting['currency'];
			}
			if ( ! empty( $paypal_setting['locale'] ) ) {
				$arg['locale'] = str_replace( '-', '_', $paypal_setting['locale'] );
			}
			foreach ( $funding_array as $fund ) {
				if ( ! empty( $paypal_setting[ $fund ] ) ) {
					$funding[] = $fund;
				}
			}
			if ( ! empty( $funding ) ) {
				$arg['disable-funding'] = implode( ',', $funding );
			}
			if ( 'enable' === $paypal_setting['debug_mode'] ) {
				$arg['debug'] = 'true';
			}
			$script = add_query_arg( $arg, $script );
		}

		return $script;
	}

	/**
	 * Return form wrappers & fields
	 *
	 * @since 1.0
	 * @return array|mixed
	 */
	public function get_wrappers() {
		if ( is_object( $this->model ) ) {
			$wrappers          = $this->model->get_fields_grouped();
			$restricted_fields = array( 'page-break', 'paypal', 'stripe', 'stripe-ocs', 'signature', 'captcha', 'postdata', 'group' );
			foreach ( $wrappers as $key => $wrapper ) {
				if ( empty( $wrapper['parent_group'] ) ) {
					continue;
				}
				$field_types = wp_list_pluck( $wrapper['fields'], 'type' );
				if ( array_intersect( $field_types, $restricted_fields ) ) {
					// If a restricted fields in wrapper into Group field - move the wrapper outside of the Group.
					$wrappers[ $key ]['parent_group'] = '';
				}
			}

			return $wrappers;
		} else {
			return $this->message_not_found();
		}
	}

	/**
	 * Return form wrappers & fields
	 *
	 * @since 1.0
	 * @return array|mixed
	 */
	public function get_fields() {
		$fields   = array();
		$wrappers = $this->get_wrappers();

		// Fallback.
		if ( empty( $wrappers ) ) {
			return $fields;
		}

		foreach ( $wrappers as $wrapper ) {

			if ( ! isset( $wrapper['fields'] ) ) {
				return array();
			}

			foreach ( $wrapper['fields'] as $field ) {
				$field['parent_group'] = ! empty( $wrapper['parent_group'] ) ? $wrapper['parent_group'] : '';

				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Get submit field
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function get_submit_field() {
		$settings = $this->get_form_settings();
		if ( ! isset( $settings['submitData'] ) ) {
			$settings['submitData'] = array();
		}
		$defaults = array(
			'element_id' => 'submit',
			'type'       => 'submit',
			'conditions' => array(),
		);

		$submit_data = array_merge( $defaults, $settings['submitData'] );

		return $submit_data;
	}

	/**
	 * Get Pagination field
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function get_pagination_field() {
		$settings = $this->get_form_settings();

		if ( ! isset( $settings['paginationData'] ) ) {
			$settings['paginationData'] = array();
		}
		$defaults = array(
			'element_id' => 'pagination',
			'type'       => 'pagination',
			'conditions' => array(),
		);

		$submit_data = array_merge( $defaults, $settings['paginationData'] );

		return $submit_data;
	}

	/**
	 * Return before wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return mixed
	 */
	public function render_wrapper_before( $wrapper ) {
		$class = 'forminator-row';
		$style = '';
		if ( $this->is_only_hidden( $wrapper ) ) {
			$class .= ' forminator-hidden';
		} elseif ( isset( $wrapper['fields'] ) && $this->is_only_invisible_field( $wrapper['fields'] ) ) {
			// Remove margin for wrappers with only invisible fields.
			$style = ' style="margin: 0;"';
		}

		$html = sprintf( '<div class="%1$s"%2$s>', esc_attr( $class ), $style );

		return apply_filters( 'forminator_before_wrapper_markup', $html, $wrapper );
	}

	/**
	 * Return after wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return mixed
	 */
	public function render_wrapper_after( $wrapper ) {
		$html = '</div>';

		return apply_filters( 'forminator_after_wrapper_markup', $html, $wrapper );
	}

	/**
	 * Extra form classes for ajax
	 *
	 * @since 1.0
	 */
	public function form_extra_classes() {
		$ajax_form = $this->is_ajax_submit();

		if ( $this->is_preview ) {
			$ajax_form = true;
		}

		$extra_class = $ajax_form ? 'forminator_ajax' : '';

		if ( isset( $this->lead_model->id ) ) {
			$extra_class .= ' forminator-leads-form';
		}

		return $extra_class;
	}

	/**
	 * Return true if we have only hidden field in the row
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function is_only_hidden( $wrapper ) {
		// We don't have any fields, abort.
		if ( ! isset( $wrapper['fields'] ) ) {
			return false;
		}

		// We have more than one field in the row, abort.
		if ( count( $wrapper['fields'] ) > 1 ) {
			// Checks if all fields are hidden.
			return ! array_diff( wp_list_pluck( $wrapper['fields'], 'type' ), array( 'hidden' ) );
		}

		// Check if the field type is hidden.
		if ( 'hidden' === $wrapper['fields'][0]['type'] || 'paypal' === $wrapper['fields'][0]['type'] ) {
			// Field type is hidden, return true.
			return true;
		}

		return false;
	}

	/**
	 * Get filtered wrappers by group. If group ID is empty - it returns ungrouped wrappers
	 *
	 * @param string $group_id Group ID.
	 * @return array
	 */
	public static function get_grouped_wrappers( $group_id = '' ) {
		$wrappers = array_filter(
			self::$all_wrappers,
			function ( $value ) use ( $group_id ) {
				return ! $group_id ? empty( $value['parent_group'] ) : ! empty( $value['parent_group'] ) && $group_id === $value['parent_group'];
			}
		);

		return $wrappers;
	}

	/**
	 * Return fields markup
	 *
	 * @since 1.0
	 *
	 * @param bool $render Render.
	 *
	 * @return string|void
	 */
	public function render_fields( $render = true ) {
		$html              = '';
		$pagination_fields = array();

		self::$all_wrappers = apply_filters( 'forminator_cform_render_fields', $this->get_wrappers(), $this->model->id );

		$wrappers = self::get_grouped_wrappers();

		$html .= $this->do_before_render_form_fields_for_addons();

		// Check if we have pagination field.
		if ( $this->has_pagination() ) {
			if ( ! empty( $wrappers ) ) {
				foreach ( $wrappers as $wrapper ) {
					foreach ( $wrapper['fields'] as $fields ) {
						if ( $this->is_pagination( $fields ) ) {
							$pagination_fields[] = $fields;
						}
					}
				}
			}
			$html .= $this->pagination_header();
			$html .= $this->pagination_start( $pagination_fields );
			$html .= $this->pagination_content_start();
		}

		$html .= $this->render_wrappers( $wrappers, $pagination_fields );

		// Check if we have pagination field.
		if ( $this->has_pagination() ) {
			$html .= $this->pagination_content_end();
			$html .= $this->pagination_submit_button();
			$html .= $this->pagination_end();
		}

		$html .= $this->do_after_render_form_fields_for_addons();

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_fields_markup', $html, $wrappers );
		}
	}

	/**
	 * Render wrappers with fields
	 *
	 * @param array $wrappers Wrappers with fields.
	 * @param array $pagination_fields Pagination fields.
	 * @return string
	 */
	public function render_wrappers( $wrappers, $pagination_fields = array() ) {
		$html = '';
		$step = 0;

		if ( empty( $wrappers ) ) {
			return $html;
		}

		foreach ( $wrappers as $wrapper ) {

			// a wrapper with no fields, continue to next wrapper.
			if ( ! isset( $wrapper['fields'] ) ) {
				continue;
			}

			$has_pagination = false;

			// Skip row markup if pagination field.
			if ( ! $this->is_pagination_row( $wrapper ) ) {
				// Render before wrapper markup.
				$html .= $this->render_wrapper_before( $wrapper );
			}

			foreach ( $wrapper['fields'] as $field ) {
				$field['parent_group'] = ! empty( $wrapper['parent_group'] ) ? $wrapper['parent_group'] : '';

				if ( $this->is_pagination( $field ) ) {
					$has_pagination = true;
				}

				// Skip row markup if pagination field.
				if ( ! $this->is_pagination_row( $wrapper ) ) {
					$html .= $this->get_field( $field );
				}
			}

			// Skip row markup if pagination field.
			if ( ! $this->is_pagination_row( $wrapper ) ) {
				// Render after wrapper markup.
				$html .= $this->render_wrapper_after( $wrapper );
			}

			if ( $has_pagination ) {
				$html .= $this->pagination_content_end();
				if ( isset( $field ) ) {
					$html .= $this->pagination_step( $field, $pagination_fields, ++$step );
				}
				$html .= $this->pagination_content_start();
			}
		}

		return $html;
	}

	/**
	 * Return if the row is pagination
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return bool
	 */
	public function is_pagination_row( $wrapper ) {
		$is_single = $this->is_single_field( $wrapper );

		if ( $is_single && isset( $wrapper['fields'][0]['type'] ) && 'page-break' === $wrapper['fields'][0]['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if only single field in the wrapper
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return bool
	 */
	public function is_single_field( $wrapper ) {
		if ( isset( $wrapper['fields'] ) && ( count( $wrapper['fields'] ) === 1 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return pagination header
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_header() {
		$type           = $this->get_pagination_type();
		$has_pagination = $this->has_pagination_header();

		if ( ! $has_pagination ) {
			return '';
		}

		if ( 'bar' === $type ) {
			$html = '<div class="forminator-pagination-progress" aria-hidden="true"></div>';
		} else {
			$html = '<div role="tablist" class="forminator-pagination-steps" aria-label="Pagination"></div>';
		}

		return apply_filters( 'forminator_pagination_header_markup', $html );
	}

	/**
	 * Return pagination start markup
	 *
	 * @param array $element Element.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_start( $element = array() ) {

		$form_settings = $this->get_form_settings();
		$label         = esc_html__( 'Finish', 'forminator' );
		$element_id    = ! empty( $element ) ? $element[0]['element_id'] : '';
		$actual_label  = sprintf(
			// translators: %d: Page number.
			__( 'Page %d', 'forminator' ),
			1
		);
		$actual_element_id = $element[0]['element_id'] ?? 'last';

		if ( isset( $form_settings['paginationData']['last-steps'] ) ) {
			$label = $form_settings['paginationData']['last-steps'];
		}

		if ( isset( $form_settings['paginationData'][ $actual_element_id . '-steps' ] ) ) {
			$actual_label = $form_settings['paginationData'][ $actual_element_id . '-steps' ];
		}

		$html = sprintf(
			'<div
				tabindex="-1"
				role="tabpanel"
				id="forminator-custom-form-%3$s--page-0"
				class="forminator-pagination forminator-pagination-start"
				aria-labelledby="forminator-custom-form-%3$s--page-0-label"
				data-step="0"
				data-label="%1$s"
				data-actual-label="%4$s"
				data-name="%2$s"
			>',
			esc_attr( $label ),
			esc_attr( $element_id ),
			esc_attr( $form_settings['form_id'] ),
			esc_attr( $actual_label )
		);

		return apply_filters( 'forminator_pagination_start_markup', $html, $label, $element_id );
	}


	/**
	 * Get Pagination Properties as array
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	public function get_pagination_properties() {

		$form_fields         = $this->get_fields();
		$pagination_settings = $this->get_pagination_field();
		$properties          = array(
			'has-pagination'           => $this->has_pagination(),
			'pagination-header-design' => 'show',
			'pagination-header'        => 'nav',
			'last-steps'               => esc_html__( 'Finish', 'forminator' ),
			'last-previous'            => esc_html__( 'Previous', 'forminator' ),
			'pagination-labels'        => 'default',
			'has-paypal'               => $this->has_paypal(),
			'progress-bar-type'        => 'progress',
			/* Translators: 1. Current page number, 2. Total number of pages. */
			'page-number-text'         => esc_html__( 'Page %1$s of %2$s', 'forminator' ),
		);

		foreach ( $properties as $property => $value ) {
			if ( isset( $pagination_settings[ $property ] ) ) {
				$new_value = $pagination_settings[ $property ];
				if ( is_bool( $value ) ) {
					// return boolean.
					$new_value = filter_var( $new_value, FILTER_VALIDATE_BOOLEAN );
				} elseif ( is_string( $new_value ) ) {
					// if empty string fallback to default.
					if ( empty( $new_value ) ) {
						$new_value = $value;
					}
				}
				$properties[ $property ] = $new_value;
			}
			foreach ( $form_fields as $form_field ) {
				if ( $this->is_pagination( $form_field ) ) {
					$element                             = $form_field['element_id'];
					$properties[ $element ]['prev-text'] = isset( $pagination_settings[ $element . '-previous' ] ) ? $pagination_settings[ $element . '-previous' ] : esc_html__( 'Previous', 'forminator' );
					$properties[ $element ]['next-text'] = isset( $pagination_settings[ $element . '-next' ] ) ? $pagination_settings[ $element . '-next' ] : esc_html__( 'Next', 'forminator' );
				}
				if ( $this->is_paypal( $form_field ) ) {
					$properties['paypal-id'] = $form_field['element_id'];
				}
			}
		}

		$form_id = $this->model->id;

		/**
		 * Filter pagination properties
		 *
		 * @since 1.1
		 *
		 * @param array $properties
		 * @param int $form_id Current Form ID.
		 */
		$properties = apply_filters( 'forminator_pagination_properties', $properties, $form_id );

		return $properties;
	}

	/**
	 * Get paypal Properties as array
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	public function get_paypal_properties() {
		global $wp;
		$form_fields = $this->get_fields();
		$paypal      = new Forminator_PayPal_Express();
		foreach ( $form_fields as $form_field ) {
			if ( $this->is_paypal( $form_field ) ) {
				foreach ( $form_field as $key => $field ) {
					$properties[ $key ] = $field;
				}
			}
		}
		$properties['live_id']      = $paypal->get_live_id();
		$properties['sandbox_id']   = $paypal->get_sandbox_id();
		$properties['redirect_url'] = home_url( $wp->request );

		$form_id               = $this->model->id;
		$properties['form_id'] = $form_id;

		/**
		 * Filter PayPal properties
		 *
		 * @since 1.1
		 *
		 * @param array $properties
		 * @param int $form_id Current Form ID.
		 */
		$properties = apply_filters( 'forminator_paypal_properties', $properties, $form_id );

		return $properties;
	}

	/**
	 * Return pagination content start markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_content_start() {
		$html = '<div class="forminator-pagination--content">';

		return apply_filters( 'forminator_pagination_content_start_markup', $html );
	}

	/**
	 * Return pagination content end markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_content_end() {
		$html = '</div>';

		return apply_filters( 'forminator_pagination_content_end_markup', $html );
	}

	/**
	 * Return submit field custom class
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function get_submit_custom_clas() {
		$settings = $this->get_form_settings();

		// Submit data is missing.
		if ( ! isset( $settings['submitData'] ) ) {
			return false;
		}

		if ( isset( $settings['submitData']['custom-class'] ) && ! empty( $settings['submitData']['custom-class'] ) ) {
			return $settings['submitData']['custom-class'];
		}

		return false;
	}

	/**
	 * Return pagination submit button markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_submit_button() {
		$button        = $this->get_submit_button_text();
		$custom_class  = $this->get_submit_custom_clas();
		$form_settings = $this->get_form_settings();

		$class = 'forminator-button forminator-pagination-submit';

		if ( $custom_class && ! empty( $custom_class ) ) {
			$class .= ' ' . esc_attr( $custom_class );
		}

		if ( $this->get_form_design() !== 'material' ) {

			$html = sprintf( '<button class="' . esc_attr( $class ) . '" style="display: none;" disabled>%s</button>', esc_html( $button ) );
		} else {
			$html
				=
				sprintf(
					'<button class="' . esc_attr( $class )
					. '" style="display: none;" disabled><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">%s</span></button>',
					esc_html( $button )
				);
		}

		$html .= $this->get_save_draft_button( $form_settings );

		return apply_filters( 'forminator_pagination_submit_markup', $html );
	}

	/**
	 * Return pagination end markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_end() {
		$html = '</div>';

		return apply_filters( 'forminator_pagination_end_markup', $html );
	}

	/**
	 * Return pagination start markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param array  $pagination Pagination.
	 * @param string $step Step number.
	 *
	 * @return string
	 */
	public function pagination_step( $field, $pagination, $step ) {
		$form_settings       = $this->get_form_settings();
		$label               = sprintf( '%s %s', esc_html__( 'Page ', 'forminator' ), $step );
		$pagination_settings = $this->get_pagination_field();
		$actual_label        = sprintf(
			// translators: %d: Page number.
			__( 'Page %d', 'forminator' ),
			$step + 1
		);
		if ( isset( $pagination_settings[ $field['element_id'] . '-steps' ] ) ) {
			$label = $pagination_settings[ $field['element_id'] . '-steps' ];
		}
		$actual_element_id = $pagination[ $step ]['element_id'] ?? 'last';
		if ( isset( $pagination_settings[ $actual_element_id . '-steps' ] ) ) {
			$actual_label = $pagination_settings[ $actual_element_id . '-steps' ];
		}
		$element_id = '';
		if ( ! empty( $pagination ) ) {
			$pagination_count = count( $pagination );
			for ( $i = $step; $i <= $pagination_count; $i++ ) {
				if ( isset( $pagination[ $i ]['element_id'] ) && ( $field['element_id'] !== $pagination[ $i ]['element_id'] ) ) {
					$element_id = $pagination[ $i ]['element_id'];
					break;
				}
			}
		}

		$html = sprintf(
			'</div><div
				tabindex="-1"
				role="tabpanel"
				id="forminator-custom-form-%4$s--page-%1$s"
				class="forminator-pagination"
				aria-labelledby="forminator-custom-form-%4$s--page-%1$s-label"
				aria-hidden="true"
				data-step="%1$s"
				data-label="%2$s"
				data-actual-label="%5$s"
				data-name="%3$s"
				hidden
			>',
			esc_attr( $step ),
			esc_attr( $label ),
			esc_attr( $element_id ),
			esc_attr( $form_settings['form_id'] ),
			esc_attr( $actual_label )
		);

		return apply_filters( 'forminator_pagination_step_markup', $html, $step, $label, $element_id );
	}

	/**
	 * Return field markup
	 *
	 * @since 1.0
	 * @since 1.17.0 Add draft_value parameter
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function get_field( $field ) {
		$html = '';

		do_action( 'forminator_before_field_render', $field );

		// Get field object.
		/**
		 * Forminator_Field
		 *
		 * @var Forminator_Field $field_object */
		$field_object = Forminator_Core::get_field_object( $this->get_field_type( $field ) );

		// If bool, abort.
		if ( is_bool( $field_object ) || is_null( $field_object ) ) {
			return $html;
		}

		if ( $field_object->is_available( $field ) ) {

			// Render before field markup.
			$html .= $this->render_field_before( $field );

			// Render field.
			$html .= $this->render_field( $field );

			// Render after field markup.
			$html .= $this->render_field_after( $field );
		}

		do_action( 'forminator_after_field_render', $field );

		return $html;
	}

	/**
	 * Check if field is invisible
	 *
	 * @param mixed $field Form Field.
	 * @return bool
	 */
	private function is_invisible_field( $field ) {
		if ( self::is_hidden( $field ) || 'paypal' === $field['type'] ) {
			return true;
		} elseif ( 'captcha' === $field['type'] && ! empty( $field['captcha_provider'] ) ) {
			if ( ( ! empty( $field['captcha_type'] ) && 'recaptcha' === $field['captcha_provider'] && in_array( $field['captcha_type'], array( 'v2_invisible', 'v3_recaptcha' ), true )
			&& ( ! empty( $field['captcha_badge'] ) && 'inline' !== $field['captcha_badge'] ) )
			|| ( ! empty( $field['hcaptcha_type'] ) && 'hcaptcha' === $field['captcha_provider'] && 'hc_invisible' === $field['hcaptcha_type'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if all fields are invisible.
	 *
	 * @param mixed $fields Form Fields.
	 * @return bool
	 */
	private function is_only_invisible_field( $fields ) {
		if ( empty( $fields ) ) {
			return false;
		}

		foreach ( $fields as $field ) {
			if ( ! $this->is_invisible_field( $field ) ) {
				// Found a visible field.
				return false;
			}
		}

		return true;
	}

	/**
	 * Return field markup
	 *
	 * @since 1.0
	 * @since 1.17.0 Add draft_value parameter
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function render_field( $field ) {
		$html = '';
		$type = $this->get_field_type( $field );

		if ( ! empty( $field['group_suffix'] ) ) {
			$field['element_id'] .= $field['group_suffix'];
		}
		$draft_value = isset( $this->draft_data[ $field['element_id'] ] ) ? $this->draft_data[ $field['element_id'] ] : null;

		// Add custom value for radio, select, or checkbox if applicable.
		if ( null !== $draft_value && in_array( $type, array( 'radio', 'select', 'checkbox' ), true ) ) {
			if ( isset( $this->draft_data[ 'custom-' . $field['element_id'] ] ) ) {
				$draft_value['custom_value'] = $this->draft_data[ 'custom-' . $field['element_id'] ];
			}
		}

		// Get field object.
		/**
		 * Forminator_Field
		 *
		 * @var Forminator_Field $field_object */
		$field_object = Forminator_Core::get_field_object( $type );

		// Print field markup.
		$html .= $field_object->markup( $field, $this, $draft_value );

		$this->inline_rules    .= $field_object->get_validation_rules();
		$this->inline_messages .= $field_object->get_validation_messages();

		return apply_filters( 'forminator_field_markup', $html, $field, $this );
	}

	/**
	 * Return field ID
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_id( $field ) {
		if ( ! isset( $field['element_id'] ) ) {
			return '';
		}
		$id = $field['element_id'];
		if ( ! empty( $field['group_suffix'] ) ) {
			$id .= $field['group_suffix'];
		}

		return $id;
	}

	/**
	 * Return field columns
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_cols( $field ) {
		if ( ! isset( $field['cols'] ) ) {
			return '12';
		}

		return $field['cols'];
	}

	/**
	 * Return field type
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function get_field_type( $field ) {
		if ( ! isset( $field['type'] ) ) {
			return false;
		}

		return $field['type'];
	}

	/**
	 * Return placeholder
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function get_placeholder( $field ) {
		if ( ! isset( $field['placeholder'] ) ) {
			return '';
		}

		return $this->sanitize_output( $field['placeholder'] );
	}

	/**
	 * Return field label
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function get_field_label( $field ) {
		if ( ! isset( $field['field_label'] ) ) {
			return '';
		}

		return $this->sanitize_output( $field['field_label'] );
	}

	/**
	 * Return description markup
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function get_description( $field ) {
		_deprecated_function( __METHOD__, '1.6' );
		$type = $this->get_field_type( $field );
		/**
		 * Forminator_Field
		 *
		 * @var Forminator_Field $field_object */
		$field_object              = Forminator_Core::get_field_object( $type );
		$has_phone_character_limit = ( ( isset( $field['phone_validation'] ) && $field['phone_validation'] )
										&& ( isset( $field['validation'] )
											&& 'character_limit' === $field['validation'] ) );

		if ( ( isset( $field['description'] ) && ! empty( $field['description'] ) ) || isset( $field['text_limit'] ) || $has_phone_character_limit ) {

			$html = sprintf( '<div class="forminator-description">' );

			if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
				$description = $this->sanitize_output( $field['description'] );
				if ( 'false' === $description ) {
					$description = '';
				}

				$html .= $description;
			}

			if ( ( isset( $field['text_limit'] ) || isset( $field['phone_limit'] ) ) && isset( $field['limit'] ) && ( $field_object->has_counter || $has_phone_character_limit ) ) {
				if ( ( isset( $field['text_limit'] ) && $field['text_limit'] ) || ( isset( $field['phone_limit'] ) && $field['phone_limit'] ) || $has_phone_character_limit ) {
					$limit = isset( $field['limit'] ) ? $field['limit'] : '';
					if ( empty( $limit ) && $has_phone_character_limit ) {
						$limit = 10;
					}
					$limit_type = isset( $field['limit_type'] ) ? $field['limit_type'] : '';
					$html      .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', esc_attr( $limit ), esc_attr( $limit_type ), esc_html( $limit ) );
				}
			}

			$html .= sprintf( '</div>' );
		} else {
			$html = '';
		}

		return apply_filters( 'forminator_field_get_description', $html, $field );
	}

	/**
	 * Return field before markup
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function render_field_before( $field ) {
		$class = $this->get_classes( $field );
		$cols  = $this->get_cols( $field );
		$id    = $this->get_id( $field );

		$class_col = '';
		if ( ! $this->is_invisible_field( $field ) ) {
			$class_col = 'forminator-col';
		}

		$html = sprintf( '<div id="%s" class="forminator-field-%s %s forminator-col-%s %s">', esc_attr( $id ), esc_attr( $field['type'] ), esc_attr( $class_col ), esc_attr( $cols ), esc_attr( $class ) );

		return apply_filters( 'forminator_before_field_markup', $html, $class );
	}

	/**
	 * Return field after markup
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function render_field_after( $field ) {
		$html = sprintf( '</div>' );

		return apply_filters( 'forminator_after_field_markup', $html, $field );
	}

	/**
	 * Return Form Settins
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_form_settings() {
		return $this->model->get_form_settings();
	}

	/**
	 * Return if hidden field
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public static function is_hidden( $field ) {
		// Array of hidden fields.
		$hidden = apply_filters( 'forminator_cform_hidden_fields', array( 'hidden' ) );

		if ( in_array( $field['type'], $hidden, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return Form Design
	 *
	 * @since 1.0
	 * @return mixed|string
	 */
	public function get_form_design() {
		$form_settings  = $this->get_form_settings();
		$form_style     = $form_settings['form-style'] ?? 'default';
		$form_sub_style = $form_settings['form-substyle'] ?? 'default';

		return 'default' === $form_style ?
			$form_sub_style : $form_style;
	}

	/**
	 * Return fields style
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_fields_style() {
		$form_settings   = $this->get_form_settings();
		$form_design     = $this->get_form_design();
		$field_style_key = 'basic' === $form_design ? 'basic-fields-style' : 'fields-style';

		if ( isset( $form_settings[ $field_style_key ] ) ) {
			return $form_settings[ $field_style_key ];
		}

		return 'open';
	}

	/**
	 * Check if honeypot protection is enabled
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_honeypot_enabled() {
		$form_settings = $this->get_form_settings();

		if ( ! isset( $form_settings['honeypot'] ) ) {
			return false;
		}

		return filter_var( $form_settings['honeypot'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if form has a captcha field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_captcha() {
		return $this->has_field_type( 'captcha' );
	}

	/**
	 * Check if form has a recaptcha field
	 *
	 * @since 1.15.5
	 * @return bool
	 */
	public function is_recaptcha() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'captcha' === $field['type'] && 'recaptcha' === $field['captcha_provider'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has Cloudflare turnstile captcha.
	 *
	 * @return bool
	 */
	public function is_turnstile() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'captcha' === $field['type'] && 'turnstile' === $field['captcha_provider'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a date field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_date() {
		return $this->has_field_type( 'date' );
	}

	/**
	 * Check if form has a date field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_upload() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'upload' === $field['type'] || 'postdata' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a pagination field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_pagination() {
		return $this->has_field_type( 'page-break' );
	}

	/**
	 * Return if field is pagination
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_pagination( $field ) {
		if ( isset( $field['type'] ) && 'page-break' === $field['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if field is paypal
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_paypal( $field ) {
		if ( isset( $field['type'] ) && 'paypal' === $field['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return field classes
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_classes( $field ) {

		$class = '';

		if ( isset( $field['custom-class'] ) && ! empty( $field['custom-class'] ) ) {
			$class .= ' ' . esc_html( $field['custom-class'] );
		}

		return $class;
	}

	/**
	 * Return fields conditions for JS
	 *
	 * @since 1.0
	 *
	 * @param array $id Id.
	 *
	 * @return mixed
	 */
	public function get_relations( $id ) {
		$relations = array();
		$fields    = $this->get_fields();

		// Add submit as field.
		$fields[] = $this->get_submit_field();

		// Fallback.
		if ( empty( $fields ) ) {
			return $relations;
		}

		foreach ( $fields as $field ) {
			if ( $this->is_conditional( $field ) ) {
				$field_conditions = isset( $field['conditions'] ) ? $field['conditions'] : array();

				foreach ( $field_conditions as $condition ) {
					if ( $id === $condition['element_id'] ) {
						$relations[] = $this->get_field_id( $field );
					}
				}
			}
		}

		return $relations;
	}

	/**
	 * Compare element_id with precision elements
	 *
	 * @since 1.13
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public function compare_element_id_with_precision_elements( $element_id ) {
		return false !== strpos( $element_id, 'calculation-' )
				|| false !== strpos( $element_id, 'currency-' );
	}

	/**
	 * Change condition value with specified precision
	 *
	 * @since 1.13
	 *
	 * @param string $condition_value Condition value.
	 * @param array  $field Field.
	 *
	 * @return string
	 */
	public function change_condition_value_with_precision( $condition_value, $field ) {
		if ( '' === $condition_value ) {
			return $condition_value;
		}

		$precision = Forminator_Field::get_property( 'precision', $field, 2 );

		return sprintf( "%.{$precision}f", $condition_value );
	}

	/**
	 * Return fields conditions for JS
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_conditions() {
		$conditions = array();
		$relations  = array();
		$fields     = $this->get_fields();
		$module_id  = $this->get_module_id();

		// Add submit as field.
		$fields[] = $this->get_submit_field();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$id               = $this->get_field_id( $field );
				$relations[ $id ] = $this->get_relations( $id );

				// Check if conditions are enabled.
				if ( $this->is_conditional( $field ) ) {
					$field_data       = array();
					$condition_action = isset( $field['condition_action'] ) ? $field['condition_action'] : 'show';
					$condition_rule   = isset( $field['condition_rule'] ) ? $field['condition_rule'] : 'all';
					$field_conditions = isset( $field['conditions'] ) ? $field['conditions'] : array();

					foreach ( $field_conditions as $condition ) {
						if ( forminator_old_field( $condition['element_id'], $fields, $module_id ) ) {
							continue;
						}
						if ( $this->compare_element_id_with_precision_elements( $condition['element_id'] ) ) {
							foreach ( $fields as $field_array ) {
								if ( $field_array['element_id'] === $condition['element_id'] ) {
									$condition['value'] = $this->change_condition_value_with_precision( $condition['value'], $field_array );
									break;
								}
							}
						}
						$new_condition = array(
							'field'    => $condition['element_id'],
							'group'    => $this->get_parent_group( $fields, $condition['element_id'] ),
							'operator' => $condition['rule'],
							'value'    => $condition['value'],
						);

						$field_data[] = $new_condition;
					}

					$conditions[ $id ] = array(
						'action'     => $condition_action,
						'rule'       => $condition_rule,
						'conditions' => $field_data,
					);
				}
			}
		}

		return array(
			'fields'    => $conditions,
			'relations' => $relations,
		);
	}

	/**
	 * Get parent group
	 *
	 * @param array  $fields Fields.
	 * @param string $field_id Field slug.
	 * @return string
	 */
	private function get_parent_group( $fields, $field_id ) {
		$parents  = $this->get_parent_groups( $fields );
		$field_id = forminator_remove_prefixes( $field_id );
		$group    = $parents[ $field_id ];

		return $group;
	}

	/**
	 * Get parent groups for all fields
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	private function get_parent_groups( $fields ) {
		if ( is_null( $this->parent_groups ) ) {
			array_pop( $fields ); // Remove 'Submit' field.
			$this->parent_groups = wp_list_pluck( $fields, 'parent_group', 'element_id' );
		}

		return $this->parent_groups;
	}

	/**
	 * Check field is conditional
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_conditional( $field ) {
		if ( ! empty( $field['hidden'] ) ) {
			return false;
		}

		if ( isset( $field['conditions'] ) && ! empty( $field['conditions'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the form encryption type if there is an upload
	 *
	 * @since 1.0
	 * @return string
	 */
	public function form_enctype() {
		if ( $this->has_upload() ) {
			return 'enctype="multipart/form-data"';
		} else {
			return '';
		}
	}

	/**
	 * Has paypal
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_paypal() {
		$is_enabled = forminator_has_paypal_settings();
		$selling    = 0;
		$fields     = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'paypal' === $field['type'] ) {
					++$selling;
				}
			}
		}

		return ( $is_enabled && $selling > 0 ) ? true : false;
	}

	/**
	 * Return button markup
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function get_button_markup() {

		$html          = '';
		$class         = 'forminator-button forminator-button-submit';
		$form_settings = $this->get_form_settings();

		if ( empty( $this->lead_model->id ) || empty( $this->lead_model->settings['pagination'] )
				|| ( ! empty( $this->lead_model->settings )
				&& 'end' === $this->get_form_placement( $this->lead_model->settings ) ) ) {
			$button = $this->get_submit_button_text();
		} else {
			$class .= ' forminator-quiz-start';
			$button = $this->get_start_button_text( $this->lead_model->settings );
		}

		$custom_class = $this->get_submit_custom_clas();

		if ( ! empty( $custom_class ) ) {
			$class .= ' ' . $custom_class;
		}

		$html .= '<div class="forminator-row forminator-row-last">';

		$html .= '<div class="forminator-col">';

		$html .= '<div class="forminator-field">';

		$html .= sprintf( '<button class="%s">', esc_attr( $class ) );

		if ( 'material' === $this->get_form_design() ) {

			$html .= sprintf( '<span>%s</span>', esc_html( $button ) );

			$html .= '<span aria-hidden="true"></span>';

		} else {

			$html .= esc_html( $button );

		}

		$html .= '</button>';

		$html .= $this->get_save_draft_button( $form_settings );

		$html .= '</div>';

		$html .= '</div>';

		$html .= '</div>';

		return apply_filters( 'forminator_render_button_markup', $html, $button );
	}

	/**
	 * PayPal button markup
	 *
	 * @since 1.0
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return mixed
	 */
	public function get_paypal_button_markup( $form_id ) {

		$html        = '';
		$custom_form = Forminator_Base_Form_Model::get_model( $form_id );
		if ( is_object( $custom_form ) ) {
			$fields = $custom_form->get_fields();
			foreach ( $fields as $field ) {

				$field_array = $field->to_formatted_array();
				$field_type  = $field_array['type'];

				if ( 'paypal' === $field_type ) {

					$id = Forminator_Field::get_property( 'element_id', $field_array );

					$html  = '<div class="forminator-row forminator-paypal-row">';
					$html .= '<div class="forminator-col forminator-col-12">';
					$html .= '<div class="forminator-field">';
					$html .= '<div id="paypal-button-container-' . $form_id . '_' . self::$uid . '" class="' . esc_attr( $id ) . '-payment forminator-button-paypal">';
					$html .= '</div>';
					$html .= $this->get_save_draft_button( $this->get_form_settings() );
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

				}
			}
		}

		return apply_filters( 'forminator_render_button_markup', $html );
	}

	/**
	 * Return form submit button markup
	 *
	 * @since 1.0
	 *
	 * @param int  $form_id Form Id.
	 * @param bool $render Render.
	 * @param int  $render_id Render Id.
	 *
	 * @return mixed|void
	 */
	public function get_submit( $form_id, $render = true, $render_id = 0 ) {
		$html       = '';
		$nonce      = $this->nonce_field( 'forminator_submit_form' . $form_id, 'forminator_nonce' );
		$post_id    = $this->get_post_id();
		$has_paypal = $this->has_paypal();
		$settings   = $this->get_form_settings();
		$form_type  = $settings['form-type'] ?? '';

		if ( $has_paypal ) {
			if ( ! ( self::$paypal instanceof Forminator_Paypal_Express ) ) {
				self::$paypal = new Forminator_Paypal_Express();
			}
			self::$paypal_forms[] = $form_id;
		}

		// If we have pagination skip button markup.
		if ( ! $this->has_pagination() ) {
			if ( $has_paypal ) {
				$html .= '<input type="hidden" name="payment_gateway_total" value="" />';
				$html .= $this->get_paypal_button_markup( $form_id );
			}
			$fields = $this->model->get_fields();
			if ( count( $fields ) ) {
				$html .= $this->get_button_markup();
			}
		}

		$html .= $nonce;
		$html .= sprintf( '<input type="hidden" name="form_id" value="%s">', esc_html( $form_id ) );
		$html .= sprintf( '<input type="hidden" name="page_id" value="%s">', esc_html( $post_id ) );
		$html .= sprintf( '<input type="hidden" name="form_type" value="%s">', esc_html( $form_type ) );
		$html .= sprintf( '<input type="hidden" name="current_url" value="%s">', esc_url( forminator_get_current_url() ) );
		$html .= sprintf( '<input type="hidden" name="render_id" value="%s">', esc_html( $render_id ) );

		if ( $this->has_multiupload() ) {
			$html .= sprintf( '<input type="hidden" name="forminator-multifile-hidden" class="forminator-multifile-hidden">' );
		}

		if ( $this->is_login_form() ) {
			$redirect_url = ! empty( $settings['redirect-url'] ) ? $settings['redirect-url'] : admin_url();
			$redirect_url = forminator_replace_variables( $redirect_url, $form_id );
			$html        .= sprintf( '<input type="hidden" name="redirect_to" value="%s">', esc_url( $redirect_url ) );
		}

		if ( isset( $this->lead_model->id ) ) {
			$html .= sprintf( '<input type="hidden" name="lead_quiz" value="%s">', esc_html( $this->lead_model->id ) );
		}

		if ( $this->is_preview ) {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_preview_form_custom-forms' );
		} else {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_form_custom-forms' );
		}

		if ( isset( $settings['use_save_and_continue'] ) && filter_var( $settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN ) ) {
			$html .= '<input type="hidden" name="save_draft" value="false">';

			if ( ! empty( $this->draft_id ) ) {
				$html .= sprintf( '<input type="hidden" name="previous_draft_id" value="%s">', esc_html( $this->draft_id ) );
			}
		}

		$html .= $this->do_after_render_form_for_addons();

		$html = apply_filters( 'forminator_render_form_submit_markup', $html, $form_id, $post_id, $nonce, $settings );
		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return $html;
		}
	}

	/**
	 * Submit button text
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_submit_button_text() {
		if ( $this->has_custom_submit_text() ) {
			return $this->get_custom_submit_text();
		} else {
			parent::get_submit_button_text();
		}
	}

	/**
	 * Return custom submit button text
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_custom_submit_text() {
		$settings = $this->get_form_settings();

		return $this->sanitize_output( $settings['submitData']['custom-submit-text'] );
	}

	/**
	 * Return if custom submit button text
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_custom_submit_text() {
		$settings = $this->get_form_settings();

		if ( isset( $settings['submitData']['custom-submit-text'] ) && ! empty( $settings['submitData']['custom-submit-text'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render honeypot field
	 *
	 * @since 1.0
	 *
	 * @param string $html - the button html.
	 * @param int    $form_id - the current form id.
	 * @param int    $post_id - the current post id.
	 * @param string $nonce - the nonce field.
	 *
	 * @return string $html
	 */
	public function render_honeypot_field(
		$html,
		$form_id,
		/* @noinspection PhpUnusedParameterInspection */
		$post_id,
		/* @noinspection PhpUnusedParameterInspection */
		$nonce
	) {
		if ( (int) $form_id === (int) $this->model->id && $this->is_honeypot_enabled() ) {
			$fields       = $this->model->get_real_fields();
			$total_fields = count( $fields ) + 1;
			// Most bots won't bother with hidden fields, so set to text and hide it.
			$html .= sprintf( '<label for="%1$s" class="forminator-hidden" aria-hidden="true">%2$s <input id="%1$s" type="text" name="%1$s" value="" autocomplete="off"></label>', "input_$total_fields", esc_html__( 'Please do not fill in this field.', 'forminator' ) );
		}

		return $html;
	}

	/**
	 * Get CSS prefix
	 *
	 * @param string $prefix Default prefix.
	 * @param array  $properties CSS properties.
	 * @param string $slug Slug.
	 * @return string
	 */
	protected static function get_css_prefix( $prefix, $properties, $slug ) {
		if ( 'none' !== $properties['form-style'] ) {
			$form_style     = $properties['form-style'] ?? 'default';
			$form_sub_style = $properties['form-substyle'] ?? 'default';
			$form_style     = 'default' === $form_style ? $form_sub_style : $form_style;
			$prefix        .= '.forminator-design--' . $form_style . ' ';
		}
		return $prefix;
	}

	/**
	 * Get PayPal field properties
	 *
	 * @since 1.7.1
	 *
	 * @return array
	 */
	public function get_pp_field_properties() {
		$fields = $this->get_fields();
		$props  = array();

		foreach ( $fields as $field ) {

			if ( 'paypal' === $field['type'] ) {

				if ( isset( $field['width'] ) ) {
					$props['paypal-width'] = $field['width'];
				}

				if ( isset( $field['height'] ) ) {
					$props['paypal-height'] = $field['height'];
				}

				if ( isset( $field['layout'] ) ) {
					$props['paypal-layout'] = $field['layout'];
				}

				if ( isset( $field['tagline'] ) ) {
					$props['paypal-tagline'] = $field['tagline'];
				}
			}
		}

		return $props;
	}

	/**
	 * Return if form pagination has header
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_pagination_header() {
		$settings  = $this->get_pagination_field();
		$is_active = 'show';

		if ( isset( $settings['pagination-header-design'] ) ) {
			$is_active = $settings['pagination-header-design'];
		}

		if ( 'show' === $is_active && ( 'nav' === $this->get_pagination_type() || 'bar' === $this->get_pagination_type() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get pagination type
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_pagination_type() {
		$settings = $this->get_pagination_field();
		if ( ! isset( $settings['pagination-header'] ) ) {
			return 'nav';
		}
		return $settings['pagination-header'];
	}

	/**
	 * Prints Javascript required for each form with PayPal
	 *
	 * @since 1.0
	 */
	public function print_paypal_scripts() {
		foreach ( self::$paypal_forms as $paypal_form_id ) {
			self::$paypal->render_buttons_script( $paypal_form_id );
		}
	}

	/**
	 * Defines translatable strings to pass to datepicker
	 * Add other strings if required
	 *
	 * @since 1.0.5
	 */
	public function get_strings_for_calendar() {
		$calendar['days']   = array(
			esc_html__( 'Su', 'forminator' ),
			esc_html__( 'Mo', 'forminator' ),
			esc_html__( 'Tu', 'forminator' ),
			esc_html__( 'We', 'forminator' ),
			esc_html__( 'Th', 'forminator' ),
			esc_html__( 'Fr', 'forminator' ),
			esc_html__( 'Sa', 'forminator' ),
		);
		$calendar['months'] = array(
			esc_html__( 'Jan', 'forminator' ),
			esc_html__( 'Feb', 'forminator' ),
			esc_html__( 'Mar', 'forminator' ),
			esc_html__( 'Apr', 'forminator' ),
			esc_html__( 'May', 'forminator' ),
			esc_html__( 'Jun', 'forminator' ),
			esc_html__( 'Jul', 'forminator' ),
			esc_html__( 'Aug', 'forminator' ),
			esc_html__( 'Sep', 'forminator' ),
			esc_html__( 'Oct', 'forminator' ),
			esc_html__( 'Nov', 'forminator' ),
			esc_html__( 'Dec', 'forminator' ),
		);

		return wp_json_encode( $calendar );
	}

	/**
	 * Return if form use google font
	 *
	 * @since 1.0
	 * @since 1.2 Deprecate function
	 * @return bool
	 */
	public function has_google_font() {

		/**
		 * Deprecate this function, since `use-fonts-settings` and `font-family` no longer valid on 1.2
		 * Font / typography settings changed to different sections
		 * such as `cform-label-font-family`, `cform-title-font-family` etc
		 *
		 * @since 1.2
		 */
		_deprecated_function( 'has_google_font', '1.2', 'get_google_fonts' );

		$settings = $this->get_form_settings();

		// Check if custom font enabled.
		if ( ! isset( $settings['use-fonts-settings'] ) || empty( $settings['use-fonts-settings'] ) ) {
			return false;
		}

		// Check if custom font.
		if ( ! isset( $settings['font-family'] ) || empty( $settings['font-family'] ) || 'custom' === $settings['font-family'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Return google font
	 *
	 * @since 1.0
	 * @since 1.2 Deprecated Function
	 * @return string
	 */
	public function get_google_font() {

		/**
		 * Deprecate this function, since `use-fonts-settings` and `font-family` no longer valid on 1.2
		 * Font / typography settings changed to different sections
		 * such as `cform-label-font-family`, `cform-title-font-family` etc
		 *
		 * @since 1.2
		 */
		_deprecated_function( 'get_google_font', '1.2', 'get_google_fonts' );

		$settings = $this->get_form_settings();

		return $settings['font-family'];
	}

	/**
	 * Return if form use inline validation
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_inline_validation() {
		$settings = $this->get_form_settings();

		if ( isset( $settings['validation-inline'] ) && $settings['validation-inline'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Render Front Script
	 *
	 * @since 1.0
	 * @since 1.1 add pagination properties on `window`
	 */
	public function forminator_render_front_scripts() {
		?>
		<script type="text/javascript">
			jQuery(function () {
				window.Forminator_Cform_Paginations = window.Forminator_Cform_Paginations || [];
				<?php
				if ( ! empty( $this->forms_properties ) ) {
					foreach ( $this->forms_properties as $form_properties ) {
						$options           = $this->get_front_init_options( $form_properties );
						$pagination_config = $options['pagination_config'];
						unset( $options['pagination_config'] );
						?>
				window.Forminator_Cform_Paginations[<?php echo esc_attr( $form_properties['id'] ); ?>] =
						<?php echo wp_json_encode( $pagination_config ); ?>;

				var runForminatorFront = function () {
					jQuery('#forminator-module-<?php echo esc_attr( $form_properties['id'] ); ?>[data-forminator-render="<?php echo esc_attr( $form_properties['render_id'] ); ?>"]')
						.forminatorFront(<?php echo wp_json_encode( $options ); ?>);
				}

				if (window.elementorFrontend) {
					if (typeof elementorFrontend.hooks !== "undefined") {
						elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
							runForminatorFront();
						});
					}
				} else {
					runForminatorFront();
				}

						<?php
					}
				}
				?>
				if (typeof ForminatorValidationErrors !== 'undefined') {
					var forminatorFrontSubmit = jQuery(ForminatorValidationErrors.selector).data('forminatorFrontSubmit');
					if (typeof forminatorFrontSubmit !== 'undefined') {
						forminatorFrontSubmit.show_messages(ForminatorValidationErrors.errors);
					}
				}
				if (typeof ForminatorFormHider !== 'undefined') {
					var forminatorFront = jQuery(ForminatorFormHider.selector).data('forminatorFront');
					if (typeof forminatorFront !== 'undefined') {
						jQuery(forminatorFront.forminator_selector).find('.forminator-row').hide();
						jQuery(forminatorFront.forminator_selector).find('.forminator-pagination-steps').hide();
						jQuery(forminatorFront.forminator_selector).find('.forminator-pagination-footer').hide();
					}
				}
			});
		</script>
		<?php
	}

	/**
	 * Render Front-end behavior Script
	 *
	 * @since 1.39
	 */
	public function forminator_render_front_submission_behavior_scripts() {
		?>
		<script type="text/javascript">
			jQuery(function () {
				if (typeof ForminatorFormNewTabRedirect !== 'undefined') {
					var forminatorFront = ForminatorFormNewTabRedirect.url;
					if (typeof forminatorFront !== 'undefined') {
						window.open(ForminatorFormNewTabRedirect.url, '_blank');
					}
				}
			});
		</script>
		<?php
	}

	/**
	 * Get Output of addons after_render_form
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_after_render_form_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_hooks( $this->model->id, 'form' );
				if ( $form_hooks instanceof Forminator_Integration_Form_Hooks ) {
					$form_hooks->on_after_render_form();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_after_render_form', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get Output of addons before render form fields
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_before_render_form_fields_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_hooks( $this->model->id, 'form' );
				if ( $form_hooks instanceof Forminator_Integration_Form_Hooks ) {
					$form_hooks->on_before_render_form_fields();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_before_render_form_fields', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get Output of addons after render form fields
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_after_render_form_fields_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_hooks( $this->model->id, 'form' );
				if ( $form_hooks instanceof Forminator_Integration_Form_Hooks ) {
					$form_hooks->on_after_render_form_fields();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_after_render_form_fields', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get Google Fonts setup on a form
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_google_fonts() {
		$fonts    = array();
		$settings = $this->get_form_settings();

		$font_settings_enabled = isset( $settings['form-font-family'] ) ? $settings['form-font-family'] : false;
		$font_settings_enabled = ( 'custom' === $font_settings_enabled ) ? true : false;

		// on clean design, disable google fonts.
		if ( 'none' !== $this->get_form_design() && $font_settings_enabled ) {
			$configs = array(
				'response',
				'label',
				'description',
				'validation',
				'title',
				'subtitle',
				'input',
				'input-prefix',
				'input-suffix',
				'radio',
				'select',
				'dropdown',
				'calendar',
				'multiselect',
				'esign-placeholder',
				'repeater-button',
				'pagination-buttons',
				'timeline',
				'progress',
				'button',
				'upload-single-button',
				'upload-single-text',
				'upload-multiple-panel',
				'upload-multiple-file-name',
				'upload-multiple-file-size',
			);

			foreach ( $configs as $font_setting_key ) {
				$font_family_settings_name = 'cform-' . $font_setting_key . '-font-family';

				// Dont add cform- prefix if setting is related to upload button.
				if (
					'upload-single-button' === $font_setting_key ||
					'upload-single-text' === $font_setting_key ||
					'upload-multiple-panel' === $font_setting_key ||
					'upload-multiple-file-name' === $font_setting_key ||
					'upload-multiple-file-size' === $font_setting_key
				) {
					$font_family_settings_name = $font_setting_key . '-font-family';
				}

				$font_family_name = '';
				// check if font family selected.
				if ( isset( $settings[ $font_family_settings_name ] ) && ! empty( $settings[ $font_family_settings_name ] ) ) {
					$font_family_name = $settings[ $font_family_settings_name ];
				}
				// check if form has relevant fields.
				if ( ! $this->has( $font_setting_key ) ) {
					continue;
				}

				// skip not selected / `custom` is selected.
				if ( empty( $font_family_name ) || 'custom' === $font_family_name ) {
					$fonts[ $font_family_settings_name ] = false;
					continue;
				}

				$fonts[ $font_family_settings_name ] = $font_family_name;

			}
		}

		$form_id = $this->model->id;

		/**
		 * Filter google fonts to be loaded for a form
		 *
		 * @since 1.2
		 *
		 * @param array $fonts
		 * @param int $form_id
		 * @param array $settings form settings.
		 */
		$fonts = apply_filters( 'forminator_custom_form_google_fonts', $fonts, $form_id, $settings );

		return $fonts;
	}

	/**
	 * Check if field with type exist on a form, and check if its setting match
	 *
	 * @since 1.2
	 *
	 * @param string      $field_type Field type.
	 * @param string|null $setting_name Setting name.
	 * @param string|null $setting_value Setting value.
	 *
	 * @return bool
	 */
	public function has_field_type_with_setting_value( $field_type, $setting_name = null, $setting_value = null ) {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( $field_type === $field['type'] ) {
					if ( is_null( $setting_name ) ) {
						return true;
					} elseif ( isset( $field[ $setting_name ] ) ) {
						$field_settings_value = $field[ $setting_name ];
						if ( is_bool( $setting_value ) ) {
							// cast to bool.
							$field_settings_value = filter_var( $field[ $setting_name ], FILTER_VALIDATE_BOOLEAN );
						}

						if (
							'address_country' === $setting_name &&
							(bool) $setting_value === (bool) $field_settings_value
						) {
							return true;
						}

						if ( $setting_value === $field_settings_value ) {
							return true;
						}
					} elseif ( 'select' === $field_type && 'value_type' === $setting_name && 'single' === $setting_value ) { // for backward compatibility if select type doesn't set.
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Find last captcha
	 *
	 * @since 1.6
	 * @return array|bool
	 */
	public function find_first_captcha() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'captcha' === $field['type'] ) {
					return $field;
				}
			}
		}

		return false;
	}

	/**
	 * Get forminatorFront js init options to be passed
	 *
	 * @since 1.6.1
	 *
	 * @param array $form_properties Form properties.
	 *
	 * @return array
	 */
	public function get_front_init_options( $form_properties ) {

		if ( empty( $form_properties ) ) {
			return array();
		}
		$has_stripe = $this->has_stripe();

		$options = array(
			'form_type'           => $this->get_form_type(),
			'inline_validation'   => filter_var( $form_properties['inline_validation'], FILTER_VALIDATE_BOOLEAN ),
			'print_value'         => ! empty( $form_properties['settings']['print_value'] )
					? filter_var( $form_properties['settings']['print_value'], FILTER_VALIDATE_BOOLEAN ) : false,
			'rules'               => $form_properties['validation_rules'],
			// this is string, todo: refactor this to array (ALL FIELDS will be affected) to avoid client JSON.parse.
			'messages'            => $form_properties['validation_messages'],
			// this is string, todo: refactor this to array (ALL FIELDS will be affected)  to avoid client JSON.parse.
			'conditions'          => $form_properties['conditions'],
			'calendar'            => $this->get_strings_for_calendar(),
			// this is string, todo: refactor this to array to (ALL FIELDS will be affected)  avoid client JSON.parse.
			'pagination_config'   => $form_properties['pagination'],
			'paypal_config'       => $form_properties['paypal_payment'],
			'forminator_fields'   => Forminator_Core::get_field_types(),
			'general_messages'    => array(
				'calculation_error'            => Forminator_Calculation::default_error_message(),
				'payment_require_ssl_error'    => apply_filters(
					'forminator_payment_require_ssl_error_message',
					esc_html__( 'SSL required to submit this form, please check your URL.', 'forminator' )
				),
				'payment_require_amount_error' => esc_html__( 'PayPal amount must be greater than 0.', 'forminator' ),
				'form_has_error'               => esc_html__( 'Please correct the errors before submission.', 'forminator' ),
			),
			'payment_require_ssl' => $this->model->is_payment_require_ssl(),
			'has_loader'          => $this->form_has_loader( $form_properties ),
			'loader_label'        => $this->get_loader_label( $form_properties ),
			'calcs_memoize_time'  => $this->get_memoize_time(),
			'is_reset_enabled'    => $this->is_reset_enabled(),
			'has_stripe'          => $has_stripe,
			'has_paypal'          => $this->has_paypal(),
			'submit_button_class' => esc_attr( $form_properties['submit_button_class'] ),
		);

		if ( ! empty( $this->lead_model ) && $this->has_lead( $this->lead_model->settings ) ) {
			$options['hasLeads']       = $this->has_lead( $this->lead_model->settings );
			$options['form_placement'] = $this->get_form_placement( $this->lead_model->settings );
			$options['leads_id']       = $this->get_leads_id( $this->lead_model->settings );
			$options['quiz_id']        = $this->lead_model->id;
		}

		if ( $has_stripe ) {
			$stripe_settings = $this->get_stripe_settings();
			if ( ! empty( $stripe_settings['automatic_payment_methods'] ) && 'false' !== $stripe_settings['automatic_payment_methods'] ) {
				$stripe_field              = Forminator_Core::get_field_object( 'stripe' );
				$options['stripe_depends'] = $stripe_field->get_amount_dependent_fields_all( $stripe_settings );
			} else {
				$options['stripe_depends'] = array();
			}
		}

		return $options;
	}

	/**
	 * Return calculations time in ms
	 *
	 * @since 1.11
	 *
	 * @return mixed
	 */
	public function get_memoize_time() {
		$default = 300; // Memoize time in ms.

		$time = apply_filters( 'forminator_calculation_memoize_time', $default );

		return $time;
	}

	/**
	 * Return if form reset after submit is enabled
	 *
	 * @since 1.12
	 *
	 * @return mixed
	 */
	public function is_reset_enabled() {
		$default = true; // Memoize time in ms.

		$value = apply_filters( 'forminator_is_form_reset_enabled', $default );

		return $value;
	}

	/**
	 * Return if form has submission loader enabled
	 *
	 * @param array $properties Properties.
	 *
	 * @since 1.7.1
	 *
	 * @return bool
	 */
	public function form_has_loader( $properties ) {
		if ( isset( $properties['settings']['submission-indicator'] ) && 'show' === $properties['settings']['submission-indicator'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return loader label
	 *
	 * @param array $properties Properties.
	 *
	 * @since 1.7.1
	 *
	 * @return mixed
	 */
	public function get_loader_label( $properties ) {
		if ( isset( $properties['settings']['indicator-label'] ) ) {
			return $properties['settings']['indicator-label'];
		}

		return esc_html__( 'Submitting...', 'forminator' );
	}

	/**
	 * Set options to Model object.
	 *
	 * @param object $form_model Model.
	 * @param array  $data Data.
	 * @return object
	 */
	protected function set_form_model_data( $form_model, $data ) {
		$fields = array();
		$title  = '';

		// Build the fields.
		if ( isset( $data ) ) {
			$fields = forminator_sanitize_field( $data['wrappers'] );
			unset( $data['wrappers'] );

			$title = ! empty( $data['settings']['formName'] ) ? sanitize_text_field( $data['settings']['formName'] ) : $title;
		}

		foreach ( $fields as $row ) {
			foreach ( $row['fields'] as $f ) {
				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['element_id'];

				$field->parent_group = ! empty( $row['parent_group'] ) ? $row['parent_group'] : '';
				$field->import( $f );
				$form_model->add_field( $field );
			}
		}

		// Sanitize custom css.
		if ( isset( $data['settings']['custom_css'] ) ) {
			$form_model->settings['custom_css'] = sanitize_textarea_field( $data['settings']['custom_css'] );
		}

		// Sanitize thank you message.
		if ( isset( $data['settings']['thankyou-message'] ) ) {
			$form_model->settings['thankyou-message'] = $data['settings']['thankyou-message'];
		}

		// Sanitize user email message.
		if ( isset( $data['settings']['user-email-editor'] ) ) {
			$form_model->settings['user-email-editor'] = $data['settings']['user-email-editor'];
		}

		$form_model->settings['formName'] = $title;

		return $form_model;
	}

	/**
	 * Html markup of form
	 *
	 * @since 1.6.1
	 *
	 * @param bool $hide Hide.
	 * @param bool $is_preview Is preview.
	 * @param int  $render_id Render Id.
	 *
	 * @return false|string
	 */
	public function get_html( $hide = true, $is_preview = false, $render_id = 0 ) {
		$form_settings = $this->model->settings;
		$form_type     = isset( $form_settings['form-type'] ) ? $form_settings['form-type'] : '';
		// Hide registration or login form for logged-in users if enabled.
		$hide_option = 'hide-' . $form_type . '-form';
		if ( ! $is_preview
			&& in_array( $form_type, array( 'login', 'registration' ), true ) && is_user_logged_in()
			&& isset( $form_settings[ $hide_option ] ) && '1' === $form_settings[ $hide_option ] ) {

			$hidden_message_option = 'hidden-' . $form_type . '-form-message';
			$html                  = isset( $form_settings[ $hidden_message_option ] )
				? $form_settings[ $hidden_message_option ]
				: esc_html__( 'User is logged in.', 'forminator' );

			return $html;
		}
		ob_start();
		if ( $this->model->form_is_visible( $is_preview ) ) {
			add_filter( 'forminator_render_form_submit_markup', array( $this, 'render_honeypot_field' ), 10, 4 );
			// Render form.
			$this->render( $this->model->id, $hide, $is_preview, $render_id );

			// setup properties for later usage.
			$this->set_forms_properties( $render_id );
		} else {
			$form_settings = $this->get_form_settings();
			?>
			<div class="forminator-custom-form">
				<?php
				if ( isset( $form_settings['expire_message'] ) && '' !== $form_settings['expire_message'] ) {
					$message = $form_settings['expire_message'];
					?>
					<label class="forminator-label--info"><span><?php echo esc_html( $message ); ?></span></label>
				<?php } ?>
			</div>
			<?php
		}

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Set module properties
	 *
	 * @param int|null $render_id Render Id.
	 */
	protected function set_forms_properties( $render_id = null ) {
		$submit_field = $this->get_submit_field();

		if ( is_null( $render_id ) ) {
			if ( empty( self::$render_ids ) || ! isset( self::$render_ids[ $this->model->id ] ) ) {
				$this->generate_render_id( $this->model->id );
			}
			$render_id = self::$render_ids[ $this->model->id ];
		}
		$submit_custom_data = '';
		if ( isset( $submit_field['custom-class'] ) ) {
			$submit_custom_data = $submit_field['custom-class'];
		}
		$this->forms_properties[] = array(
			'id'                  => $this->model->id,
			'render_id'           => $render_id,
			'inline_validation'   => $this->has_inline_validation() ? 'true' : 'false',
			'conditions'          => $this->get_conditions(),
			'validation_rules'    => $this->inline_rules,
			'validation_messages' => $this->inline_messages,
			'settings'            => $this->get_form_settings(),
			'pagination'          => $this->get_pagination_properties(),
			'paypal_payment'      => $this->get_paypal_properties(),
			'fonts_settings'      => $this->get_google_fonts(),
			'submit_button_class' => esc_attr( $submit_custom_data ),
		);
	}

	/**
	 * Check if form has a phone field
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function has_phone() {
		return $this->has_field_type( 'phone' );
	}

	/**
	 * Check if form has a postdata field
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function has_postdata() {
		return $this->has_field_type( 'postdata' );
	}

	/**
	 * Check if form has a stripe field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_stripe() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'stripe' === $field['type'] || 'stripe-ocs' === $field['type'] ) {
					$stripe = new Forminator_Gateway_Stripe();
					return $stripe->is_ready();
				}
			}
		}

		return false;
	}

	/**
	 * Get stripe settings
	 *
	 * @return array|bool
	 */
	public function get_stripe_settings() {
		$fields = $this->get_fields();
		$stripe = new Forminator_Gateway_Stripe();

		if ( empty( $fields ) || ! $stripe->is_ready() ) {
			return false;
		}
		// Filter elements where type is 'stripe-ocs'.
		$stripe_fields = array_filter(
			$fields,
			function ( $item ) {
				return 'stripe-ocs' === $item['type'];
			}
		);

		if ( empty( $stripe_fields ) ) {
			// filter elements where type is 'stripe'.
			$stripe_fields = array_filter(
				$fields,
				function ( $item ) {
					return 'stripe' === $item['type'];
				}
			);
		}

		if ( ! empty( $stripe_fields ) ) {
			return array_shift( $stripe_fields );
		}

		return false;
	}

	/**
	 * Is form has a group field with enabled repeater option?
	 *
	 * @return bool
	 */
	private function has_repeater() {
		return $this->has_field_type_with_setting_value( 'group', 'is_repeater', 'true' );
	}

	/**
	 * Check if form has a editor field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_editor() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$editor_type = Forminator_Field::get_property( 'editor-type', $field, false, 'bool' );
				if ( 'textarea' === $field['type'] && true === $editor_type ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form given field type
	 *
	 * @param string $type Field type.
	 *
	 * @since 1.14
	 * @return bool
	 */
	public function has_field_type( $type ) {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( $type === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check login form
	 *
	 * @return bool
	 */
	public function is_login_form() {
		$settings = $this->model->settings;

		if ( isset( $settings['form-type'] ) && 'login' === $settings['form-type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Render a message if form is hidden
	 *
	 * @since 1.11
	 *
	 * @param string $hidden_form_message Message.
	 *
	 * @return string
	 */
	public function render_hidden_form_message( $hidden_form_message ) {
		return apply_filters( 'forminator_render_hidden_form_message', $hidden_form_message );
	}

	/**
	 * Check if Custom form has upload field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_multiupload() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['type'] ) && 'upload' === $field['type'] &&
					isset( $field['file-type'] ) && 'multiple' === $field['file-type']
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get lead skip text
	 *
	 * @param array $form_settings Form settings.
	 *
	 * @return bool
	 */
	public function get_skip_text( $form_settings ) {
		$skip_text = ! empty( $form_settings['skip-text'] ) ? esc_html( $form_settings['skip-text'] ) : esc_html__( 'Skip and continue', 'forminator' );

		return $skip_text;
	}

	/**
	 * Render skip form content
	 *
	 * @return string
	 */
	public function render_skip_form_content() {
		$html          = '';
		$lead_settings = isset( $this->lead_model->settings ) ? $this->lead_model->settings : array();
		if ( ! empty( $lead_settings ) && $this->has_lead( $lead_settings ) && $this->has_skip_form( $lead_settings ) ) {
			$html .= '<div class="forminator-quiz--skip forminator-lead-form-skip">';
			$html .= sprintf( '<button>%s</button>', $this->get_skip_text( $lead_settings ) );
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Check if form has a formatting field
	 *
	 * @since 1.15.1
	 * @return bool
	 */
	public function has_formatting() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'number' === $field['type'] || 'currency' === $field['type'] || 'calculation' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get 2FA provider
	 *
	 * @return array[]
	 */
	public function get_2FA_poviders() {
		$defender_data    = defender_backward_compatibility();
		$two_fa_component = new $defender_data['two_fa_component']();
		$providers        = $two_fa_component->get_providers();

		return $providers;
	}

	/**
	 * Get Save draft button
	 *
	 * @param array $form_settings Form settings.
	 *
	 * @return string
	 */
	public function get_save_draft_button( $form_settings ) {
		if (
			! isset( $form_settings['use_save_and_continue'] ) ||
			! filter_var( $form_settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN )
		) {
			return;
		}

		$draft_permissions = isset( $form_settings['sc_permission'] ) ? $form_settings['sc_permission'] : 'public';
		if ( 'registered' === $draft_permissions && ! is_user_logged_in() ) {
			return;
		}

		$button = sprintf(
			'<a href="#" class="forminator-save-draft-link disabled" title="%s" formnovalidate>%s</a>',
			esc_attr__(
				'Fill in form fields before saving it as a draft',
				'forminator'
			),
			esc_html__(
				isset( $form_settings['sc_link_text'] ) && ! empty( $form_settings['sc_link_text'] ) ? esc_html( $form_settings['sc_link_text'] ) : 'Save as Draft', // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				'forminator'
			)
		);

		return $button;
	}
}
