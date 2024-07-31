<?php

class GF_Field_Unique_ID extends GF_Field {

	public $type = 'uid';

	private $wait_for_payment = false;

	public static $_is_gpuid_generated = false;

	public static $_generated_uids = array();

	/**
	 * An array of stashed field values that were provided to the current form being rendered for population. We use this
	 * to generate a hash of any populated Unique ID field value to ensure that the value is not changed when the form is
	 * submitted.
	 *
	 * @var array
	 */
	public static $_field_values = array();

	public static $instance = null;

	public function __construct( $data = array() ) {

		parent::__construct( $data );

		// init on first run
		if ( self::$instance === null ) {
			self::$instance = $this->init();
		}

	}

	public function init() {

		add_action( 'gform_field_css_class', array( $this, 'add_editor_field_class' ), 10, 2 );
		add_action( 'gform_field_standard_settings_25', array( $this, 'field_settings_ui' ) );
		add_action( 'gform_field_advanced_settings_50', array( $this, 'advanced_field_settings_ui' ) );
		add_action( 'gform_editor_js', array( $this, 'editor_js' ) );
		add_action( 'gform_editor_js', array( $this, 'field_default_properties_js' ) );
		add_filter( 'gform_routing_field_types', array( $this, 'add_routing_field_type' ) );

		// This is here for backwards compatibility. GF introduced the "hidden" visibility setting a while back. In order
		// to use it, we must make sure old fields have it set as well.
		add_action( 'gform_form_post_get_meta', array( $this, 'set_field_visibility' ) );

		add_action( 'gform_update_status', array( $this, 'process_unspammed' ), 10, 3 );

		add_action( 'gform_form_tag', array( $this, 'add_populated_value_hash_input' ), 10, 2 );
		add_action( 'gform_form_args', array( $this, 'stash_field_values' ), 99 ); // Get 'em late to allow other plugins to modify them before we stash 'em.

		// Priority 8 so ID is generated before GF Feeds are processed (10) and gives other plugins a chance to do something
		// with the generated ID before the GF Feeds are processed as well (9).
		add_filter( 'gform_entry_post_save', array( $this, 'populate_field_value' ), 8, 2 );
		add_action( 'gform_post_add_entry', array( $this, 'populate_field_value' ), 8, 2 );

		// Handle delayed payment transactions
		add_action( 'gform_trigger_payment_delayed_feeds', array( $this, 'delayed_payment_populate_field_value' ), 8, 4 );
		add_action( 'gform_paypal_fulfillment', array( $this, 'delayed_populate_field_value' ), 8 );
		add_action( 'gform_disable_notification', array( $this,
			'disable_completed_payment_notifications_until_uid_generated'
		), 10, 5 );

		add_action( 'wp_ajax_gpui_reset_starting_number', array( $this, 'ajax_reset_starting_number' ) );

		add_filter( 'gform_save_field_value', array( $this, 'handle_sequential_edits' ), 10, 5 );

		// Enable Inline Edit for Unique ID field.
		add_filter( 'gravityview-inline-edit/supported-fields', array( $this, 'gravityview_inline_edit_unique_id' ) );

		return $this;
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Unique ID', 'gp-unique-id' );
	}

	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	public function get_form_editor_field_settings() {
		/**
		 * Filter the field settings that appear in the Form Editor for Unique ID fields.
		 *
		 * @since 1.3.13
		 *
		 * @param array $settings The default field settings available for the Unique ID field type.
		 */
		return apply_filters(
			'gpui_form_editor_field_settings',
			array(
				'label_setting',
				'uid_setting',
				'conditional_logic_field_setting',
				'prepopulate_field_setting',
				'admin_label_setting',
				'css_class_setting',
			)
		);
	}

	public function field_default_properties_js() {
		?>

		<script type="text/javascript">

			function SetDefaultValues_<?php echo $this->type; ?>( field ) {
				field.label = '<?php esc_html_e( 'Unique ID', 'gp-unique-id' ); ?>';
				field['<?php echo gp_unique_id()->perk->key( 'type' ); ?>'] = 'alphanumeric';
				field.visibility = 'hidden';
				return field;
			}

		</script>

		<?php
	}

	/**
	 * Add `gform_hidden` class to field container to tap into GF's default styling for hidden-type inputs in the form editor.
	 *
	 * @param $css_class
	 * @param $field
	 *
	 * @return string
	 */
	public function add_editor_field_class( $css_class, $field ) {
		if ( $this->is_form_editor() && $field->get_input_type() === $this->type ) {
			$css_class .= ' gform_hidden';
		}
		return $css_class;
	}

	public function field_settings_ui() {
		?>

		<li class="uid_setting gwp_field_setting field_setting">

			<div>
				<label for="<?php echo gp_unique_id()->perk->key( 'type' ); ?>" class="section_label">
					<?php _e( 'Type', 'gp-unique-id' ); ?>
					<?php gform_tooltip( gp_unique_id()->perk->key( 'type' ) ); ?>
				</label>
				<select name="<?php echo gp_unique_id()->perk->key( 'type' ); ?>" id="<?php echo gp_unique_id()->perk->key( 'type' ); ?>"
						onchange="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'type' ); ?>', this.value ); gpui.toggleByType( this.value );">
					<?php foreach ( gp_unique_id()->get_unique_id_types() as $value => $type ) : ?>
						<?php printf( '<option value="%s">%s</option>', $value, $type['label'] ); ?>
					<?php endforeach; ?>
				</select>
			</div>

		</li>

		<?php
	}

	public function advanced_field_settings_ui() {
		?>

		<li class="uid_setting gwp_field_setting field_setting gp-field-setting">

			<div class="gp-row">
				<label for="<?php echo gp_unique_id()->perk->key( 'starting_number' ); ?>" class="section_label">
					<?php _e( 'Starting Number', 'gp-unique-id' ); ?>
					<?php gform_tooltip( gp_unique_id()->perk->key( 'starting_number' ) ); ?>
				</label>
				<input type="number" name="<?php echo gp_unique_id()->perk->key( 'starting_number' ); ?>" id="<?php echo gp_unique_id()->perk->key( 'starting_number' ); ?>"
					   onkeyup="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'starting_number' ); ?>', this.value );"
					   onchange="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'starting_number' ); ?>', this.value );"
					   style="width:25%;" />

				<a href="#" id="gp-unique-id_reset" style="margin-left:10px;" onclick="gpui.resetStartingNumber( this )"><?php _e( 'reset', 'gp-unique-id' ); ?></a>
				<?php gform_tooltip( gp_unique_id()->perk->key( 'reset' ) ); ?>

			</div>

			<div class="gp-row">
				<label for="<?php echo gp_unique_id()->perk->key( 'length' ); ?>" class="section_label">
					<?php _e( 'Length', 'gp-unique-id' ); ?>
					<?php gform_tooltip( gp_unique_id()->perk->key( 'length' ) ); ?>
				</label>
				<input type="number" name="<?php echo gp_unique_id()->perk->key( 'length' ); ?>" id="<?php echo gp_unique_id()->perk->key( 'length' ); ?>"
					   onkeyup="gpui.setLengthFieldProperty( this.value );"
					   onchange="gpui.setLengthFieldProperty( this.value );"
					   onblur="gpui.setLengthFieldProperty( this.value, true );"
					   style="width:25%;" />
			</div>

			<div class="gp-row">
				<label for="<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>" class="section_label">
					<?php _e( 'Prefix', 'gp-unique-id' ); ?>
					<?php gform_tooltip( gp_unique_id()->perk->key( 'prefix' ) ); ?>
				</label>
				<input type="text" class="merge-tag-support mt-position-right" name="<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>" id="<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>"
						onkeyup="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>', this.value );"
						onchange="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>', this.value );"
						oninput="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'prefix' ); ?>', this.value );" />
			</div>

			<div>
				<label for="<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>" class="section_label">
					<?php _e( 'Suffix', 'gp-unique-id' ); ?>
					<?php gform_tooltip( gp_unique_id()->perk->key( 'suffix' ) ); ?>
				</label>
				<input type="text" class="merge-tag-support mt-position-right" name="<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>" id="<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>"
						onkeyup="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>', this.value );"
						onchange="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>', this.value );"
						oninput="SetFieldProperty( '<?php echo gp_unique_id()->perk->key( 'suffix' ); ?>', this.value );" />
			</div>

		</li>

		<?php
	}

	public function editor_js() {
		?>

		<script type='text/javascript'>

			jQuery( document ).ready(function( $ ) {

				$( document).bind( 'gform_load_field_settings', function( event, field, form ) {

					var $type       = $( '#' + gpui.key( 'type' ) ),
						$prefix     = $( '#' + gpui.key( 'prefix' ) ),
						$suffix     = $( '#' + gpui.key( 'suffix' ) ),
						$length     = $( '#' + gpui.key( 'length' ) ),
						$start      = $( '#' + gpui.key( 'starting_number' ) ),
						$reset = $( '#' + gpui.key( 'reset' ) ),
						type        = field[gpui.key( 'type' )];

					$type.val( type );
					$prefix.val( field[gpui.key( 'prefix' )] );
					$suffix.val( field[gpui.key( 'suffix' )] );
					$length.val( field[gpui.key( 'length' )] );
					$start.val( field[gpui.key( 'starting_number' )] );
					$reset.prop( 'checked', field[gpui.key( 'reset' )] == true );

					gpui.toggleByType( type );

				} );

			} );

			var gpui;

			( function( $ ) {

				gpui = {

					key: function( key ) {
						return '<?php echo gp_unique_id()->perk->key( '' ); ?>' + key;
					},

					setLengthFieldProperty: function( length, enforce ) {

						var type    = $( '#' + gpui.key( 'type' ) ).val(),
							length  = parseInt( length ),
							enforce = typeof enforce != 'undefined' && enforce === true;

						if( isNaN( length ) ) {
							length = '';
						} else {
							switch( type ) {
								case 'alphanumeric':
									length = Math.max( length, 4 );
									break;
								case 'numeric':
									length = Math.max( length, <?php echo apply_filters( 'gpui_numeric_minimum_length', 6 ); ?> );
									length = Math.min( length, 19 );
									break;
							}
						}

						SetFieldProperty( gpui.key( 'length' ), length );

						if( enforce ) {
							$( '#' + gpui.key( 'length' ) ).val( length );
						}

					},

					toggleByType: function( type ) {

						var $start = $( '#' + gpui.key( 'starting_number' ) );

						switch( type ) {
							case 'sequential':
								$start.parent().show();
								break;
							default:
								$start.parent().hide();
								$start.val( '' ).change();
						}

					},

					resetStartingNumber: function( elem ) {

						var starting_number = parseInt( $( '#' + gpui.key( 'starting_number' ) ).val() );
						if ( ! starting_number ) {
							return alert( '<?php _e( 'Please enter a starting number to reset the sequential ID', 'gp-unique-id' ); ?>' );
						}
						var $elem         = $( elem ),
							field         = GetSelectedField(),
							resettingText = '<?php _e( 'resetting', 'gp-unique-id' ); ?>',
							$response     = $( '<span />' ).text( resettingText ).css( 'margin-left', '10px' );


						$elem.hide();
						$response.insertAfter( $elem );

						var loadingInterval = setInterval( function() {
							$response.text( $response.text() + '.' );
						}, 500 );

						$.post( ajaxurl, {
							action:          'gpui_reset_starting_number',
							starting_number: $( '#' + gpui.key( 'starting_number' ) ).val(),
							form_id:         field.formId,
							field_id:        field.id,
							gpui_reset_starting_number: '<?php echo wp_create_nonce( 'gpui_reset_starting_number' ); ?>'
						}, function( response ) {

							clearInterval( loadingInterval );

							if( response ) {
								response = $.parseJSON( response );
								$response.text( response.message );
							}

							setTimeout( function() {
								$response.remove();
								$elem.show();
							}, 4000 );

						} );

					}

				}

			} )( jQuery );

		</script>

		<?php
	}

	public function set_field_visibility( $form ) {
		foreach ( $form['fields'] as &$field ) {
			if ( $field->get_input_type() == $this->get_input_type() ) {
				if ( version_compare( GFCommon::$version, '2.1', '<=' ) ) {
					$field->cssClass .= ' gf_hidden';
				}
				$field->visibility = 'hidden';
			}
		}
		return $form;
	}

	public function process_unspammed( $entry_id, $property_value, $previous_value ) {
		if ( $property_value === 'active' ) {
			$entry = GFAPI::get_entry( $entry_id ); 
			$form  = GFAPI::get_form( $entry['form_id'] );
			foreach( $form['fields'] as $field ) {
				// Regenerate Unique ID : Check the field type, and ensure it already doesn't have a Unique ID stored.
				if ( $field->type === 'uid' && ! rgar( $entry, $field->id ) ) {
					$value = gp_unique_id()->get_unique( $entry['form_id'], $field, 5, array(), $entry, false );
					$entry[ $field->id ] = $value;
					GFAPI::update_entry( $entry );
				}
			}
		}
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( $this->is_form_editor() ) {
			return $this->get_field_input_form_editor();
		}

		$input_type = $this->is_form_editor() || $this->is_entry_detail() ? 'text' : 'hidden';
		$html_id    = $this->is_entry_detail() ? "input_{$this->id}" : "input_{$form['id']}_{$this->id}";
		$disabled   = $this->is_form_editor() ? "disabled='disabled'" : '';

		extract( gf_apply_filters( 'gpui_input_html_options', array( $form['id'], $this->id ), compact( 'input_type', 'disabled' ) ) );

		$input_html = sprintf( "<input name='input_%d' id='%s' type='%s' value='%s' %s />", $this->id, $html_id, $input_type, esc_attr( $value ), $disabled );
		$input_html = sprintf( "<div class='ginput_container ginput_container_%s'>%s</div>", $input_type, $input_html );

		return $input_html;
	}

	public function get_field_input_form_editor() {
		$style = 'border:1px dashed #ccc;background-color:transparent;text-transform:lowercase;width: 100%;text-align:center;font-size: 0.9375rem;padding: 0.5rem;line-height: 2;border-radius: 4px;';
		if ( GravityPerks::is_gf_version_lte( '2.5-beta-1' ) ) {
			$style = 'border:1px dashed #ccc;background-color:transparent;padding:5px;color:#bbb;letter-spacing:.05em;text-transform:lowercase;width:330px;text-align:center;font-family:\'Open Sans\', sans-serif;';
		}
		$input_html = sprintf( '<input
            style="%s"
            value="hidden field, populated on submission"
            disabled="disabled" />',
			$style
		);
		$input_html = sprintf( "<div class='ginput_container ginput_container_hidden'>%s</div>", $input_html );
		return $input_html;
	}

	/**
	 * GF 2.5 adds an ugly "Hidden" label and icon about field's with a hidden visibility. Let's disable this.
	 * @return string
	 */
	public function get_hidden_admin_markup() {
		return '';
	}

	public function populate_field_value( $entry, $form, $fulfilled = false ) {

		if ( rgar( $entry, 'partial_entry_id' ) ) {
			return $entry;
		}

		$feed = null;

		foreach ( $form['fields'] as $field ) {

			if ( $field->get_input_type() != $this->get_input_type() || GFFormsModel::is_field_hidden( $form, $field, array(), $entry ) ) {
				continue;
			}

			if ( $feed === null ) {
				$feed = $this->get_paypal_standard_feed( $form, $entry );
				// Look for other feeds if not PayPal feed.
				if ( ! $feed ) {
					// Find payment feeds for this entry by looping through all registered add-ons.
					foreach ( GFAddOn::get_registered_addons( true ) as $addon ) {
						// Need to add a check here for GFPaymentAddon::get_post_payment_actions_config() to see if this is an add-on that supports post payment actions.
						if ( method_exists( $addon, 'get_post_payment_actions_config' ) && ! empty( $addon->get_post_payment_actions_config( 'gp-unique-id' ) ) ) {
							$feed_entry       = $entry;
							$feed_entry['id'] = null;
							$feed             = $addon->get_payment_feed( $feed_entry, $form );
							if ( $feed ) {
								break;
							}
						}
					}
				}
				/**
				 * Modify the feed that indicates a payment gateway is configured that
				 * accepts delayed payments (i.e. PayPal Standard).
				 *
				 * This filter allows 3rd party payment add-ons to add support for delaying unique ID generation when
				 * one of their feeds is present.
				 *
				 * @since 1.3.1
				 *
				 * @param $feed  array The payment feed.
				 * @param $form  array The current form object.
				 * @param $entry array The current entry object.
				 */
				$feed = gf_apply_filters( array( 'gpui_wait_for_payment_feed', $form['id'], $field->id ), $feed, $form, $entry );
			}

			if ( $feed ) {
				$wait_for_payment = $this->is_wait_for_payment_enabled( $form, $field, $feed, $entry );
				/*
				 * Don't populate a unique ID if wait for payment is enabled and the order is not fulfilled - or - if
				 * wait for payment is *not* enabled and the order *is* fulfilled. This prevents unique IDs from being
				 * regenerated when the order is fulfilled and wait for payment is not enabled.
				 */
				if ( ( $wait_for_payment && ! $fulfilled ) || ( ! $wait_for_payment && $fulfilled ) ) {
					continue;
				}
			}

			// If the unique ID was already generated this runtime, do not generate again. Also ensure the same unique id is not used for 2 separate entries.
			if ( ! rgar( self::$_generated_uids, $entry['id'] ) ) {
				self::$_generated_uids[ $entry['id'] ] = array();
			}

			if ( ! rgar( self::$_generated_uids[ $entry['id'] ], $field->id ) ) {
				self::$_generated_uids[ $entry['id'] ][ $field->id ] = $this->save_value( $entry, $field, rgar( $entry, $field->id ) );
			}

			$entry[ $field['id'] ] = self::$_generated_uids[ $entry['id'] ][ $field->id ];

		}

		return $entry;
	}

	/**
	 * When a PayPal order is fulfilled, loop through fields and populate any there were configured to wait for payment.
	 *
	 * @param $entry
	 */
	public function delayed_populate_field_value( $entry ) {
		$form = GFAPI::get_form( $entry['form_id'] );
		$this->populate_field_value( $entry, $form, true );
	}

	public function get_paypal_standard_feed( $form, $entry ) {

		$feed = false;

		if ( is_callable( 'gf_paypal' ) ) {
			$entry['id'] = null;
			$feed        = gf_paypal()->get_payment_feed( $entry, $form );
		}

		return $feed;
	}

	public function delayed_payment_populate_field_value( $transaction_id, $payment_feed, $entry, $form ) {
		foreach ( GFAddOn::get_registered_addons( true ) as $addon ) {
			if ( rgar( $payment_feed, 'addon_slug' ) == $addon->get_slug() && method_exists( $addon, 'get_post_payment_actions_config' ) && ! empty( $addon->get_post_payment_actions_config( 'gp-unique-id' ) ) ) {
				$this->populate_field_value( $entry, $form, true );
				if ( $this->is_wait_for_payment_enabled( $form, false, $payment_feed, $entry ) ) {
					// For any aborted complete payment notifications, make sure to trigger them now (after updating the entry with Unique ID).
					GFAPI::send_notifications( $form, GFAPI::get_entry( $entry['id'] ), 'complete_payment' );
				}
			}
		}
	}

	public function disable_completed_payment_notifications_until_uid_generated( $disable, $notification, $form, $entry, $data = array() ) {

		// Delayed notification only applies to "Payment Completed" notifications.
		if ( $notification['event'] !== 'complete_payment' ) {
			return $disable;
		}
	
		// Abort notification if unique IDs have not been generated, and the UID field itself is not conditionally hidden.
		foreach( $form['fields'] as $field ) {
			if ( $field->type === 'uid' && ! rgar( $entry, $field->id ) && ! GFFormsModel::is_field_hidden( $form, $field, array(), $entry ) && $this->is_wait_for_payment_enabled( $form, $field, array(), $entry ) ) {
				return true;
			}
		}
	
		return $disable;
	}

	public function is_wait_for_payment_enabled( $form, $field, $feed, $entry ) {
		if ( ! $field ) {
			foreach ( $form['fields'] as $field ) {
				if ( $field->get_input_type() == $this->get_input_type() ) {
					$wait_for_payment = gf_apply_filters( array(
						'gpui_wait_for_payment',
						$form['id'],
						$field->id
					), false, $feed, $form, $entry );
					if ( $wait_for_payment ) {
						return true;
					}
				}
			}
		} else {
			/**
			 * Indicate whether the unique ID generation should wait for a completed payment.
			 *
			 * Only applies to payment gateways that accept delayed payments (i.e. PayPal Standard).
			 *
			 * @since 1.3.0
			 *
			 * @param $wait_for_payment bool  Whether or not to wait for payment. Defaults to false.
			 * @param $form             array The current form object.
			 * @param $entry            array The current entry object.
			 */
			return gf_apply_filters( array( 'gpui_wait_for_payment', $form['id'], $field->id ), false, $feed, $form, $entry );
		}
	}

	public function save_value( $entry, $field, $value = null ) {

		if ( ! $value || ! $this->validate_existing_value( $value, $entry, $field ) ) {
			gp_unique_id()->log( sprintf( 'Generating a unique ID for field %d', $field->id ) );
			self::$_is_gpuid_generated = true;
			$value = gp_unique_id()->get_unique( $entry['form_id'], $field, 5, array(), $entry );
		}

		gp_unique_id()->log( sprintf( 'Saving unique ID for field %d: %s', $field->id, $value ) );

		$result = GFAPI::update_entry_field( $entry['id'], $field->id, $value );

		self::$_is_gpuid_generated = false;

		return $result ? $value : false;
	}

	/**
	 * Check if is request to Gravity Forms REST API.
	 *
	 * @since 1.5.8
	 *
	 * @return bool Returns true if this is a request to the Gravity Forms REST API. False otherwise
	 */
	private function is_request_to_rest_api() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		// Check if Gravity Forms endpoint.
		$is_gf_endpoint = ( strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'gf/' ) !== false );

		// Check if Third Party endpoint.
		$third_party = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'gf-' ) );

		// Check if Rest v1 request.
		$rest_v1 = ( false !== strpos( $_SERVER['REQUEST_URI'], 'gravityformsapi/' ) );

		return ( $is_gf_endpoint || $third_party || $rest_v1 );
	}
	
	/**
	 * We assume that the value passed is a value that has either been generated by a 3rd-party script or has been
	 * prepopulated into the form – often in an "edit" context but rarely where the Unique ID is actually editable. As
	 * such, let's assume the value should not change.
	 *
	 * Notes:
	 * → Editing via GravityView does not pass through this method.
	 * → Unique ID fields are not editable via Gravity Flow. Last checked: 4/7/2024.
	 *
	 * @param $value
	 * @param $entry
	 * @param $field
	 *
	 * @return mixed
	 */
	public function validate_existing_value( $value, $entry, $field ) {

		$is_valid = false;

		if ( in_array( $value, GP_Unique_ID::$_generated_uids_safeguard ) ) {
			$is_valid = true;
		}

		$submitted_hash = rgpost( sprintf( 'gpuid_existing_value_%d', $field->id ) );
		if ( $submitted_hash && wp_hash( $value ) === $submitted_hash ) {
			$is_valid = true;
		}

		// If the entry is a revision (powered by GravityRevisions), trust the value.
		if ( class_exists( 'GV_Entry_Revisions' ) && $entry['status'] === GV_Entry_Revisions::revision_status_key ) {
			$is_valid = true;
		}

		// Compatibility for GravityView Import Entries.
		if ( did_action( 'gravityview/import/processor/init' ) ) {
			$is_valid = true;
		}

		// Compatibility for adding entries via Rest API (like Gravity Flow Form Connector).
		if ( $this->is_request_to_rest_api() ) {
			$is_valid = true;
		}

		return gf_apply_filters( array( 'gpuid_is_existing_value_valid', $field->formId, $field->id ), $is_valid, $value, $entry, $field );
	}

	/**
	 * Stash the field values that will be used to populate the form to get the most accurate value for our populated
	 * value hash in self::add_populated_value_hash_input().
	 *
	 * @param $form_args
	 *
	 * @return mixed
	 */
	public function stash_field_values( $form_args ) {
		self::$_field_values = $form_args['field_values'];
		return $form_args;
	}

	/**
	 * Add input to the form that captures a hash of the populated Unique ID field value.
	 *
	 * @param $form_tag
	 * @param $form
	 *
	 * @return mixed|string
	 */
	public function add_populated_value_hash_input( $form_tag, $form ) {

		foreach( $form['fields'] as $field ) {
			if ( $field->get_input_type() != $this->get_input_type() ) {
				continue;
			}

			$value = GFFormsModel::get_field_value( $field, self::$_field_values, false );
			$value = $field->get_value_default_if_empty( $value );
			$hash  = wp_hash( $value );

			$form_tag .= sprintf( '<input type="hidden" name="gpuid_existing_value_%d" id="gpuid_existing_value_%d" value="%s" />', $field->id, $field->id, $hash );
		}

		return $form_tag;
	}

	public function ajax_reset_starting_number() {

		$form_id         = rgpost( 'form_id' );
		$field_id        = rgpost( 'field_id' );
		$starting_number = rgpost( 'starting_number' );
		$starting_number = is_numeric( $starting_number ) ? $starting_number : 1; // Default to 1 if starting number is missing

		if ( ! check_admin_referer( 'gpui_reset_starting_number', 'gpui_reset_starting_number' ) || ! $form_id || ! $field_id || ! $starting_number ) {
			die( __( 'Oops! There was an error resetting the starting number.', 'gp-unique-id' ) );
		}

		$result = gp_unique_id()->set_sequential_starting_number( $form_id, $field_id, $starting_number - 1 );

		if ( $result == true ) {
			$response = array(
				'success' => true,
				'message' => __( 'Reset successfully!', 'gp-unique-id' ),
			);
		} elseif ( $result === 0 ) {
			$response = array(
				'success' => false,
				'message' => __( 'Already reset.', 'gp-unique-id' ),
			);
		} else {
			$response = array(
				'success' => false,
				'message' => __( 'Error resetting.', 'gp-unique-id' ),
			);
		}

		die( json_encode( $response ) );
	}

	function add_routing_field_type( $field_types ) {
		$field_types[] = 'uid';
		return $field_types;
	}

	public function handle_sequential_edits( $value, $entry, $field, $form, $input_id ) {
		if ( self::$_is_gpuid_generated
		     /**
		      * Let's assume that the field type will not be "uid" if the field is editable. We may need to revisit this
		      * assumption in the future. For now, editing via GravityView and GF's own entry detail view are the only
		      * known contexts where the user can directly modify the unique ID value. GV changes the field type to
		      * "text" when editing.
		      */
		     || ( $field->type === 'uid' && GFForms::get_page() !== 'entry_detail' )
		     || $field->{'gp-unique-id_type'} !== 'sequential'
		     || empty( $value )
		     || $value == rgar( $entry, $field->id )
		) {
			return $value;
		}

		$atts   = gp_unique_id()->get_field_unique_id_attributes( $field, $field->{gp_unique_id()->perk->key( 'length' )}, array(), $entry );
		$prefix = GFCommon::replace_variables( $atts['prefix'], $form, $entry, false, true, false, 'text' );
		$suffix = GFCommon::replace_variables( $atts['suffix'], $form, $entry, false, true, false, 'text' );

		// Find just the sequential ID portion of the unique ID.
		preg_match( '/^' . preg_quote( $prefix, '/' ) . '(\d+)' . preg_quote( $suffix, '/' ) . '$/', $value, $matches );
		if( empty( $matches[1] ) ) {
			return $value;
		}

		gp_unique_id()->fast_forward_sequence( $form['id'], $field->id, intval( $matches[1] ) );

		return $value;
	}

	public function gravityview_inline_edit_unique_id( $supported_fields ) {
		$supported_fields[] = $this->type;
		return $supported_fields;
	}

}

class GP_Unique_ID_Field extends GF_Field_Unique_ID { }

GF_Fields::register( new GF_Field_Unique_ID() );

function gp_unique_id_field() {
	return GF_Field_Unique_ID::$instance;
}
