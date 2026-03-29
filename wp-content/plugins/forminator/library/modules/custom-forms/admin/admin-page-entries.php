<?php
/**
 * The Forminator_CForm_View_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_View_Page
 *
 * @since 1.0
 */
class Forminator_CForm_View_Page extends Forminator_Admin_View_Page {

	/**
	 * Current model id
	 *
	 * @var int
	 */
	protected $model_id = 0;

	/**
	 * Moduel type
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Nested Mappers
	 *
	 * @var array
	 */
	protected $fields_mappers = array();

	/**
	 * Flatten version of mappers
	 *
	 * @var array
	 */
	protected $flatten_field_mappers = array();

	/**
	 * Custom form model
	 *
	 * @var Forminator_Form_Model|null
	 */
	protected static $custom_form_model = null;

	/**
	 * Initialise variables
	 *
	 * @param int|null $form_id Form id.
	 *
	 * @since 1.0
	 */
	public function before_render( $form_id = null ) {
		$this->maybe_redirect();

		$request_form_id = (int) Forminator_Core::sanitize_text_field( 'form_id' );
		if ( $request_form_id ) {
			$this->model_id = $request_form_id;
			$this->form_id  = ! empty( $form_id ) ? $form_id : $request_form_id;
			parent::before_render();
			$form_id                 = (int) $this->form_id;
			$custom_form_model       = $this->model;
			self::$custom_form_model = $custom_form_model;
			$visible_fields          = $this->get_visible_fields();

			/**
			 * Fires on custom form page entries render before request and result processed
			 *
			 * @since 1.1
			 *
			 * @param int                          $form_id           Current Form ID.
			 * @param Forminator_Form_Model $custom_form_model Current Form Model.
			 * @param array                        $visible_fields    Visible fields on page.
			 * @param int                          $pagenum           current page number.
			 */
			do_action(
				'forminator_custom_form_admin_page_entries',
				$form_id,
				$custom_form_model,
				$visible_fields,
				$this->pagenum
			);

			$this->process_request();
			$this->prepare_results();
		}
	}

	/**
	 * Action delete_all
	 */
	public function delete_all_action() {
		$entry = isset( $_GET['entry'] ) ? Forminator_Core::sanitize_array( $_GET['entry'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $entry ) ) {
			$entries = implode( ',', $entry );
			Forminator_Form_Entry_Model::delete_by_entrys( $this->model->id, $entries );
			$this->delete_signup_user_entry( $entry );
			$this->maybe_redirect_to_referer();
			exit;
		}
	}

	/**
	 * Action approve_users
	 */
	public function approve_users() {
		$entries = isset( $_GET['entry'] ) ? Forminator_Core::sanitize_array( $_GET['entry'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		if ( ! empty( $entries ) ) {
			$users_approved         = 0;
			$email_activation_users = 0;
			foreach ( $entries as $entry_id ) {
				$entry             = new Forminator_Form_Entry_Model( $entry_id );
				$activation_key    = $entry->get_meta( 'activation_key' );
				$activation_method = $entry->get_meta( 'activation_method' );
				if ( ! $activation_key ) {
					continue;
				} elseif ( 'email' === $activation_method ) {
					++$email_activation_users;
				} elseif ( 'manual' === $activation_method ) {
					require_once dirname( __DIR__ ) . '/user/class-forminator-cform-user-signups.php';
					$userdata = Forminator_CForm_User_Signups::activate_signup( $activation_key, true );
					if ( ! is_wp_error( $userdata ) ) {
						++$users_approved;
					}
				} else {
					continue;
				}
			}
			/* Translators: 1. Approved users. */
			$notice = sprintf( esc_html__( '%s users approved successfully.', 'forminator' ), '<strong>' . $users_approved . '</strong>' );
			if ( $email_activation_users ) {
				/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
				$notice .= '<br>' . sprintf( esc_html__( '%1$sNote:%2$s This action does not apply to user accounts awaiting email activation.', 'forminator' ), '<strong>', '</strong>' );
			}
			$args = array(
				'page'      => $this->get_admin_page(),
				'form_type' => $this->get_form_type(),
				'form_id'   => $this->get_form_id(),
			);

			$args['forminator_text_notice'] = rawurlencode( $notice );

			$fallback_redirect = add_query_arg(
				$args,
				admin_url( 'admin.php' )
			);
			$this->maybe_redirect_to_referer( $fallback_redirect, false );
			exit;
		}
	}

	/**
	 * Return visible fields as string
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_visible_fields_as_string() {
		return implode( ',', $this->visible_fields );
	}

	/**
	 * Show a field if selected
	 *
	 * @since 1.0
	 *
	 * @param string $slug - the field slug.
	 *
	 * @return bool
	 */
	public function is_selected_field( $slug ) {
		if ( ! empty( $this->visible_fields ) && is_array( $this->visible_fields ) ) {
			if ( in_array( $slug, $this->visible_fields, true ) ) {
				return true;
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Render entry
	 *
	 * @since 1.0
	 *
	 * @param object $item        - the entry.
	 * @param string $column_name - the column name.
	 *
	 * @param null   $field       @since 1.0.5, optional Forminator_Form_Field_Model.
	 *
	 * @return string
	 * TO-DO: replace Forminator_CForm_View_Page::render_entry() by render_entry() on other files
	 */
	public static function render_entry( $item, $column_name, $field = null ) {
		return render_entry( $item, $column_name, $field );
	}

	/**
	 * Render entry values raw
	 *
	 * @since 1.0
	 *
	 * @param object $item        - the entry.
	 * @param string $column_name - the column name.
	 *
	 * @return mixed
	 */
	public static function render_raw_entry( $item, $column_name ) {
		$data = $item->get_meta( $column_name, '' );
		if ( $data ) {
			if ( is_array( $data ) ) {
				$output       = '';
				$product_cost = 0;
				$is_product   = false;

				foreach ( $data as $key => $value ) {
					if ( is_array( $value ) ) {
						if ( 'file' === $key && isset( $value['file_url'] ) ) {
							$output .= $value['file_url'] . ', ';
						}
					} elseif ( ! is_int( $key ) ) {
						if ( 'postdata' === $key ) {
							$output .= "$value, ";
						} elseif ( is_string( $key ) ) {

							if ( 'product-id' === $key || 'product-quantity' === $key ) {
								if ( 0 === $product_cost ) {
									$product_cost = $value;
								} else {
									$product_cost = $product_cost * $value;
								}
								$is_product = true;
							} else {
								$output .= "$value $key , ";
							}
						}
					}
				}
				if ( $is_product ) {
					$output = $product_cost;
				} elseif ( ! empty( $output ) ) {
						$output = substr( trim( $output ), 0, - 1 );
				} else {
					$output = implode( ',', $data );
				}

				return $output;
			} else {
				return $data;
			}
		}

		return '';
	}

	/**
	 * Mimic from export
	 *
	 * @see Forminator_Export::get_custom_form_export_mappers()
	 * TODO: decouple this function so it can be called on multiple occasions (export, entries render) with single place to update
	 *
	 * @return array
	 */
	private function build_fields_mappers() {
		/**
		 * Forminator_Form_Model
		 *
		 * @var  Forminator_Form_Model $model */
		$model  = $this->model;
		$fields = apply_filters( 'forminator_custom_form_build_fields_mappers', $model->get_grouped_real_fields() );

		/**
		 * Forminator_Form_Field_Model
		 *
		 * @var  Forminator_Form_Field_Model $fields */
		$mappers = array(
			array(
				// read form model's property.
				'property' => 'entry_id', // must be on entries.
				'label'    => esc_html__( 'ID', 'forminator' ),
				'type'     => 'entry_entry_id',
			),
			array(
				// read form model's property.
				'property' => 'time_created', // must be on entries.
				'label'    => esc_html__( 'Date Submitted', 'forminator' ),
				'type'     => 'entry_time_created',
			),
		);

		foreach ( $fields as $field ) {
			$mapper = $this->build_field_mapper( $field );

			if ( ! empty( $mapper ) ) {
				$mappers[] = $mapper;
			}
		}

		return $mappers;
	}

	/**
	 * Build field
	 *
	 * @param object $field Field object.
	 * @return array
	 */
	private function build_field_mapper( $field ) {
		$visible_fields = $this->get_visible_fields();

		$field_type = $field->__get( 'type' );

		if ( ! empty( $visible_fields ) ) {
			if ( ! in_array( $field->slug, $visible_fields, true ) ) {
				return array();
			}
		}

		// base mapper for every field.
		$mapper             = array();
		$mapper['meta_key'] = $field->slug; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- false positive
		$mapper['label']    = $field->get_label_for_entry();
		$mapper['type']     = $field_type;
		$mapper['field']    = $field->to_array();

		if ( 'textarea' === $field_type ) {
			$field_array    = $field->to_array();
			$mapper['rich'] = isset( $field_array['editor-type'] ) ? $field_array['editor-type'] : false;
		} elseif ( 'number' === $field_type || 'currency' === $field_type || 'calculation' === $field_type ) {
			$field_array = $field->to_array();
			$separator   = Forminator_Field::get_property( 'separators', $field_array, 'blank' );
			$precision   = Forminator_Field::get_calculable_precision( $field_array );
			$separators  = Forminator_Field::forminator_separators( $separator, $field_array );

			$mapper['separator'] = $separators['separator'];
			$mapper['point']     = $separators['point'];
			$mapper['precision'] = $precision;
		} elseif ( 'name' === $field_type ) {
			// fields that should be displayed as multi column (sub_metas).
			$is_multiple_name = filter_var( $field->__get( 'multiple_name' ), FILTER_VALIDATE_BOOLEAN );
			if ( $is_multiple_name ) {
				$prefix_enabled      = filter_var( $field->__get( 'prefix' ), FILTER_VALIDATE_BOOLEAN );
				$first_name_enabled  = filter_var( $field->__get( 'fname' ), FILTER_VALIDATE_BOOLEAN );
				$middle_name_enabled = filter_var( $field->__get( 'mname' ), FILTER_VALIDATE_BOOLEAN );
				$last_name_enabled   = filter_var( $field->__get( 'lname' ), FILTER_VALIDATE_BOOLEAN );
				// at least one sub field enabled.
				if ( $prefix_enabled || $first_name_enabled || $middle_name_enabled || $last_name_enabled ) {
					// sub metas.
					$mapper['sub_metas'] = array();
					if ( $prefix_enabled ) {
						$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'prefix' );
						$label                 = $field->__get( 'prefix_label' );
						$mapper['sub_metas'][] = array(
							'key'   => 'prefix',
							'label' => ( $label ? $label : $default_label ),
						);
					}

					if ( $first_name_enabled ) {
						$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'first-name' );
						$label                 = $field->__get( 'fname_label' );
						$mapper['sub_metas'][] = array(
							'key'   => 'first-name',
							'label' => ( $label ? $label : $default_label ),
						);
					}

					if ( $middle_name_enabled ) {
						$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'middle-name' );
						$label                 = $field->__get( 'mname_label' );
						$mapper['sub_metas'][] = array(
							'key'   => 'middle-name',
							'label' => ( $label ? $label : $default_label ),
						);
					}
					if ( $last_name_enabled ) {
						$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'last-name' );
						$label                 = $field->__get( 'lname_label' );
						$mapper['sub_metas'][] = array(
							'key'   => 'last-name',
							'label' => ( $label ? $label : $default_label ),
						);
					}
				} else {
					// if no succbvgbfield enabled when multiple name remove mapper (means dont show it on export).
					$mapper = array();
				}
			}
		} elseif ( 'address' === $field_type ) {
			$street_enabled  = filter_var( $field->__get( 'street_address' ), FILTER_VALIDATE_BOOLEAN );
			$line_enabled    = filter_var( $field->__get( 'address_line' ), FILTER_VALIDATE_BOOLEAN );
			$city_enabled    = filter_var( $field->__get( 'address_city' ), FILTER_VALIDATE_BOOLEAN );
			$state_enabled   = filter_var( $field->__get( 'address_state' ), FILTER_VALIDATE_BOOLEAN );
			$zip_enabled     = filter_var( $field->__get( 'address_zip' ), FILTER_VALIDATE_BOOLEAN );
			$country_enabled = filter_var( $field->__get( 'address_country' ), FILTER_VALIDATE_BOOLEAN );
			if ( $street_enabled || $line_enabled || $city_enabled || $state_enabled || $zip_enabled || $country_enabled ) {
				$mapper['sub_metas'] = array();
				if ( $street_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'street_address' );
					$label                 = $field->__get( 'street_address_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'street_address',
						'label' => ( $label ? $label : $default_label ),
					);
				}
				if ( $line_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'address_line' );
					$label                 = $field->__get( 'address_line_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'address_line',
						'label' => ( $label ? $label : $default_label ),
					);
				}
				if ( $city_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'city' );
					$label                 = $field->__get( 'address_city_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'city',
						'label' => ( $label ? $label : $default_label ),
					);
				}
				if ( $state_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'state' );
					$label                 = $field->__get( 'address_state_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'state',
						'label' => ( $label ? $label : $default_label ),
					);
				}
				if ( $zip_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'zip' );
					$label                 = $field->__get( 'address_zip_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'zip',
						'label' => ( $label ? $label : $default_label ),
					);
				}
				if ( $country_enabled ) {
					$default_label         = Forminator_Form_Entry_Model::translate_suffix( 'country' );
					$label                 = $field->__get( 'address_country_label' );
					$mapper['sub_metas'][] = array(
						'key'   => 'country',
						'label' => ( $label ? $label : $default_label ),
					);
				}
			} else {
				// if no subfield enabled when multiple name remove mapper (means dont show it on export).
				$mapper = array();
			}
		} elseif ( 'stripe' === $field_type || 'stripe-ocs' === $field_type ) {
			$mapper['label']         = esc_html__( 'Stripe Payment', 'forminator' );
			$mapper['sub_metas']     = array();
			$mapper['sub_metas'][]   = array(
				'key'                => 'mode',
				'label'              => esc_html__( 'Mode', 'forminator' ),
				'transform_callback' => 'strtoupper',
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'product_name',
				'label' => esc_html__( 'Product / Plan Name', 'forminator' ),
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'payment_type',
				'label' => esc_html__( 'Payment type', 'forminator' ),
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'amount',
				'label' => esc_html__( 'Amount', 'forminator' ),
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'currency',
				'label' => esc_html__( 'Currency', 'forminator' ),
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'quantity',
				'label' => esc_html__( 'Quantity', 'forminator' ),
			);
			$transaction_link_mapper = array(
				'key'   => 'transaction_id',
				'label' => esc_html__( 'Transaction ID', 'forminator' ),
			);
			if ( class_exists( 'Forminator_Stripe' ) ) {
				$transaction_link_mapper['transform_callback'] = array( 'Forminator_Stripe', 'linkify_transaction_id' );
				$transaction_link_mapper['num_transform_arg']  = 2;
			}
			$mapper['sub_metas'][] = $transaction_link_mapper;
			$mapper['sub_metas'][] = array(
				'key'                => 'status',
				'label'              => esc_html__( 'Status', 'forminator' ),
				'transform_callback' => 'ucfirst',
			);
			if ( class_exists( 'Forminator_Stripe_Subscription' ) ) {
				$manage_mapper                       = array(
					'key'   => 'subscription_id',
					'label' => esc_html__( 'Manage', 'forminator' ),
				);
				$manage_mapper['transform_callback'] = array( 'Forminator_Stripe_Subscription', 'manage_subscription' );
				$manage_mapper['num_transform_arg']  = 2;

				$mapper['sub_metas'][] = $manage_mapper;
			}
		} elseif ( 'paypal' === $field_type ) {
			$mapper['label']         = esc_html__( 'PayPal Checkout', 'forminator' );
			$mapper['sub_metas']     = array();
			$mapper['sub_metas'][]   = array(
				'key'                => 'mode',
				'label'              => esc_html__( 'Mode', 'forminator' ),
				'transform_callback' => 'strtoupper',
			);
			$mapper['sub_metas'][]   = array(
				'key'                => 'status',
				'label'              => esc_html__( 'Status', 'forminator' ),
				'transform_callback' => 'ucfirst',
			);
			$mapper['sub_metas'][]   = array(
				'key'   => 'amount',
				'label' => esc_html__( 'Amount', 'forminator' ),
			);
			$mapper['sub_metas'][]   = array(
				'key'                => 'currency',
				'label'              => esc_html__( 'Currency', 'forminator' ),
				'transform_callback' => 'strtoupper',
			);
			$transaction_link_mapper = array(
				'key'   => 'transaction_id',
				'label' => esc_html__( 'Transaction ID', 'forminator' ),
			);
			if ( class_exists( 'Forminator_PayPal' ) ) {
				$transaction_link_mapper['transform_callback'] = array( 'Forminator_PayPal', 'linkify_transaction_id' );
				$transaction_link_mapper['num_transform_arg']  = 2;
			}
			$mapper['sub_metas'][] = $transaction_link_mapper;
		} elseif ( 'group' === $field_type ) {
			$group_fields = $this->model->get_grouped_real_fields( $field->__get( 'slug' ) );

			$mapper['sub_metas'] = array();
			foreach ( $group_fields as $group_field ) {
				$field_mapper = $this->build_field_mapper( $group_field );
				if ( ! empty( $field_mapper ) ) {
					$field_mapper['key']   = $field_mapper['meta_key'];
					$mapper['sub_metas'][] = $field_mapper;
				}
			}
		} elseif ( 'rating' === $field_type ) {
			$mapper['max_rating'] = $field->__get( 'max_rating' );
			$mapper['suffix']     = $field->__get( 'suffix' );
			$mapper['icon']       = $field->__get( 'icon' );
			$mapper['size']       = $field->__get( 'size' );
		}

		return $mapper;
	}

	/**
	 * Get Fields Mappers based on current state of form
	 *
	 * @return array
	 */
	public function get_fields_mappers() {
		if ( empty( $this->fields_mappers ) ) {
			$fields_mappers = $this->build_fields_mappers();
			/**
			 * Filter fields mappers
			 *
			 * @param array  $fields_mappers Fields mappers.
			 * @param object $model Forminator_Form_Model object.
			 */
			$this->fields_mappers = apply_filters( 'forminator_fields_mappers', $fields_mappers, $this->model );
		}

		return $this->fields_mappers;
	}

	/**
	 * Get flatten fields mappers
	 *
	 * @return array
	 */
	public function get_flatten_fields_mappers() {
		if ( empty( $this->flatten_field_mappers ) ) {
			$fields_mappers = $this->get_fields_mappers();
			// flatten field mappers for multi field.
			$flatten_fields_mappers = array();
			foreach ( $fields_mappers as $fields_mapper ) {
				if ( ! isset( $fields_mapper['sub_metas'] ) ) {
					$flatten_fields_mappers[] = $fields_mapper;
				} else {
					foreach ( $fields_mapper['sub_metas'] as $sub_meta ) {
						$sub_meta['parent']       = $fields_mapper;
						$flatten_fields_mappers[] = $sub_meta;
					}
				}
			}

			$this->flatten_field_mappers = $flatten_fields_mappers;

		}

		return $this->flatten_field_mappers;
	}

	/**
	 * Build Html Entries Header
	 */
	public function entries_header() {

		$flatten_fields_mappers = $this->get_flatten_fields_mappers();

		// start from 2, since first two is ID and Date.
		// length is 2 because we only display first two fields only.
		$fields_headers = array_slice( $flatten_fields_mappers, 2, 2 );

		// minus by header fields.
		$actual_num_fields = count( $flatten_fields_mappers ) - 2;
		$fields_left       = $actual_num_fields - count( $fields_headers );
		?>
		<thead>

			<th>
				<label class="sui-checkbox">
					<input type="checkbox" id="wpf-cform-check_all">
					<span></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all entries', 'forminator' ); ?></span>
				</label>
				<?php esc_html_e( 'ID', 'forminator' ); ?>
			</th>

			<th><?php esc_html_e( 'Date Submitted', 'forminator' ); ?></th>

			<?php
			foreach ( $fields_headers as $header ) {
				?>

				<th>
				<?php
					// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html is used inside convert_markdown.
					echo Forminator_Field::convert_markdown( esc_html( $header['label'] ) );
				?>
				</th>

				<?php
			}

			if ( $fields_left > 0 ) {
				?>

				<th data-num-hidden-fields="<?php echo esc_attr( $fields_left ); ?>" aria-label="<?php esc_attr_e( 'Other fields', 'forminator' ); ?>"></th>

			<?php } ?>

		</thead>

		<?php
	}


	/**
	 * Entries iterator
	 *
	 * @param null|array $entries Entries.
	 * @param string     $form_type Form type.
	 *
	 * @return array
	 */
	public function entries_iterator( $entries = null, $form_type = '' ) {
		/**
		 * Example
		 *
		 * @example
		 * {
		 *  id => 'ENTRY_ID'
		 *  summary = [
		 *      'num_fields_left' => true/false,
		 *      'items' => [
		 *          [
		 *              'colspan' => 2/...,
		 *              'value' => '----',
		 *          ]
		 *          [
		 *              'colspan' => 2/...
		 *              value' => '----',
		 *          ]
		 *      ],
		 *  ],
		 *  detail = [
		 *      'colspan' => '',
		 *      'items' => [
		 *          [
		 *              'label' => '----',
		 *              'value' => '-----'
		 *              'sub_entries' => [
		 *                  [
		 *                      'label' => '----',
		 *                      'value' => '-----'
		 *                  ]
		 *              ]
		 *          ]
		 *          [
		 *              'label' => '----',
		 *              'value' => '-----'
		 *          ]
		 *      ],
		 * ]
		 * }
		 */
		$entries_iterator = array();

		$total_colspan          = 2; // Colspan for ID + Date Submitted.
		$fields_mappers         = $this->get_fields_mappers();
		$flatten_fields_mappers = $this->get_flatten_fields_mappers();

		// start from 2, since first two is ID and Date.
		// length is 2 because we only display first two fields only.
		$fields_headers    = array_slice( $flatten_fields_mappers, 2, 2 );
		$actual_num_fields = count( $flatten_fields_mappers ) - 2;
		$fields_left       = $actual_num_fields - count( $fields_headers );

		$total_colspan += count( $fields_headers ); // 2 for each header colspan.
		if ( $fields_left > 0 ) {
			++$total_colspan;
		}

		// all headers including Id + Date, start from 0 and max is 4.
		$headers = array_slice( $flatten_fields_mappers, 0, 4 );

		$numerator_id = $this->total_entries;
		if ( $this->page_number > 1 ) {
			$numerator_id = $this->total_entries - ( ( $this->page_number - 1 ) * $this->per_page );
		}

		if ( ! empty( $entries ) ) {
			$this->entries = $entries;
		}

		foreach ( $this->entries as $entry ) {
			/**
			 * Forminator_Form_Entry_Model
			 *
			 * @var Forminator_Form_Entry_Model $entry */

			// create placeholder.
			$iterator = array(
				'id'         => $numerator_id,
				'entry_id'   => $entry->entry_id,
				'draft_id'   => $entry->draft_id,
				'entry_date' => $entry->time_created,
				'status'     => $entry->status,
				'summary'    => array(),
				'detail'     => array(),
			);

			$iterator['summary']['num_fields_left'] = $fields_left;
			$iterator['summary']['items']           = array();

			$iterator['detail']['colspan'] = $total_colspan;
			$iterator['detail']['items']   = array();

			// Build array for summary row.
			$summary_items = array();
			foreach ( $headers as $header ) {
				$colspan = 2;
				if ( isset( $header['type'] ) && 'entry_entry_id' === $header['type'] ) {
					$summary_items[] = array(
						'colspan' => 1,
						'value'   => $numerator_id,
					);
					continue;
				} elseif ( isset( $header['type'] ) && 'entry_time_created' === $header['type'] ) {
					$colspan = 3;
				}

				if ( isset( $header['parent'] ) ) {
					$value = forminator_get_entry_field_value( $entry, $header['parent'], $header['key'], false, 100 );
				} else {
					$value = forminator_get_entry_field_value( $entry, $header, '', false, 100 );
				}
				$summary_items[] = array(
					'colspan' => $colspan,
					'value'   => $value,
				);
			}

			// Build array for -content row.
			$detail_items = array();

			foreach ( $fields_mappers as $mapper ) {
				// skip entry id.
				if ( isset( $mapper['type'] ) && 'entry_entry_id' === $mapper['type'] ) {
					continue;
				}

				if ( 'quiz' === $form_type && isset( $mapper['type'] ) && 'entry_time_created' === $mapper['type'] ) {
					continue;
				}

				$type        = $mapper['type'];
				$label       = $mapper['label'];
				$value       = '';
				$sub_entries = array();

				if ( ! isset( $mapper['sub_metas'] ) ) {
					$value = forminator_get_entry_field_value( $entry, $mapper, '', true );
				} else {
					$sub_entries = self::get_sub_entries( $mapper, $entry );
				}

				$detail_args = array(
					'type'        => $type,
					'label'       => $label,
					'value'       => $value,
					'rich'        => isset( $mapper['rich'] ) ? $mapper['rich'] : false,
					'sub_entries' => $sub_entries,
				);

				if ( 'group' === $mapper['type'] && ! empty( $mapper['sub_metas'] ) ) {
					$original_keys       = wp_list_pluck( $mapper['sub_metas'], 'key' );
					$repeated_group_keys = forminator_get_cloned_field_keys( $entry, $original_keys );
					foreach ( $repeated_group_keys as $slug ) {
						$sub_entries = self::get_sub_entries( $mapper, $entry, $slug );

						$detail_args[ 'sub_entries' . $slug ] = $sub_entries;
					}
					$detail_args['repeated_group_keys'] = array_merge( array( '' ), $repeated_group_keys );
				}

				if ( ! empty( $mapper['separator'] ) || ! empty( $mapper['point'] ) ) {
					$detail_args['separator'] = $mapper['separator'];
					$detail_args['point']     = $mapper['point'];
					$detail_args['precision'] = $mapper['precision'];
				}
				if ( 'rating' === $mapper['type'] ) {
					$detail_args['size']       = $mapper['size'];
					$detail_args['icon']       = $mapper['icon'];
					$detail_args['suffix']     = $mapper['suffix'];
					$detail_args['max_rating'] = $mapper['max_rating'];
				}

				if ( ! empty( $mapper['separators'] ) ) {
					$detail_args['separators'] = $mapper['separators'];
				}

				$detail_items[] = $detail_args;
			}

			if ( 'quiz' === $form_type ) {
				$addons_detail_items = $this->attach_addon_on_quiz_render_entry( $entry );
				if ( isset( $entry->meta_data['skip_form']['value'] ) && $entry->meta_data['skip_form']['value'] ) {
					$detail_items = array();
				}
				$iterator['detail']['items']        = $detail_items;
				$iterator['detail']['integrations'] = $addons_detail_items;
				$iterator['detail']['quiz_entry']   = isset( $entry->meta_data['entry'] ) ? $entry->meta_data['entry'] : array();
				$iterator['detail']['quiz_url']     = isset( $entry->meta_data['quiz_url'] ) ? $entry->meta_data['quiz_url'] : array();
			} else {
				// Additional render for addons.
				$addons_detail_items = $this->attach_addon_on_render_entry( $entry );
				$merge_detail_items  = array_merge( $detail_items, $addons_detail_items );

				$iterator['summary']['items'] = $summary_items;
				$iterator['detail']['items']  = $merge_detail_items;
			}

			$iterator['summary']['items'] = $summary_items;

			$iterator = apply_filters( 'forminator_custom_form_entries_iterator', $iterator, $entry );

			$entries_iterator[] = $iterator;
			--$numerator_id;
		}

		return $entries_iterator;
	}

	/**
	 * Get sub entries
	 *
	 * @param array  $mapper Field Mapper.
	 * @param object $entry Entry object.
	 * @param string $slug Cloned field suffix.
	 * @return array
	 */
	private static function get_sub_entries( $mapper, $entry, $slug = '' ) {
		$sub_entries = array();
		if ( empty( $mapper['sub_metas'] ) ) {
			return $sub_entries;
		}
		$field_suffixes = Forminator_Form_Entry_Model::field_suffix();

		foreach ( $mapper['sub_metas'] as $sub_meta ) {
			$submeta_key     = in_array( $sub_meta['key'], $field_suffixes, true ) ? $sub_meta['key'] : $sub_meta['key'] . $slug;
			$sub_entry_value = forminator_get_entry_field_value( $entry, $mapper, $submeta_key, true );
			if ( ! empty( $sub_entry_value ) && isset( $sub_meta['transform_callback'] ) && is_callable( $sub_meta['transform_callback'] ) ) {
				$transform_args = array( $sub_entry_value );
				if ( isset( $sub_meta['num_transform_arg'] ) && 2 === $sub_meta['num_transform_arg'] ) {
					$meta_value       = $entry->get_meta( $mapper['meta_key'], '' );
					$transform_args[] = $meta_value;
				}

				$sub_entry_value = call_user_func_array( $sub_meta['transform_callback'], $transform_args );
			}
			if ( 'amount' === $submeta_key && in_array( $mapper['type'], array( 'paypal', 'stripe', 'stripe-ocs' ), true ) ) {
				if ( ! empty( $mapper['meta_key'] ) && ! empty( $entry->meta_data[ $mapper['meta_key'] ]['value'] ) ) {
					$sub_entry_value = $entry->meta_data[ $mapper['meta_key'] ]['value'];
				}
				$sub_entry_value = Forminator_Field::get_formatted_amount( $mapper['field'], $sub_entry_value, self::$custom_form_model );
			}
			$sub_entry = array(
				'key'   => $sub_meta['key'],
				'label' => $sub_meta['label'],
				'value' => $sub_entry_value,
			);

			if ( ! empty( $sub_meta['sub_metas'] ) ) {
				$sub_meta['meta_key']    .= $slug;
				$sub_entry['sub_entries'] = self::get_sub_entries( $sub_meta, $entry, $slug );
			}

			$sub_entries[] = $sub_entry;
		}

		return $sub_entries;
	}

	/**
	 * Ensuring additional items for addons met the entries data requirement
	 * Format used is,
	 * - label
	 * - value
	 * - subentries[]
	 *      - label
	 *      - value
	 *
	 * @since 1.1
	 *
	 * @param  array $addon_additional_items Additional items.
	 * @param  array $meta_data Meta data.
	 *
	 * @return mixed
	 */
	protected static function format_addon_additional_items( $addon_additional_items, $meta_data = array() ) {
		// to `name` and `value` basis.
		$formatted_additional_items = array();
		if ( ! is_array( $addon_additional_items ) ) {
			return array();
		}

		foreach ( $addon_additional_items as $key => $additional_item ) {
			// make sure label and value exist, without it, it will display empty row, so leave it.
			if ( ! isset( $additional_item['label'] ) || ! isset( $additional_item['value'] ) ) {
				continue;
			}
			$sub_entries = array();

			// do below check if sub_entries available.
			if ( isset( $additional_item['sub_entries'] ) && is_array( $additional_item['sub_entries'] ) ) {
				foreach ( $additional_item['sub_entries'] as $sub_entry ) {
					// make sure label and value exist, without it, it will display empty row, so leave it.
					if ( ! isset( $sub_entry['label'] ) || ! isset( $sub_entry['value'] ) ) {
						continue;
					}
					$sub_entries[] = array(
						'key'   => isset( $sub_entry['key'] ) ? $sub_entry['key'] : '',
						'label' => $sub_entry['label'],
						'value' => $sub_entry['value'],
					);
				}
			}

			$formatted_additional_items[] = array(
				'title'       => isset( $meta_data[ $key ]['title'] ) ? $meta_data[ $key ]['title'] : '',
				'label'       => $additional_item['label'],
				'value'       => $additional_item['value'],
				'banner'      => isset( $meta_data[ $key ]['banner'] ) ? $meta_data[ $key ]['banner'] : '',
				'banner_x2'   => isset( $meta_data[ $key ]['banner_x2'] ) ? $meta_data[ $key ]['banner_x2'] : '',
				'sub_entries' => $sub_entries,
			);
		}

		return $formatted_additional_items;
	}

	/**
	 * Get Connected Addons on current form, avoid overhead for checking connected addons many times
	 *
	 * @since 1.1
	 *
	 * @return array|Forminator_Integration[]
	 */
	public function get_connected_addons() {
		if ( is_null( self::$connected_addons ) ) {
			self::$connected_addons = array();

			$connected_addons = forminator_get_addons_instance_connected_with_module( $this->form_id, 'form' );
			foreach ( $connected_addons as $connected_addon ) {
				try {
					$form_hooks = $connected_addon->get_addon_hooks( $this->form_id, 'form' );
					if ( $form_hooks instanceof Forminator_Integration_Form_Hooks ) {
						self::$connected_addons[] = $connected_addon;
					}
				} catch ( Exception $e ) {
					forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to get_addon_hooks', $e->getMessage() );
				}
			}
		}

		return self::$connected_addons;
	}

	/**
	 * Get form type param
	 *
	 * @since 1.5.4
	 * @return string
	 */
	protected function get_form_type() {
		return Forminator_Core::sanitize_text_field( 'form_type' );
	}

	/**
	 * Get form id param
	 *
	 * @since 1.5.4
	 * @return int
	 */
	protected function get_form_id() {
		return (int) filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
	}

	/**
	 * Redirect to referer if available
	 *
	 * @param string $fallback_redirect Fallback redirect.
	 * @param bool   $to_referer Referrer.
	 */
	protected function maybe_redirect_to_referer( $fallback_redirect = '', $to_referer = true ) {

		if ( empty( $fallback_redirect ) ) {
			$fallback_redirect = admin_url( 'admin.php' );
			$fallback_redirect = add_query_arg(
				array(
					'page'      => $this->get_admin_page(),
					'form_type' => $this->get_form_type(),
					'form_id'   => $this->get_form_id(),
				),
				$fallback_redirect
			);
		}
		parent::maybe_redirect_to_referer( $fallback_redirect, $to_referer );

		exit();
	}

	/**
	 * Check payment
	 *
	 * @return bool
	 */
	public function has_payments() {
		$model = Forminator_Base_Form_Model::get_model( $this->form_id );
		if ( is_object( $model ) && $model->has_stripe_or_paypal() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check payment
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return bool
	 */
	public function has_live_payments( $form_id ) {
		$model = Forminator_Form_Entry_Model::has_live_payment( $form_id );

		return $model;
	}

	/**
	 * Executor of adding additional items on entry page
	 *
	 * @see   Forminator_Integration_Form_Hooks::on_render_entry()
	 * @since 1.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 *
	 * @return array
	 */
	private function attach_addon_on_quiz_render_entry( Forminator_Form_Entry_Model $entry_model ) {
		$additonal_items = array();
		// find all registered addons, so history can be shown even for deactivated addons.
		$registered_addons = $this->get_registered_addons();

		foreach ( $registered_addons as $registered_addon ) {
			try {
				$form_hooks = $registered_addon->get_addon_hooks( $this->form_id, 'form' );
				$meta_data  = forminator_find_addon_meta_data_from_entry_model( $registered_addon, $entry_model );

				$addon_additional_items = $form_hooks->on_render_entry( $entry_model, $meta_data );// run and forget.

				$addon_additional_items = self::format_addon_additional_items( $addon_additional_items, $meta_data );
				$additonal_items        = array_merge( $additonal_items, $addon_additional_items );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $registered_addon->get_slug(), 'failed to on_render_entry', $e->getMessage() );
			}
		}

		return $additonal_items;
	}

	/**
	 * Delete signup user entry
	 *
	 * @param array $entry Entry.
	 */
	public function delete_signup_user_entry( $entry ) {
		foreach ( $entry as $entry_id ) {
			$entry_model    = new Forminator_Form_Entry_Model( $entry_id );
			$activation_key = $entry_model->get_meta( 'activation_key' );
			if ( ! empty( $activation_key ) ) {
				$user_data = new Forminator_CForm_User_Data();
				$user_data->delete_signup_by_activation( $activation_key );
			}
		}
	}
}
