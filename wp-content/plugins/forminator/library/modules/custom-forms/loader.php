<?php
/**
 * The Forminator_Custom_Forms class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Custom_Forms
 */
class Forminator_Custom_Forms extends Forminator_Module {

	/**
	 * Module instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Store template objects
	 *
	 * @var array
	 */
	public $templates = array();

	/**
	 * Return the plugin instance
	 *
	 * @since 1.0
	 * @return Forminator_Module
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 * @since 1.0.6 Include General Data Protecion
	 */
	public function init() {
		self::$instance = $this;

		$this->set_templates();

		if ( ! class_exists( 'Forminator_General_Data_Protection' ) ) {
			include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-general-data-protection.php';
		}
		include_once __DIR__ . '/protection/general-data-protection.php';
		if ( class_exists( 'Forminator_CForm_General_Data_Protection' ) ) {
			new Forminator_CForm_General_Data_Protection();
		}
		if ( ! class_exists( 'Forminator_CForm_User_Data' ) ) {
			include_once __DIR__ . '/user/class-forminator-cform-user-data.php';
			new Forminator_CForm_User_Data();
		}

		add_action( 'forminator_update_version', array( __CLASS__, 'migrate_leads_forms' ), 10, 2 );
	}

	/**
	 * Set templates
	 */
	private function set_templates() {
		$loader = new Forminator_Loader();

		$templates = $loader->load_files( 'library/modules/custom-forms/form-templates' );

		/**
		 * Filters the custom form templates list
		 */
		$this->templates = apply_filters( 'forminator_custom_form_templates', $templates );

		array_multisort(
			array_map(
				function ( $template ) {
					return $template->options['priority'];
				},
				$this->templates
			),
			SORT_ASC,
			$this->templates
		);
	}

	/**
	 * Load module Admin part
	 *
	 * @since 1.0
	 */
	public function load_admin() {
		if ( is_admin() ) {
			include_once __DIR__ . '/admin/admin-loader.php';

			new Forminator_Custom_Form_Admin();
		}
	}

	/**
	 * Load front part
	 *
	 * @since 1.0
	 */
	public function load_front() {

		include_once __DIR__ . '/front/front-action.php';
		include_once __DIR__ . '/front/front-render.php';
		include_once __DIR__ . '/front/front-user-login.php';
		include_once __DIR__ . '/front/front-user-registration.php';
		include_once __DIR__ . '/front/front-mail.php';
		include_once __DIR__ . '/front/front-assets.php';

		Forminator_CForm_Front_Action::get_instance();
		new Forminator_CForm_Front();

		add_action( 'wp_ajax_forminator_preset_templates', array( $this, 'get_preset_templates' ) );
		add_action( 'wp_ajax_forminator_update_live_preview', array( 'Forminator_CForm_Front', 'update_live_preview' ) );

		add_action( 'wp_ajax_forminator_load_form', array( 'Forminator_CForm_Front', 'ajax_load_module' ) );
		add_action( 'wp_ajax_nopriv_forminator_load_form', array( 'Forminator_CForm_Front', 'ajax_load_module' ) );
	}

	/**
	 * Register CPT
	 *
	 * @since 1.0
	 */
	public function register_cpt() {
		$labels = array(
			'name'          => $this->get_option( 'name' ),
			'singular_name' => $this->get_option( 'singular_name' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => $this->get_option( 'description' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array(),
		);

		register_post_type( 'forminator_forms', $args );

		// Register custom post_status.
		add_action( 'init', array( __CLASS__, 'add_custom_post_status' ) );
	}

	/**
	 * Add custom post status for Leads forms
	 */
	public static function add_custom_post_status() {
		register_post_status(
			'leads',
			array(
				'public'                    => false,
				'internal'                  => true,
				'post_type'                 => array( 'forminator_forms' ),
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'exclude_from_search'       => true,
			)
		);
	}

	/**
	 * Migrate leads forms
	 *
	 * @param string $new_version New plugin version.
	 * @param string $old_version Old plugin version.
	 * @return null
	 */
	public static function migrate_leads_forms( $new_version, $old_version ) {
		if ( version_compare( $old_version, '1.14.7', '>' ) ) {
			return;
		}
		Forminator_Migration::migrate_leads_forms();
	}

	/**
	 * Module options
	 *
	 * @since 1.0
	 * @return array
	 */
	public function options() {
		return array(
			'name'          => esc_html__( 'Custom Forms', 'forminator' ),
			'singular_name' => esc_html__( 'Custom Form', 'forminator' ),
			'description'   => esc_html__( 'Custom forms, conditional forms, etc. Choose from our library of forms or create a new one from scratch.', 'forminator' ),
			'button_label'  => esc_html__( 'New Custom Form', 'forminator' ),
			'icon'          => '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="19" viewBox="0 0 13 19" preserveAspectRatio="none" class="wpmudev-icon wpmudev-i_cforms"><path fill-rule="evenodd" d="M12.916 17.035v-.228.228c0 .305-.105.563-.316.774-.21.21-.47.315-.774.315H1.174c-.305 0-.563-.105-.774-.316-.21-.212-.316-.47-.316-.775V2.287v.21-.227c0-.305.105-.566.316-.783.21-.216.47-.325.774-.325H4.25V.46c0-.095.032-.174.097-.238.064-.065.143-.097.237-.097h3.85c.093 0 .172.032.237.097.065.064.098.143.098.237v.702h3.058c.305 0 .563.11.774.325.21.217.316.478.316.783v14.765zm-2.074-.984V3.237H9.805v.668-.08.08c0 .106-.035.194-.106.264-.07.07-.16.105-.264.105H3.564c-.105 0-.193-.035-.263-.105-.07-.07-.105-.158-.105-.264v-.668H2.14V16.05h8.702zm-6.61-8.77v.544c0 .06-.023.114-.07.167-.047.054-.105.08-.176.08H3.44c-.07 0-.127-.026-.174-.08-.047-.052-.07-.107-.07-.166V7.28c0-.07.023-.13.07-.176.047-.047.105-.07.175-.07h.546c.07 0 .13.023.176.07.047.046.07.105.07.175zm5.573 0v.527c0 .07-.024.13-.07.184-.048.054-.106.08-.176.08H5.532c-.07 0-.13-.026-.176-.08-.046-.052-.07-.113-.07-.183V7.28c0-.07.024-.133.07-.185.047-.053.106-.08.176-.08H9.56c.07 0 .127.027.174.08.047.052.07.114.07.184zM4.232 9.353v.544c0 .07-.023.13-.07.176-.047.047-.105.07-.176.07H3.44c-.057 0-.113-.023-.166-.07-.052-.047-.08-.105-.08-.176v-.544c0-.07.028-.13.08-.176.053-.047.11-.07.167-.07h.546c.07 0 .13.023.176.07.047.047.07.105.07.176zm5.573 0v.544c0 .07-.024.13-.07.176-.048.047-.106.07-.176.07H5.532c-.07 0-.13-.023-.176-.07-.046-.047-.07-.105-.07-.176v-.544c0-.07.024-.13.07-.176.047-.047.106-.07.176-.07H9.56c.07 0 .127.023.174.07.047.047.07.105.07.176zm-5.573 2.074v.545c0 .07-.023.13-.07.175-.047.047-.105.07-.176.07H3.44c-.057 0-.113-.023-.166-.07-.052-.046-.08-.105-.08-.175v-.545c0-.06.028-.115.08-.167.053-.052.11-.078.167-.078h.546c.07 0 .13.026.176.08.047.05.07.107.07.166zm5.573 0v.545c0 .07-.024.13-.07.175-.048.047-.106.07-.176.07H5.532c-.07 0-.13-.023-.176-.07-.046-.046-.07-.105-.07-.175v-.545c0-.06.024-.115.07-.167.047-.052.106-.078.176-.078H9.56c.07 0 .127.026.174.08.047.05.07.107.07.166z"/></svg>',
		);
	}

	/**
	 * Order templates by priorty
	 *
	 * @since 1.0
	 * @param object $b Template.
	 * @param object $a Template.
	 *
	 * @return int
	 */
	public function order_templates( $b, $a ) {
		if ( isset( $a->options['priority'] ) && isset( $b->options['priority'] ) ) {
			if ( $a->options['priority'] === $b->options['priority'] ) {
				return 0;
			}

			return ( $a->options['priority'] < $b->options['priority'] ) ? +1 : -1;
		}
	}

	/**
	 * Get templates
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_templates() {
		// Cache preset templates.
		$pro_templates = get_transient( 'forminator_get_preset_templates' );
		if ( ! $pro_templates ) {
			$pro_templates = Forminator_Template_API::get_templates( true );
			if ( $pro_templates ) {
				set_transient( 'forminator_get_preset_templates', $pro_templates, DAY_IN_SECONDS );
			}
		}
		$free_templates = $this->get_free_templates();
		$all_templates  = array_merge( $free_templates, $pro_templates );
		$all_templates  = self::prepare_templates_data( $all_templates );

		$all_templates = self::make_templates_info_translatable( $all_templates );

		return $all_templates;
	}

	/**
	 * Filter accessible templates
	 *
	 * @param array $templates Templates.
	 * @return array
	 */
	public function filter_accessible_templates( $templates ) {
		if ( ! current_user_can( 'create_users' ) ) {
			$templates = array_filter(
				$templates,
				function ( $item ) {
					return 'registration' !== $item['id'];
				}
			);
			// Reset array keys.
			$templates = array_values( $templates );
		}
		return $templates;
	}

	/**
	 * Make templates info translatable
	 *
	 * @param array $templates - templates.
	 *
	 * @return array
	 */
	public static function make_templates_info_translatable( $templates ) {
		foreach ( $templates as $key => $template ) {
			$templates[ $key ]['name']        = self::translate_template_name( $template['name'] );
			$templates[ $key ]['description'] = self::translate_template_description( $template['description'] );
		}

		return $templates;
	}

	/**
	 * Translate category name
	 *
	 * @param string $name - category name.
	 * @return string
	 */
	public static function translate_category_name( $name ) {
		$array = array(
			'All'                 => __( 'All', 'forminator' ),
			'Customer Service'    => __( 'Customer Service', 'forminator' ),
			'Marketing'           => __( 'Marketing', 'forminator' ),
			'Custom Form'         => __( 'Custom Form', 'forminator' ),
			'Business Operation'  => __( 'Business Operation', 'forminator' ),
			'Event Registration'  => __( 'Event Registration', 'forminator' ),
			'NGO'                 => __( 'NGO', 'forminator' ),
			'Education'           => __( 'Education', 'forminator' ),
			'Health and Wellness' => __( 'Health and Wellness', 'forminator' ),
		);
		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return $name;
	}

	/**
	 * Translate template name
	 *
	 * @param string $name - template name.
	 *
	 * @return string
	 */
	public static function translate_template_name( $name ) {
		$array = array(
			'Appointment Booking'                    => __( 'Appointment Booking', 'forminator' ),
			'Conference Registration Form'           => __( 'Conference Registration Form', 'forminator' ),
			'Course Enrollment Form'                 => __( 'Course Enrollment Form', 'forminator' ),
			'Course Evaluation Form'                 => __( 'Course Evaluation Form', 'forminator' ),
			'Customer Complaint Form'                => __( 'Customer Complaint Form', 'forminator' ),
			'Customer Feedback Form'                 => __( 'Customer Feedback Form', 'forminator' ),
			'Donation Form'                          => __( 'Donation Form', 'forminator' ),
			'Event Feedback Form'                    => __( 'Event Feedback Form', 'forminator' ),
			'Event Registration'                     => __( 'Event Registration', 'forminator' ),
			'Event Registration Template'            => __( 'Event Registration', 'forminator' ),
			'Event Registration Form'                => __( 'Event Registration', 'forminator' ),
			'Fitness Class Registration'             => __( 'Fitness Class Registration', 'forminator' ),
			'Home Service Request Form'              => __( 'Home Service Request Form', 'forminator' ),
			'Job Application Form'                   => __( 'Job Application Form', 'forminator' ),
			'Medical History Form'                   => __( 'Medical History Form', 'forminator' ),
			'Order Form for Small Businesses'        => __( 'Small Business Order Request', 'forminator' ),
			'Order Form for Small Business'          => __( 'Small Business Order Request', 'forminator' ),
			'Small Business Order Request'           => __( 'Small Business Order Request', 'forminator' ),
			'Real Estate Inquiry Form'               => __( 'Real Estate Inquiry Form', 'forminator' ),
			'Restaurant Reservation Form'            => __( 'Restaurant Reservation Form', 'forminator' ),
			'Return Merchandise Authorization (RMA)' => __( 'Return Merchandise Authorization (RMA)', 'forminator' ),
			'Return Merchandise Authorization Form'  => __( 'Return Merchandise Authorization (RMA)', 'forminator' ),
			'RSVP Form'                              => __( 'RSVP Form', 'forminator' ),
			'User Registration'                      => __( 'User Registration', 'forminator' ),
			'Travel Booking Form'                    => __( 'Travel Booking Form', 'forminator' ),
			'Volunteer Sign-up Form'                 => __( 'Volunteer Sign-up Form', 'forminator' ),
			'User Login'                             => __( 'User Login', 'forminator' ),
		);
		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return $name;
	}

	/**
	 * Translate template description
	 *
	 * @param string $description - template description.
	 *
	 * @return string
	 */
	public static function translate_template_description( $description ) {
		$array = array(
			'Allows clients to book appointments, with options for selecting services, dates, times, and personal information.' => __( 'Allows clients to book appointments, with options for selecting services, dates, times, and personal information.', 'forminator' ),
			'For registering attendees at conferences, including personal details, session choices, and payment options.' => __( 'For registering attendees at conferences, including personal details, session choices, and payment options.', 'forminator' ),
			'For enrolling in educational courses, including fields for course selection, personal details, and payment information.' => __( 'For enrolling in educational courses, including fields for course selection, personal details, and payment information.', 'forminator' ),
			'Designed for students to evaluate courses or training, with questions on content, instruction, and overall experience.' => __( 'Designed for students to evaluate courses or training, with questions on content, instruction, and overall experience.', 'forminator' ),
			'Allows customers to lodge complaints, with fields for issue description, desired resolution, and contact details.' => __( 'Allows customers to lodge complaints, with fields for issue description, desired resolution, and contact details.', 'forminator' ),
			'For gathering customer opinions on products or services, including rating scales and open-ended questions.' => __( 'For gathering customer opinions on products or services, including rating scales and open-ended questions.', 'forminator' ),
			'For charitable contributions, including options for donation amounts, donor information, and payment methods.' => __( 'For charitable contributions, including options for donation amounts, donor information, and payment methods.', 'forminator' ),
			'Allows attendees to provide feedback post-event, with questions on experience, organization, and suggestions.' => __( 'Allows attendees to provide feedback post-event, with questions on experience, organization, and suggestions.', 'forminator' ),
			'For event sign-ups, including sections for personal information, event preferences, and payment details.' => __( 'For event sign-ups, including sections for personal information, event preferences, and payment details.', 'forminator' ),
			'For signing up for fitness classes, including options for class types, schedules, and participant information.' => __( 'For signing up for fitness classes, including options for class types, schedules, and participant information.', 'forminator' ),
			'A form for requesting home services like cleaning or repairs, with fields for service type, dates, and client information.' => __( 'A form for requesting home services like cleaning or repairs, with fields for service type, dates, and client information.', 'forminator' ),
			'For job applicants, including sections for personal information, qualifications, experience, and references.' => __( 'For job applicants, including sections for personal information, qualifications, experience, and references.', 'forminator' ),
			'Collects a patient\'s medical history, with sections for conditions, medications, allergies, and family history.' => __( 'Collects a patient\'s medical history, with sections for conditions, medications, allergies, and family history.', 'forminator' ),
			'Tailored for small business orders, featuring fields for product selection, quantities, and customer information.' => __( 'Tailored for small business orders, featuring fields for product selection, quantities, and customer information.', 'forminator' ),
			'For potential buyers or renters to inquire about properties, including preferences, budget, and contact details.' => __( 'For potential buyers or renters to inquire about properties, including preferences, budget, and contact details.', 'forminator' ),
			'Lets customers book a table, with options for date, time, number of guests, and special requests.' => __( 'Lets customers book a table, with options for date, time, number of guests, and special requests.', 'forminator' ),
			'For processing returns, including fields for product information, reason for return, and customer details.' => __( 'For processing returns, including fields for product information, reason for return, and customer details.', 'forminator' ),
			'For confirming attendance at events, including options for guest names, contact information, and any dietary restrictions.' => __( 'For confirming attendance at events, including options for guest names, contact information, and any dietary restrictions.', 'forminator' ),
			'Allows users to book travel arrangements, with fields for destinations, dates, preferences, and traveler information.' => __( 'Allows users to book travel arrangements, with fields for destinations, dates, preferences, and traveler information.', 'forminator' ),
			'Designed for recruiting volunteers, including fields for personal details, availability, and areas of interest.' => __( 'Designed for recruiting volunteers, including fields for personal details, availability, and areas of interest.', 'forminator' ),
		);
		if ( isset( $array[ $description ] ) ) {
			return $array[ $description ];
		}

		return $description;
	}

	/**
	 * Get preset templates
	 */
	public function get_preset_templates() {
		forminator_validate_nonce_ajax( 'forminator_get_preset_templates' );

		$templates  = $this->get_templates();
		$categories = $this->get_templates_categories();

		wp_send_json_success(
			array(
				'templates'  => $templates,
				'categories' => $categories,
			)
		);
	}

	/**
	 * Prepare templates data
	 *
	 * @param array $templates - templates.
	 *
	 * @return array
	 */
	public function prepare_templates_data( $templates ) {
		foreach ( $templates as $key => $template ) {
			$path       = 'assets/images/templates/' . str_replace( '_', '-', $template['id'] ) . '.png';
			$screenshot = forminator_plugin_dir() . $path;
			if ( file_exists( $screenshot ) ) {
				$templates[ $key ]['screenshot'] = forminator_plugin_url() . $path;
				$templates[ $key ]['thumbnail']  = forminator_plugin_url() . $path;
			}
		}

		return $templates;
	}

	/**
	 * Get free templates
	 *
	 * @return array
	 */
	public function get_free_templates() {
		$templates = array_column( $this->templates, 'options' );

		$templates = array_values(
			array_filter(
				$templates,
				function ( $item ) {
					return ! empty( $item['id'] ) && 'leads' !== $item['id'];
				}
			)
		);
		return $this->filter_accessible_templates( $templates );
	}

	/**
	 * Get main templates
	 *
	 * @return array
	 */
	public function get_main_templates(): array {
		$templates = $this->get_free_templates();

		// get only first 4.
		$templates = array_slice( $templates, 0, 4 );

		return self::prepare_templates_data( $templates );
	}

	/**
	 * Get Template Category
	 *
	 * @return array
	 */
	public function get_templates_categories(): array {
		$transient_key = 'forminator_get_templates_categories';
		// cache result.
		$hub_categories = get_transient( $transient_key );
		if ( ! $hub_categories ) {
			$hub_categories = Forminator_Template_API::get_categories();
			if ( $hub_categories ) {
				set_transient( $transient_key, $hub_categories, WEEK_IN_SECONDS );
			}
		}

		$categories = $this->add_free_templates_category( $hub_categories );

		// Sort categories by templates count.
		usort(
			$categories,
			function ( $a, $b ) {
				return $b['templates_count'] <=> $a['templates_count'];
			}
		);

		array_unshift(
			$categories,
			array(
				'slug'            => 'all',
				'name'            => 'All',
				'templates_count' => array_sum( wp_list_pluck( $categories, 'templates_count' ) ) + 1,
				// Plus Blank template.
			)
		);

		// Remove empty categories.
		$categories = array_filter(
			$categories,
			function ( $category ) {
				return $category['templates_count'] > 0;
			}
		);

		// Translate categories.
		$categories = array_map(
			function ( $category ) {
				$category['name'] = self::translate_category_name( $category['name'] );

				return $category;
			},
			$categories
		);

		return $categories;
	}

	/**
	 * Add free templates category
	 *
	 * @param array $categories - categories.
	 *
	 * @return array
	 */
	private function add_free_templates_category( $categories ) {
		$free_templates  = $this->get_free_templates();
		$free_categories = wp_list_pluck( $free_templates, 'category' );

		$summary = array_count_values( $free_categories );
		foreach ( $categories as $key => $category ) {
			if ( isset( $summary[ $category['slug'] ] ) ) {
				$categories[ $key ]['templates_count'] += $summary[ $category['slug'] ];
			}
		}

		return $categories;
	}

	/**
	 * Get Pro static template categories
	 *
	 * @return array
	 */
	private function get_pro_static_template_categories(): array {
		$categories = array(
			array(
				'slug' => 'customer-service',
				'name' => esc_html__( 'Customer Service', 'forminator' ),
			),
			array(
				'slug' => 'marketing',
				'name' => esc_html__( 'Marketing', 'forminator' ),
			),
			array(
				'slug' => 'custom-form',
				'name' => esc_html__( 'Custom Form', 'forminator' ),
			),
			array(
				'slug' => 'business-operation',
				'name' => esc_html__( 'Business Operation', 'forminator' ),
			),
			array(
				'slug' => 'event-registration',
				'name' => esc_html__( 'Event Registration', 'forminator' ),
			),
			array(
				'slug' => 'ngo',
				'name' => esc_html__( 'NGO', 'forminator' ),
			),
			array(
				'slug' => 'education',
				'name' => esc_html__( 'Education', 'forminator' ),
			),
			array(
				'slug' => 'health',
				'name' => esc_html__( 'Health', 'forminator' ),
			),
		);

		foreach ( $categories as $key => $category ) {
			$categories[ $key ]['templates_count'] = $this->get_templates_count_by_category( $category['slug'] );
		}

		return $categories;
	}

	/**
	 * Get Template count
	 *
	 * @param string $category Category.
	 *
	 * @return int
	 */
	public function get_templates_count_by_category( $category ) {
		$all_templates = $this->get_templates();
		$templates     = array_filter(
			$all_templates,
			function ( $item ) use ( $category ) {
				return 'all' === $category || ( isset( $item['category'] ) && $item['category'] === $category );
			}
		);

		return count( $templates );
	}
}
