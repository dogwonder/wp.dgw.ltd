<?php
defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Google_Account;
use GP_Google_Sheets\Accounts\Oauth;
use GP_Google_Sheets\Actions;
use GP_Google_Sheets\AJAX\Admin;
use GP_Google_Sheets\AJAX\Feed_Settings;
use GP_Google_Sheets\AJAX\Plugin_Settings;
use GP_Google_Sheets\Cron;
use GP_Google_Sheets\Migration_1_0;
use GP_Google_Sheets\Retry;
use GP_Google_Sheets\Spreadsheets\Range_Parser;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;

if ( class_exists( 'GP_Feed_Plugin' ) ) {
	class GP_Google_Sheets extends GP_Feed_Plugin {

		/**
		 * Defines the version the add-On.
		 *
		 * @since 1.0
		 * @var string $_version Contains the version.
		 */
		protected $_version = GP_GOOGLE_SHEETS_VERSION;

		/**
		 * Defines the minimum Gravity Forms version required.
		 *
		 * @since 1.0
		 * @var string $_min_gravityforms_version The minimum version required.
		 */
		protected $_min_gravityforms_version = '2.5';

		/**
		 * @var string $_slug The add-on slug doubles as the key in which all the settings are stored. If this changes, also change uninstall.php where the string is hard-coded.
		 * @see get_slug()
		 */
		protected $_slug = 'gp-google-sheets';

		/**
		 * Defines the main plugin file.
		 *
		 * @since 1.0
		 * @var string $_path The path to the main plugin file, relative to the plugins folder.
		 */
		protected $_path = 'gp-google-sheets/gp-google-sheets.php';

		/**
		 * Defines the full path to this class file.
		 *
		 * @since 1.0
		 * @var string $_full_path The full path.
		 */
		protected $_full_path = __FILE__;

		/**
		 * Defines the URL where this add-on can be found.
		 *
		 * @since 1.0
		 * @var string
		 */
		protected $_url = 'https://gravitywiz.com';

		/**
		 * Defines the title of this add-on.
		 *
		 * @since 1.0
		 * @var string $_title The title of the add-on.
		 */
		protected $_title = 'GP Google Sheets';

		/**
		 * Defines the short title of the add-on.
		 *
		 * @since 1.0
		 * @var string $_short_title The short title.
		 */
		protected $_short_title = 'Google Sheets';

		/**
		* Defines if feed ordering is supported.
		*
		* @since  1.1.8
		* @var    bool $_supports_feed_ordering Is feed ordering supported?
		*/
		protected $_supports_feed_ordering = true;

		/**
		 * Contains an instance of this class, if available.
		 *
		 * @since 1.0
		 * @var GP_Google_Sheets $_instance If available, contains an instance of this class
		 */
		private static $_instance = null;

		/**
		 * Even though we use Action Scheduler, we still want async feed processing as we immediately run the action.
		 *
		 * @since 2.2
		 * @var bool
		 */
		protected $_async_feed_processing = true;

		/**
		 * Defines if Add-On should use Gravity Forms servers for update data.
		 *
		 * @since  1.0
		 * @var    bool
		 */
		protected $_enable_rg_autoupgrade = false;

		/**
		 * Defines the capabilities needed for the Add-On. Ensures compatibility
		 * with Members plugin.
		 *
		 * @since  1.0
		 * @var    array $_capabilities The capabilities needed for the Add-On
		 */
		protected $_capabilities = array(
			'gp-google-sheets',
			'gp-google-sheets_uninstall',
			'gp-google-sheets_results',
			'gp-google-sheets_settings',
			'gp-google-sheets_form_settings',
		);

		/**
		 * Defines the capability needed to access the Add-On settings page.
		 *
		 * @since  1.0
		 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
		 */
		protected $_capabilities_settings_page = 'gp-google-sheets_settings';

		/**
		 * Defines the capability needed to access the Add-On form settings page.
		 *
		 * @since  1.0
		 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
		 */
		protected $_capabilities_form_settings = 'gp-google-sheets_form_settings';

		/**
		 * Defines the capability needed to uninstall the Add-On.
		 *
		 * @since  1.0
		 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
		 */
		protected $_capabilities_uninstall = 'gp-google-sheets_uninstall';

		const NONCE_AJAX             = 'gpgs_settings_nonce';
		const SCRIPT_HANDLE          = 'gpgs_settings';
		const GWIZ_OAUTH_SERVICE_URL = 'https://oauth.gravitywiz.com';

		/**
		 * @credit https://github.com/google/site-kit-wp
		 */
		public function setup_vendor_autoload() {
			try {
				$class_map = array_merge(
					include plugin_dir_path( __FILE__ ) . 'third-party/vendor/composer/autoload_classmap.php'
				);

				spl_autoload_register(
					function ( $class ) use ( $class_map ) {
						if ( isset( $class_map[ $class ] ) && substr( $class, 0, 29 ) === 'GP_Google_Sheets\\Dependencies' ) {
							require_once $class_map[ $class ];
						}
					},
					true,
					true
				);
			} catch ( \TypeError $e ) {
				gp_google_sheets()->log_error( __METHOD__ . '(): Could not initialize autoloader. ' . $e->getMessage() );
			}
		}

		/**
		 * Handles hooks and loading of language files.
		 */
		public function init() {
			$this->setup_vendor_autoload();

			parent::init();

			Actions::hooks();
			Cron::hooks();
			Migration_1_0::hooks();

			/**
			 * Check if our setting to edit & delete rows in the Sheet is enabled
			 * each time an entry is edited or deleted.
			 */
			add_action( 'gform_after_update_entry', array( $this, 'handle_after_update_entry' ), 10, 2 ); //legacy save_lead() calls
			add_action( 'gform_post_update_entry', array( $this, 'handle_post_update_entry' ), 10, 2 ); //newer update_entry() calls
			add_action( 'gform_update_status', array( $this, 'entry_status_changed' ), 10, 3 );
			add_action( 'gform_post_payment_action', array( $this, 'entry_payment_status_changed' ), 10, 2 );

			//Add additional tooltips to Gravity Forms for our settings page
			add_filter( 'gform_tooltips', array( $this, 'add_tooltips' ) );

			//Add more core fields to our field map list of fields
			add_filter( 'gform_field_map_choices', array( $this, 'add_more_core_fields_to_field_map' ), 10, 4 );

			//The sheets are created during the feed settings validation
			//No easy way to edit the settings values at that time, so
			add_action( 'gform_post_save_feed_settings', array( $this, 'save_sheet_url_after_create_sheet' ), 10, 4 );

			//Handles changes a user makes to the field map in feed settings
			add_action( 'gform_post_save_feed_settings', array( $this, 'update_sheet_after_field_map_change' ), 10, 4 );

			// Add entry meta box for seeing the status of each feed
			add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_entry_meta_box' ), 10, 3 );
			add_action( 'gform_pre_entry_detail', array( $this, 'entry_details_maybe_process_feed' ), 10, 2 );

			// Feed error notifications
			add_filter( 'gform_notification_events', array( $this, 'add_feed_error_notification_event' ), 10, 2 );

			// add notice if no plugin token is set (e.g. aauthentication with Google has not happened yet.)
			add_action( 'admin_notices', array( $this, 'maybe_display_http_warning' ) );

			add_filter( 'gform_settings_save_button', array( $this, 'remove_plugin_settings_save_button' ), 10, 2 );

			add_filter( 'install_plugin_complete_actions', array( $this, 'add_back_to_plugin_settings_action' ), 10, 3 );
			add_filter( 'wp_redirect', array( $this, 'redirect_to_plugin_settings' ) );

			// Add delayed payment support to the feed
			$this->add_delayed_payment_support( array() );

			// Load the GP Populate Anything integration
			if ( class_exists( 'GPPA_Object_Type' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/compatibility/class-object-type-google-sheet.php';

				gp_populate_anything()->register_object_type( 'gpgs_sheet', '\GP_Google_Sheets\Compatibility\GPPA_Object_Type_Google_Sheet' );
			}

			GP_Google_Sheets\Compatibility\GravityView::get_instance();

			add_action( 'gform_delete_entry', array( $this, 'delete_entry_from_connected_sheets' ), 25, 1 );
		}

		public function init_admin() {
			parent::init_admin();

			add_action( 'admin_init', array( $this, 'maybe_purge_action_scheduler' ) );
		}

		public function init_ajax() {
			parent::init_ajax();

			Plugin_Settings::hooks();
			Feed_Settings::hooks();
			Admin::hooks();
			Oauth::hooks();
		}

		public function upgrade( $previous_version ) {
			if ( $previous_version && version_compare( $previous_version, '1.0', '<' ) ) {
				Migration_1_0::schedule();
			}
		}

		public function remove_plugin_settings_save_button( $button, $settings ) {
			if ( rgget( 'page' ) === 'gf_settings' && rgget( 'subview' ) === 'gp-google-sheets' ) {
				return '';
			}

			return $button;
		}

		public function add_more_core_fields_to_field_map( $choices, $form_id, $input_type, $excluded_types ) {
			foreach ( $choices as $key => $arr ) {
				if ( __( 'Entry Properties', 'gravityforms' ) != $arr['label'] ) {
					continue;
				}

				$core_fields = array(
					array(
						'label' => __( 'Created By (User Id)', 'gravityforms' ),
						'value' => 'created_by',
					),
					array(
						'label' => __( 'Transaction Id', 'gravityforms' ),
						'value' => 'transaction_id',
					),
					array(
						'label' => __( 'Payment Amount', 'gravityforms' ),
						'value' => 'payment_amount',
					),
					array(
						'label' => __( 'Payment Date', 'gravityforms' ),
						'value' => 'payment_date',
					),
					array(
						'label' => __( 'Payment Status', 'gravityforms' ),
						'value' => 'payment_status',
					),
					array(
						'label' => __( 'Post Id', 'gravityforms' ),
						'value' => 'post_id',
					),
					array(
						'label' => __( 'User Agent', 'gravityforms' ),
						'value' => 'user_agent',
					),
				);

				$choices[ $key ]['choices'] = array_merge( $choices[ $key ]['choices'], $core_fields );
				break;
			}
			return $choices;
		}

		/**
		 * Filter callback that adds tooltips to Gravity Forms
		 */
		function add_tooltips( $tooltips ) {
			$new_tips = array(
				array(
					'slug'    => 'google_sheet_create_sheet',
					'title'   => __( 'Create New Sheet', 'gp-google-sheets' ),
					'content' => __( 'Create a new sheet inside the configured Google Drive account.', 'gp-google-sheets' ),
				),
				array(
					'slug'    => 'google_sheet_disconnect',
					'title'   => __( 'Disconnect', 'gp-google-sheets' ),
					'content' => __( 'Deletes the authorization token that grants us permission to edit the Google Sheet.', 'gp-google-sheets' ),
				),
				array(
					'slug'    => 'google_sheet_insert_test_row',
					'title'   => __( 'Insert Test Row', 'gp-google-sheets' ),
					'content' => __( 'Creates a new row in the Google Sheet containing sample data.', 'gp-google-sheets' ),
				),
			);

			foreach ( $new_tips as $new_tip ) {
				$tooltips[ $new_tip['slug'] ] = sprintf(
					'<h6>%s</h6>%s',
					$new_tip['title'],
					$new_tip['content']
				);
			}
			return $tooltips;
		}

		/**
		 * Registers a notification event for Google Sheets feed errors.
		 *
		 * @param array $events Register notification events.
		 * @param array $form The current form.
		 *
		 * @return array
		 */
		public function add_feed_error_notification_event( $events, $form ) {
			if ( ! $this->get_active_feeds( $form['id'] ) ) {
				return $events;
			}

			$events['gpgs_feed_error'] = __( 'Google Sheets Feed Error' );

			return $events;
		}

		/**
		 * Extend add_feed_error to trigger a notification event.
		 *
		 * @todo Add a merge tag for the error message.
		 *
		 * @param $error_message
		 * @param $feed
		 * @param $entry
		 * @param $form
		 *
		 * @return void
		 */
		function add_feed_error( $error_message, $feed, $entry, $form ) {
			parent::add_feed_error( $error_message, $feed, $entry, $form );

			$notifications = GFCommon::get_notifications_to_send( 'gpgs_feed_error', $form, $entry );
			$ids           = array();

			foreach ( $notifications as $notification ) {
				$ids[] = $notification['id'];
			}

			GFCommon::send_notifications( $ids, $form, $entry, true, 'gpgs_feed_error' );
		}

		function delete_entry_from_connected_sheets( $entry_id ) {
			//Get form ID from $entry_id
			$entry = GFAPI::get_entry( $entry_id );
			if ( is_wp_error( $entry ) || empty( $entry['form_id'] ) ) {
				return;
			}

			$feeds = $this->get_active_feeds( $entry['form_id'] );

			if ( empty( $feeds ) ) {
				return;
			}

			foreach ( $feeds as $feed ) {
				$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );

				//Do we have a Sheet URL? Check the feed settings
				if ( empty( $spreadsheet_id ) ) {
					//No. Need a Sheet before we can make updates inside
					continue;
				}

				$edit_rows = false;
				if ( ! empty( $feed['meta']['edit_rows'] ) || $feed['meta']['edit_rows'] === '1' ) {
					$edit_rows = true;
				}

				$form = GFAPI::get_form( $entry['form_id'] );

				/**
				 * Disable Google Sheet row deletion when the "edit_rows" config option is enabled.
				 *
				 * @param bool $should_delete_sheet_row Whether or not the Google Sheet row should be deleted. Default is if the "edit_rows" config option value.
				 * @param array $form The current form.
				 * @param array $feed The current feed.
				 * @param array $entry The current entry.
				 *
				 * @since 1.0-beta-1.5
				 *
				 * @return bool
				 */
				$should_delete_sheet_row = gf_apply_filters( array( 'gpgs_should_delete_google_sheets_row', $form['id'] ), $edit_rows, $form, $feed, $entry );

				//Is the feature to edit & delete enabled on this form?
				if ( ! $should_delete_sheet_row ) {
					//No
					continue;
				}

				// queue up the delete action for immediately processing
				Retry::enqueue_async_action(
					'gp_google_sheets_delete_entry_from_sheet',
					array(
						'entry_id'       => $entry_id,
						'feed_id'        => $feed['id'],
						'spreadsheet_id' => $spreadsheet_id,
					),
					$entry_id
				);
			}
		}

		/**
		 * gform_after_update_entry and gform_post_update_entry have different signatures. This methods passes everything
		 * to handle_after_update_entry().
		 *
		 * @param array $lead           The entry object after being updated.
		 * @param array $original_entry The entry object before being updated.
		 */
		function handle_post_update_entry( $lead, $original_entry ) {
			$this->handle_after_update_entry( GFAPI::get_form( $lead['form_id'] ), $lead );
		}

		/**
		 * @param array $form The Gravity Forms form array for the entry.
		 * @param int|array $entry_or_id The entry ID or entry object.
		 */
		function handle_after_update_entry( $form, $entry_or_id ) {
			if ( empty( $form['id'] ) ) {
				return;
			}

			if ( ! empty( rgar( $entry_or_id, 'id' ) ) ) {
				$entry_id = rgar( $entry_or_id, 'id' );
				$entry    = $entry_or_id;
			} elseif ( is_numeric( $entry_or_id ) ) {
				$entry_id = $entry_or_id;
				$entry    = GFAPI::get_entry( $entry_id );
			}

			if ( empty( $entry_id ) || empty( $entry ) || is_wp_error( $entry ) ) {
				return;
			}

			$feeds = $this->get_active_feeds( $entry['form_id'] );

			if ( empty( $feeds ) ) {
				return;
			}

			foreach ( $feeds as $feed ) {
				$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );

				//Do we have a Sheet URL? Check the feed settings
				if ( empty( $spreadsheet_id ) ) {
					//No. Need a Sheet before we can make updates inside
					continue;
				}

				//Is the edit feature enabled for this form?
				if ( empty( $feed['meta']['edit_rows'] ) ) {
					//No
					continue;
				}

				if ( ! $this->is_feed_condition_met( $feed, $form, $entry ) ) {
					continue;
				}

				Retry::enqueue_async_action(
					'gp_google_sheets_edit_entry_in_sheet',
					array(
						'entry_id'       => $entry_id,
						'feed_id'        => $feed['id'],
						'spreadsheet_id' => $spreadsheet_id,
					),
					$entry_id,
				);
			}
		}

		/**
		 * entry_status_changed
		 *
		 * @param  integer $entry_id Current entry ID.
		 * @param  string $property_value New value of the entry’s property (ie “Active”, “Spam”, “Trash”).
		 * @param  string $previous_value Previous value of the entry’s property (ie “Active”, “Spam”, “Trash”).
		 * @return void
		 */
		function entry_status_changed( $entry_id, $property_value, $previous_value ) {
			switch ( strtolower( $property_value ) ) {
				case 'trash':
				case 'spam':
					//The entry was just trashed, delete the row from the Sheet
					$this->delete_entry_from_connected_sheets( $entry_id );
					return;
				case 'active':
					//The entry was just untrashed or unspammed
					$entry = GFAPI::get_entry( $entry_id );
					if ( is_wp_error( $entry ) ) {
						return;
					}

					$feeds = $this->get_active_feeds( $entry['form_id'] );

					if ( empty( $feeds ) ) {
						return;
					}

					foreach ( $feeds as $feed ) {
						$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );

						//Do we have a Sheet URL? Check the feed settings
						if ( empty( $spreadsheet_id ) ) {
							//No. Need a Sheet before we can make updates inside
							continue;
						}

						//process the feed
						$this->process_feed( $feed, $entry, GFAPI::get_form( $entry['form_id'] ) );
					}
					return;
			}

		}

		/**
		 * Handle payment status changes.
		 *
		 * To prevent adding rows if the feed is configured to be delayed until payment is captured, we can just
		 * ignore some statuses like pending for now.
		 *
		 * @param array $entry The Entry Object
		 * @param array $action {
		 *     The action performed.
		 *
		 *     @type string $type             The callback action type. Required.
		 *     @type string $transaction_id   The transaction ID to perform the action on. Required if the action is a payment.
		 *     @type string $subscription_id  The subscription ID. Required if this is related to a subscription.
		 *     @type string $amount           The transaction amount. Typically required.
		 *     @type int    $entry_id         The ID of the entry associated with the action. Typically required.
		 *     @type string $transaction_type The transaction type to process this action as. Optional.
		 *     @type string $payment_status   The payment status to set the payment to. Optional.
		 *     @type string $note             The note to associate with this payment action. Optional.
		 * }
		 */
		public function entry_payment_status_changed( $entry, $action ) {
			if ( rgar( $entry, 'payment_status' ) === 'Pending' ) {
				return;
			}

			$this->handle_after_update_entry( GFAPI::get_form( $entry['form_id'] ), $entry );
		}

		/**
		 * Get an instance of this class.
		 *
		 * @return GP_Google_Sheets
		 */
		public static function get_instance() {
			if ( self::$_instance == null ) {
				self::$_instance = new GP_Google_Sheets();
			}

			return self::$_instance;
		}

		/**
		 * Sets the minimum requirements for the Perk.
		 *
		 * @return array
		 */
		public function minimum_requirements() {
			return array(
				'gravityforms' => array(
					'version' => '2.5',
				),
				'plugins'      => array(
					'gravityperks/gravityperks.php' => array(
						'name'    => 'Gravity Perks',
						'version' => '2.0',
					),
				),
				'php'          => array(
					'version' => '7.3',
				),
			);
		}

		/**
		 * Prevent the class from being cloned
		 *
		 * @since 1.0
		 */
		private function __clone() {
		} /* do nothing */

		/**
		 * Indicates if the feed can be duplicated.
		 *
		 * @since 1.0
		 * @since 1.3 Enabled feed duplication.
		 *
		 * @param int $id Feed ID requesting duplication.
		 *
		 * @return bool
		 */
		public function can_duplicate_feed( $id ) {
			return true;
		}

		/**
		 * Setup columns for feed list table.
		 *
		 * @return array
		 * @since  1.0
		 *
		 */
		public function feed_list_columns() {
			return array(
				'feed_name' => esc_html__( 'Name', 'gp-google-sheets' ),
			);
		}

		/**
		 * Format the value to be displayed in the spreadsheet_link column.
		 *
		 * @param array $feed The feed being included in the feed list.
		 *
		 * @return string
		 */
		public function get_column_value_feed_name( $feed ) {
			$output = '';

			if ( rgars( $feed, 'meta/google_sheet_url' ) ) {
				$output .= sprintf(
					'<a href="%s" target="_blank" title="%s"><span class=""><span class="dashicons dashicons-media-spreadsheet"></span></a>',
					rgars( $feed, 'meta/google_sheet_url' ),
					esc_attr__( 'Open Spreadsheet', 'gp-google-sheets' )
				) . '&nbsp;';
			}

			$output .= self::get_feed_name( $feed );

			return $output;
		}

		public static function get_feed_name( $feed ) {
			return rgars( $feed, 'meta/feed_name' ) ? rgars( $feed, 'meta/feed_name' ) : rgars( $feed, 'meta/feedName' );
		}

		public static function get_addon_slug() {
			return self::get_instance()->get_slug();
		}

		public function get_title() {
			return $this->_title;
		}

		/**
		 * save_sheet_url_after_create_sheet
		 *
		 * Gravity Forms doesn't make it easy for add-ons to change feed
		 * settings during the validation step, and that's when we create the
		 * Sheet. This callback on the gform_post_save_feed_settings hook takes
		 * the new Sheet URL and saves it in the feed settings that were just
		 * saved.
		 *
		 * @param string  $feed_id  The ID of the feed which was saved.
		 * @param int     $form_id  The current form ID associated with the feed.
		 * @param array   $settings An array containing the settings and mappings for the feed.
		 * @param GFAddOn $addon    The current instance of the GFAddOn object which extends GFFeedAddOn or GFPaymentAddOn (i.e. GFCoupons, GF_User_Registration, GFStripe).
		 * @return void
		 */
		public function save_sheet_url_after_create_sheet( $feed_id, $form_id, $settings, $addon ) {
			$spreadsheet_url = rgpost( '_gform_setting_google_sheet_url' );

			if ( empty( $spreadsheet_url ) || ! empty( rgars( $settings, 'meta/google_sheet_url' ) ) ) {
				return;
			}

			$settings['google_sheet_url'] = $spreadsheet_url;
			if ( rgpost( '_gform_setting_google_sheet_id' ) === 'add' ) {
				// When creating a new spreadsheet, we only add one sheet to it which will have an id of "0"
				$settings['google_sheet_id'] = '0';
			} else {
				$settings['google_sheet_id'] = rgpost( '_gform_setting_google_sheet_id' );
			}

			$this->update_feed_meta( $feed_id, $settings );
		}

		// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

		private function js_url( $file_name ) {
			return plugins_url( 'js/built/' . $file_name, dirname( __FILE__ ) . '/gp-google-sheets.php' );
		}

		private function assets_url( $file_name ) {
			return plugins_url( 'assets/' . $file_name, dirname( __FILE__ ) . '/gp-google-sheets.php' );
		}

		/**
		 * Return the scripts which should be enqueued.
		 *
		 * @return array
		 */
		public function scripts() {
			$settings_global_asset_file      = include( plugin_dir_path( __FILE__ ) . 'js/built/gp-google-sheets-settings-global.asset.php' );
			$settings_feed_asset_file        = include( plugin_dir_path( __FILE__ ) . 'js/built/gp-google-sheets-settings-feed.asset.php' );
			$form_editor_gppa_asset_file     = include( plugin_dir_path( __FILE__ ) . 'js/built/gp-google-sheets-settings-form-editor-gppa.asset.php' );
			$form_editor_gppa_vue_asset_file = include( plugin_dir_path( __FILE__ ) . 'js/built/gp-google-sheets-settings-form-editor-gppa-vue.asset.php' );

			wp_set_script_translations( self::SCRIPT_HANDLE, 'gp-google-sheets', plugin_dir_path( __FILE__ ) . 'languages/' );
			wp_set_script_translations( self::SCRIPT_HANDLE . '_plugin', 'gp-google-sheets', plugin_dir_path( __FILE__ ) . 'languages/' );

			$scripts = array(
				// Plugin settings script
				array(
					'handle'    => self::SCRIPT_HANDLE . '_plugin',
					'src'       => $this->js_url( 'gp-google-sheets-settings-global.js' ),
					'callback'  => array( $this, 'localize_plugin_settings_script' ),
					'version'   => $settings_global_asset_file['version'],
					'deps'      => $settings_global_asset_file['dependencies'],
					'in_footer' => true, // Required for React
					'enqueue'   => array(
						//Forms → Settings → Google Sheets
						array(
							'query' => 'page=gf_settings&subview=' . $this->_slug,
						),
					),
				),

				// Feed settings script
				array(
					'handle'    => self::SCRIPT_HANDLE,
					'src'       => $this->js_url( 'gp-google-sheets-settings-feed.js' ),
					'callback'  => array( $this, 'localize_feed_settings_script' ),
					'version'   => $settings_feed_asset_file['version'],
					'deps'      => $settings_feed_asset_file['dependencies'],
					'in_footer' => true, // Required for React
					'enqueue'   => array(
						array(
							'query' => 'page=gf_edit_forms&view=settings&id=_notempty_&fid=_notempty_&subview=' . $this->_slug,
						),
						array(
							'query' => 'page=gf_edit_forms&view=settings&id=_notempty_&fid=0&subview=' . $this->_slug,
						),
					),
				),
			);

			if (
				defined( 'GPPA_VERSION' )
				/** @phpstan-ignore-next-line (GPPA_VERSION can vary) */
				&& version_compare( GPPA_VERSION, '2.0.14', '>=' )
			) {
				// If GPPA admin is Vue (<2.1)
				/** @phpstan-ignore-next-line (GPPA_VERSION can vary) */
				if ( version_compare( GPPA_VERSION, '2.1', '<' ) ) {
					$scripts[] = array(
						'handle'  => self::SCRIPT_HANDLE . '_form_editor',
						'src'     => $this->js_url( 'gp-google-sheets-settings-form-editor-gppa-vue.js' ),
						'version' => $form_editor_gppa_vue_asset_file['version'],
						'deps'    => $form_editor_gppa_vue_asset_file['dependencies'],
						'enqueue' => array(
							array( 'admin_page' => array( 'form_editor' ) ),
						),
					);
				} else {
					$scripts[] = array(
						'handle'  => self::SCRIPT_HANDLE . '_form_editor',
						'src'     => $this->js_url( 'gp-google-sheets-settings-form-editor-gppa.js' ),
						'version' => $form_editor_gppa_asset_file['version'],
						'deps'    => $form_editor_gppa_asset_file['dependencies'],
						'enqueue' => array(
							array( 'admin_page' => array( 'form_editor' ) ),
						),
					);
				}
			}

			return array_merge( parent::scripts(), $scripts );
		}

		/**
		 * Localize the feed settings script.
		 */
		public function localize_feed_settings_script() {
			$license_info = $this->get_gp_license_info();
			$form_id      = ( ! empty( $_GET['id'] ) ? intval( $_GET['id'] ) : null );
			$user_id      = get_current_user_id();

			$error_message = null;

			$have_sheet  = $this->feed_has_sheet();
			$spreadsheet = Spreadsheet::from_feed( $this->get_current_feed() );

			/** @var \Gravity_Forms\Gravity_Forms\Settings\Settings */
			$settings_renderer = $this->get_settings_renderer();

			if ( $have_sheet !== true && $settings_renderer !== false ) {
				$existing_field = $settings_renderer->get_field( 'google_sheet_url_field' );

				// not using rgar() as $existing_field is not of type array.
				if ( ! empty( $existing_field ) && ! empty( $existing_field['error'] ) ) {
					$error_message = $existing_field['error'];
				}

				if ( $this->get_setting( 'google_sheet_url' ) && ! $settings_renderer->is_save_postback() ) {
					$unable_to_load_spreadsheet = esc_html__( 'Unable to load spreadsheet from Google Sheets API.' );
					$how_to_fix_it              = esc_html__( 'How do I fix this error?' );
					$fix_instructions           = esc_html__( 'Disconnect this feed by clicking the “Disconnect” button below. Then, use the "Connect Existing Sheet” option to reconnect your spreadsheet.' );

					$error_message = "<b>{$unable_to_load_spreadsheet}</b><br /><br />";

					/*
					 * If we're unable to get the spreadsheet via the API but a URL to one is set, try to pass the error
					 * from Google into the UI if it's available.
					*/
					if ( $spreadsheet->get_error() ) {
						$spreadsheet_error = $spreadsheet->get_error();
						$error_message    .= "<code class=\"gpgs_google_error_message\">{$spreadsheet_error}</code><br /><br />";
					}

					$error_message .= "<b class=\"gpgs_how_to_fix_header\">{$how_to_fix_it}</b>{$fix_instructions}";
				}
			}

			wp_localize_script( self::SCRIPT_HANDLE, 'gpgs_settings_strings', array(
				'slug'                       => $this->_slug,
				'feed_id'                    => $this->get_current_feed_id(),
				'form_id'                    => $form_id,
				'ajax_url'                   => admin_url( 'admin-ajax.php' ),
				'nonce'                      => wp_create_nonce( self::NONCE_AJAX ),
				'oauth_validation_token'     => GFCommon::openssl_encrypt( (string) time() ),
				'gravity_perks_license_id'   => $license_info['id'],
				'gravity_perks_license_hash' => $license_info['hash'],
				'gwiz_oauth_service_url'     => GP_Google_Sheets::GWIZ_OAUTH_SERVICE_URL,
				'site_url'                   => get_site_url(),
				'user_id'                    => $user_id,
				'sheet_url'                  => $spreadsheet ? $spreadsheet->get_url() : null,
				'spreadsheet_name'           => $spreadsheet ? $spreadsheet->get_name() : null,
				'sheet_name'                 => $spreadsheet ? $spreadsheet->get_sheet_name() : null,
				'error_message'              => $error_message,
			) );
		}

		/**
		 * Localize the plugin settings script.
		 */
		public function localize_plugin_settings_script() {
			$license_info = $this->get_gp_license_info();
			$user_id      = get_current_user_id();

			/**
			 * Filter whether to show the callout to the GP Populate Anything integration if Populate Anything is
			 * not installed.
			 *
			 * @param bool $show_gppa_integration Whether to show the callout. Default is `true`.
			 *
			 * @since 1.0-beta-2.0
			 */
			$show_gppa_integration = apply_filters( 'gpgs_show_gppa_integration', true );

			/**
			 * Filter whether the "Danger Zone" section should be shown in the plugin settings.
			 *
			 * Right now, the only utility in the Danger Zone is purging the Action Scheduler queue.
			 *
			 * Even if this filter is set to true, the user must have uninstall capabilities for GP Google Sheets.
			 *
			 * @since 1.1
			 */
			$show_danger_zone = apply_filters( 'gpgs_show_danger_zone', false ) && GFCommon::current_user_can_any( array( 'gp-google-sheets_uninstall' ) );

			wp_localize_script( self::SCRIPT_HANDLE . '_plugin', 'gpgs_settings_plugin_strings', array(
				'slug'                       => $this->_slug,
				'ajax_url'                   => admin_url( 'admin-ajax.php' ),
				'admin_url'                  => admin_url(),
				'nonce'                      => wp_create_nonce( self::NONCE_AJAX ),
				'oauth_validation_token'     => GFCommon::openssl_encrypt( (string) time() ),
				'gravity_perks_license_id'   => $license_info['id'],
				'gravity_perks_license_hash' => $license_info['hash'],
				'gwiz_oauth_service_url'     => GP_Google_Sheets::GWIZ_OAUTH_SERVICE_URL,
				'site_url'                   => get_site_url(),
				'user_id'                    => $user_id,
				'gppa_installed'             => ! is_wp_error( validate_plugin( 'gp-populate-anything/gp-populate-anything.php' ) ),
				'has_available_perks'        => GWPerks::has_available_perks(),
				'upgrade_license_url'        => add_query_arg( array(
					'utm_campaign' => 'gp-ui',
					'utm_medium'   => 'gpgs-settings',
					'utm_source'   => 'gpgs-gppa-upgrade',
				), GWPerks::get_license_upgrade_url() ),
				'gppa_activated'             => class_exists( 'GPPA_Object_Type' ),
				'install_gppa_url'           => gp_google_sheets()->get_plugin_action_url( 'install', 'gp-populate-anything/gp-populate-anything.php' ),
				'activate_gppa_url'          => gp_google_sheets()->get_plugin_action_url( 'activate', 'gp-populate-anything/gp-populate-anything.php' ),
				'show_gppa_integration'      => $show_gppa_integration,
				'show_danger_zone'           => $show_danger_zone,
				'purge_action_scheduler_url' => $show_danger_zone ? add_query_arg( array(
					'gpgs_purge_action_scheduler' => wp_create_nonce( 'gpgs_purge_action_scheduler' ),
					'gpgs_purge_action_timestamp' => 'TIMESTAMP_PLACEHOLDER', // Used to prevent accidentally navigating and purging again
				) ) : '',
			) );
		}

		/**
		 * Return the stylesheets which should be enqueued.
		 *
		 * @return array
		 */
		public function styles() {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

			$styles = array(

				//Feed settings stylesheet
				array(
					'handle'  => self::SCRIPT_HANDLE,
					'src'     => $this->assets_url( "settings-feed{$min}.css" ),
					'version' => $this->_version,
					'enqueue' => array(
						array(
							'query' => 'page=gf_edit_forms&view=settings&id=_notempty_&subview=' . $this->_slug,
						),
					),
				),

				//Feed & plugin settings stylesheet
				array(
					'handle'  => self::SCRIPT_HANDLE . '_plugin',
					'src'     => $this->assets_url( "settings-global{$min}.css" ),
					'version' => $this->_version,
					'enqueue' => array(
						array(
							'query' => 'page=gf_edit_forms&view=settings&id=_notempty_&subview=' . $this->_slug,
						),
						array(
							'query' => 'page=gf_settings&subview=' . $this->_slug,
						),
					),
				),

				// Entry details
				array(
					'handle'  => self::SCRIPT_HANDLE . '_entry_details',
					'src'     => $this->assets_url( "entry-details{$min}.css" ),
					'version' => $this->_version,
					'enqueue' => array(
						array(
							'query' => 'page=gf_entries&view=entry',
						),
					),
				),
			);

			return array_merge( parent::styles(), $styles );
		}

		/**
		 * Helper method to get Gravity Perks license ID and hash for authenticating with the OAuth backend.
		 */
		public function get_gp_license_info() {
			$license_key = GWPerks::get_license_key();

			if ( ! $license_key ) {
				return array(
					'id'   => '',
					'hash' => '',
				);
			}

			$license = GravityPerks::get_license_data();

			return array(
				'id'   => isset( $license['ID'] ) ? $license['ID'] : '',
				'hash' => md5( $license_key ),
			);
		}

		// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

		/**
		 * Set the icon on our Form Settings tab
		 */
		public function form_settings_icon() {
			return '<i class="fa fa-table"></i>';
		}

		public function form_settings_title() {
			// translators: placeholder is form name
			return sprintf( esc_html__( '%s Feeds', 'gp-google-sheets' ), $this->get_title() );
		}

		public function get_menu_icon() {
			return file_get_contents( dirname( __FILE__ ) . '/assets/menu-icon.svg' );
		}

		/**
		 * This is a wrapper for Gravity Form's gform_tooltip() method that checks
		 * for the existence of that method before using it to avoid exceptions when
		 * gform_tooltip() may be called when Gravity Form is not loaded. Returns
		 * the tooltip name in brackets if the method is not defined.
		 *
		 * This is useful when gform_tooltip() may be called in an AJAX callback.
		 */
		function maybe_tooltip_html( $tooltip_name, $css_class = '', $return = false ) {
			if ( ! function_exists( 'gform_tooltip' ) ) {
				//Make shortcode like codes that we can replace in JavaScript
				//google_sheet_insert_test_row is the name of the tooltip
				//[tooltip_insert_test_row] is the code JS will look for
				return sprintf( '[%s] ', str_replace( 'google_sheet_', 'tooltip_', $tooltip_name ) );
			}
			return gform_tooltip( $tooltip_name, $css_class, $return ) . ' ';
		}

		/**
		 * plugin_settings_fields
		 *
		 * Provides the fields that appear on the Google Sheets tab of the
		 * Gravity Forms global settings (and not a specific form's settings).
		 *
		 * @return array
		 */
		public function plugin_settings_fields() {
			$sections = array(
				array(
					'fields' => array(
						array(
							'name' => 'plugin_settings_react_root',
							'type' => 'loading',
						),
					),
				),
			);

			return $sections;
		}

		/**
		 * Purges all pending, failed, in-progress, and past-due GP Google Sheets actions in Action Scheduler.
		 */
		public function maybe_purge_action_scheduler() {
			if (
				! apply_filters( 'gpgs_show_danger_zone', false )
				|| ! GFCommon::current_user_can_any( array( 'gp-google-sheets_uninstall' ) )
			) {
				return false;
			}

			$purge_nonce     = rgget( 'gpgs_purge_action_scheduler' );
			$purge_timestamp = rgget( 'gpgs_purge_action_timestamp' );

			if ( ! $purge_nonce || ! $purge_timestamp ) {
				return false;
			}

			if ( ! wp_verify_nonce( $purge_nonce, 'gpgs_purge_action_scheduler' ) ) {
				return false;
			}

			// Only allow the purge request timestamp to be processed within 10 seconds of the original request to
			// prevent accidental purging.
			if ( time() - (int) $purge_timestamp > 10 ) {
				wp_die(
					esc_html__( 'Action Scheduler purge link has expired. Please go back and click the "Purge Action Scheduler" button again.', 'gp-google-sheets' ),
					esc_html__( 'GP Google Sheets', 'gp-google-sheets' ),
					array(
						'response'  => 200,
						'back_link' => true,
					)
				);
			}

			/**
			 * Purge all uncomplete GP Google Sheets actions in Action Scheduler.
			 *
			 * We know they're GP Google Sheets actions because they have the "gp_google_sheets_" prefix in the hook.
			 */
			global $wpdb;

			$deleted_row_count = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE hook LIKE %s AND status NOT IN (%s)",
					$wpdb->esc_like( 'gp_google_sheets_' ) . '%',
					'complete'
				)
			);

			wp_die(
				esc_html__( 'GP Google Sheets actions have been successfully purged.', 'gp-google-sheets' )
				. '<br /><br />'
				// translators: %s is the number of rows deleted.
				. sprintf( _n( '%s action was deleted.', '%s actions were deleted.', $deleted_row_count, 'gp-google-sheets' ), number_format_i18n( $deleted_row_count ) ),
				esc_html__( 'GP Google Sheets', 'gp-google-sheets' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}

		public function get_plugin_action_url( $action, $plugin_file ) {
			return esc_attr( add_query_arg( array(
				'gwp'  => false,
				'from' => $this->get_slug(),
			), htmlspecialchars_decode( $this->perk->get_link_for( $action, $plugin_file ) ) ) );
		}

		/**
		 * Add a link to the plugin installation page that will take the user back to the Google Sheets plugin settings
		 * page after installing Populate Anything from the Google Sheets settings page.
		 *
		 * @param $actions
		 * @param $api
		 * @param $plugin_file
		 *
		 * @return mixed
		 */
		public function add_back_to_plugin_settings_action( $actions, $api, $plugin_file ) {
			if ( rgget( 'from' ) !== $this->get_slug() || ! $plugin_file ) {
				return $actions;
			}

			unset( $actions['plugins_page'] );

			// translators: placeholder is the plugin short title
			$actions['manage_perks'] = '<a href="' . $this->get_plugin_settings_url() . '">' . sprintf( __( 'Back to %s Settings', 'gp-google-sheets' ), $this->get_short_title() ) . '</a>';

			if ( isset( $actions['activate_plugin'] ) ) {
				$actions['activate_plugin'] = sprintf(
					'<a class="button button-primary" href="%s" target="_parent">%s</a>',
					wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ) . '&amp;from=' . $this->get_slug(), 'activate-plugin_' . $plugin_file ),
					__( 'Activate Perk', 'gp-google-sheets' )
				);
			}

			return $actions;
		}

		/**
		 * Redirect back to the Google Sheets settings page after activating Populate Anything from the Google Sheets
		 * settings page.
		 *
		 * @param $location
		 *
		 * @return mixed|string
		 */
		public function redirect_to_plugin_settings( $location ) {
			if ( rgget( 'from' ) !== $this->get_slug() ) {
				return $location;
			}

			$parsed_url = parse_url( $location );
			parse_str( $parsed_url['query'], $query );
			if ( ! isset( $query['action'] ) ) {
				$location = $this->get_plugin_settings_url();
			}

			return $location;
		}

		/**
		 * Used for React roots in settings.
		 */
		public function settings_loading( $field, $echo = true ) {
			$output = '<span>Loading...</span>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * @since  1.0
		 *
		 * @param  array $feed  Feed object.
		 * @param  array $entry Entry object.
		 * @param  array $form  Form object.
		 */
		public function process_feed( $feed, $entry, $form ) {
			Retry::enqueue_async_action(
				'gp_google_sheets_add_entry_to_sheet',
				array(
					'feed_id'  => $feed['id'],
					'entry_id' => $entry['id'],
					'form_id'  => $form['id'],
				),
				$entry['id']
			);

			return null;
		}

		/**
		 * Setup fields for feed settings.
		 *
		 * @since 1.0
		 *
		 * @return array
		 */
		public function feed_settings_fields() {
			$have_sheet  = $this->feed_has_sheet();
			$spreadsheet = Spreadsheet::from_feed( $this->get_current_feed() );

			$fields = array(
				array(
					'fields' => array(

						//Name
						array(
							'name'          => 'feed_name',
							'label'         => esc_html__( 'Name', 'gp-google-sheets' ),
							'type'          => 'text',
							'class'         => 'medium',
							'required'      => true,
							'default_value' => $this->get_default_feed_name(),
							'tooltip'       => '<h6>' . esc_html__( 'Name', 'gp-google-sheets' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this feed. If creating a new sheet, this will be used as the sheet\'s name.', 'gp-google-sheets' ),
						),

						/*
						 * GPGS version that the feed is created with or saved with if the feed was created prior to
						 * this field being added.
						 */
						array(
							'name'          => 'gpgs_version',
							'type'          => 'hidden',
							'default_value' => $this->_version,
						),

						/**
						 * Google Spreadsheet URL, a hidden setting so this value
						 * doesn't get erased when settings are saved.
						 *
						 * NOTE: even though this is named "sheet_url", it's still
						 * just the url to the spreadsheet and not necessarily
						 * a direct link to the connected sheet itself.
						 */
						array(
							'label' => __( 'Google Sheet URL', 'gp-google-sheets' ),
							'name'  => 'google_sheet_url',
							'type'  => 'hidden',
						),

						/**
						 * Google Sheet ID, a hidden setting so this value
						 * doesn't get erased when settings are saved.
						 */
						array(
							'label'               => __( 'Google Sheet ID', 'gp-google-sheets' ),
							'name'                => 'google_sheet_id',
							'type'                => 'hidden',
							'validation_callback' => function( $field, $value ) {
								if ( empty( rgpost( '_gform_setting_google_sheet_url' ) ) ) {
									if ( $value == 'add' || rgblank( $value ) ) {
										//create sheet
										try {
											$spreadsheet = new Spreadsheet();
											$account = Google_Account::from_email( rgpost( '_gform_setting_google_sheet_account' ) );
											$new_spreadsheet = $spreadsheet->create( rgpost( '_gform_setting_feed_name' ), $account );

											$_POST['_gform_setting_google_sheet_url'] = $new_spreadsheet->get_url();
											$_POST['_gform_setting_google_sheet_id'] = 'add';
										} catch ( \Exception $e ) {
											// translators: %s is an error message.
											$field->set_error( sprintf( __( 'Unable to create sheet. Error: %s', 'gp-google-sheets' ), $e->getMessage() ) );
										}
									}
								}
							},
						),

						array(
							'label' => __( 'Google Picker Token', 'gp-google-sheets' ),
							'name'  => 'picked_token',
							'type'  => 'hidden',
						),
					),
				),
				array(
					'title'  => esc_html__( 'Google Sheets Settings', 'gp-google-sheets' ),
					'fields' => array(
						array(
							'name' => 'feed_settings_google_sheet_settings_react_root',
							'type' => 'loading',
						),
					),
				),

				// Column Mapping
				array(
					'title'       => esc_html__( 'Column Mapping', 'gp-google-sheets' ),
					'description' => esc_html__( 'Specify which entry data should populate which columns in your Google Sheet.' ),
					'fields'      => array(
						array(
							'type'      => 'generic_map',
							'name'      => 'column_mapping',
							'key_field' => array(
								'title'       => __( 'Sheet Column', 'gp-google-sheets' ),
								'placeholder' => __( 'Column heading', 'gp-google-sheets' ),
								'choices'     => $spreadsheet ? $spreadsheet->field_map_key_field_choices() : array(),
							),
						),
					),
				),

				//Editing Settings
				array(
					'title'  => esc_html__( 'Additional Options', 'gp-google-sheets' ),
					'fields' => array(
						array(
							'label'   => esc_html__( 'Update &amp; Delete Rows', 'gp-google-sheets' ),
							'type'    => 'checkbox',
							'name'    => 'edit_rows',
							'tooltip' => esc_html__( 'When entries are edited, also edit the corresponding row in the Google Sheet. Delete rows from the Sheet when entries are moved to trash or marked as spam. Entries are re-appended to the bottom of the Sheet after being restored from trash or spam.', 'gp-google-sheets' ),
							'choices' => array(
								array(
									'label' => esc_html__( 'Edit rows when entries are edited, and delete rows when entries are trashed or marked as spam', 'gp-google-sheets' ),
									'name'  => 'edit_rows',
								),
							),
						),
					),
				),

				//Conditional Logic
				array(
					'title'  => esc_html__( 'Conditional Logic', 'gp-google-sheets' ),
					'fields' => array(
						array(
							'label' => '',
							'name'  => 'conditional_logic',
							'type'  => 'feed_condition',
						),
					),
				),
			);

			//debug
			if ( defined( 'GP_GOOGLE_SHEETS_DEBUG' ) && GP_GOOGLE_SHEETS_DEBUG && ! $have_sheet ) {
				$fields[] = array(
					'title'  => 'Developer Metadata (debug)',
					'fields' => array(
						array(
							'label' => 'Column Metadata',
							'type'  => 'metadata_output_column',
							'name'  => 'debug_metadata_column',
						),
						array(
							'label' => 'Row Metadata',
							'type'  => 'metadata_output_row',
							'name'  => 'debug_metadata_row',
						),
					),
				);
			}

			return $fields;
		}

		/**
		 * Duplicated from GFFeedAddOn::get_feed_settings_field(), but without the check for $this->_feed_settings_fields
		 * as its private and cannot be unset().
		 *
		 * @return mixed
		 */
		public function get_feed_settings_fields_no_cache() {
			/**
			 * Filter the feed settings fields (typically before they are rendered on the Feed Settings edit view).
			 *
			 * @param array $feed_settings_fields An array of feed settings fields which will be displayed on the Feed Settings edit view.
			 * @param object $addon The current instance of the GFAddon object (i.e. GF_User_Registration, GFPayPal).
			 *
			 * @since 2.0
			 *
			 * @return array
			 */
			$feed_settings_fields = apply_filters( 'gform_addon_feed_settings_fields', $this->feed_settings_fields(), $this );
			$feed_settings_fields = apply_filters( "gform_{$this->_slug}_feed_settings_fields", $feed_settings_fields, $this );

			/** @phpstan-ignore-next-line */
			$this->_feed_settings_fields = $this->add_default_feed_settings_fields_props( $feed_settings_fields );

			return $this->_feed_settings_fields;
		}

		public function feed_settings_init() {
			//Look at the sheet, make sure our field map is still accurate, do not run this if saving.
			if ( rgempty( 'gform-settings-save' ) ) {
				$this->maybe_update_field_map_setting();
			}

			parent::feed_settings_init();

			/*
			 * Set fields again after saving has been processed as the save postback happens AFTER the
			 * settings are rendered by default, which causes have some values to not be ready when performing conditionals.
			 */
			/** @var \Gravity_Forms\Gravity_Forms\Settings\Settings */
			$settings_renderer = $this->get_settings_renderer();

			if ( $settings_renderer->is_save_postback() ) {
				// Get current form.
				$form = ( $this->get_current_form() ) ? $this->get_current_form() : array();
				$form = gf_apply_filters( array( 'gform_admin_pre_render', rgar( $form, 'id', 0 ) ), $form );

				// Get current feed ID, feed object.
				$feed_id      = $this->_multiple_feeds ? $this->get_current_feed_id() : $this->get_default_feed_id( rgar( $form, 'id', 0 ) );
				$current_feed = $feed_id ? $this->get_feed( $feed_id ) : array();

				// Refresh initial values in the settings otherwise custom columns in the field map may not properly change to a select.
				$settings_renderer->set_values( rgar( $current_feed, 'meta' ) );

				$sections = $this->get_feed_settings_fields_no_cache();
				$sections = $this->prepare_settings_sections( $sections, 'feed_settings' );
				$settings_renderer->set_fields( $sections );
			}
		}

		public function feed_list_message() {
			return GFFeedAddOn::feed_list_message();
		}

		/**
		 * @return bool
		 */
		protected function feed_has_sheet() {
			$feed_id = $this->get_current_feed_id();

			// Do not validate if creating a new feed
			if ( ! $feed_id && rgpost( '_gform_setting_google_sheet_url' ) ) {
				return true;
			}

			// Do not validate if saving feed settings and "Create new spreaedsheet" is selected
			if (
				rgpost( 'gform-settings-save' ) === 'save'
				&& ! empty( rgpost( '_gform_setting_google_sheet_url' ) )
			) {
				return true;
			}

			if ( $feed_id ) {
				$spreadsheet = Spreadsheet::from_feed( $this->get_current_feed() );

				if ( $spreadsheet && $spreadsheet->has_spreadsheet() ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * maybe_update_field_map_setting
		 *
		 * Compare the Sheet field map with the feed settings field map. The
		 * user could have edited the sheet columns & our map needs updated.
		 *
		 * @param string  $feed_id  The ID of the feed which was saved.
		 * @param array   $settings An array containing the settings and mappings for the feed.
		 *
		 * @return array Updated feed
		 */
		public function maybe_update_field_map_setting( $feed_id = null, $settings = null ) {
			$feed           = $this->get_feed( isset( $feed_id ) ? $feed_id : $this->get_current_feed_id() );
			$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );

			if ( ! $feed ) {
				return $feed;
			}

			if ( empty( $spreadsheet_id ) ) {
				// If the spreadsheet isn't found, revert any columns with a custom key back to gf_custom after disconnecting.
				if ( ! empty( $feed['meta']['column_mapping'] ) && is_array( $feed['meta']['column_mapping'] ) ) {
					foreach ( $feed['meta']['column_mapping'] as &$mapping ) {
						if ( ! rgblank( $mapping['custom_key'] ) ) {
							$mapping['key'] = 'gf_custom';
						}
					}
				}

				$this->update_feed_meta( $feed['id'], $feed['meta'] );

				return $feed;
			}

			$spreadsheet = Spreadsheet::from_feed( $feed );

			//Get the column headers & field IDs from developer metadata
			if ( ! $spreadsheet->get_google_account() ) {
				gp_google_sheets()->log_debug( __METHOD__ . '(): Could not find Google account for feed #' . $feed['id'] );
				return $feed;
			}

			try {
				$field_map_sheet = $spreadsheet->metadata_field_map();
			} catch ( \Exception $e ) {
				$field_map_sheet = false;
			}

			if ( empty( $field_map_sheet ) ) {
				return $feed;
			}

			if ( is_array( $field_map_sheet ) ) {
				$field_map_sheet = gpgs_fill_missing_array_keys( $field_map_sheet );
			}

			if ( ! is_array( rgars( $feed, 'meta/column_mapping' ) ) ) {
				return $feed;
			}

			$field_map_settings = $spreadsheet->flatten_column_mapping();

			if ( $field_map_sheet != $field_map_settings
				|| sizeof( isset( $feed['meta']['column_mapping'] ) ? $feed['meta']['column_mapping'] : array() ) != sizeof( array_filter( $field_map_sheet ) ) ) {
				//Something has changed, update column_mapping before displaying
				$column_mapping = array();
				foreach ( $field_map_sheet as $index => $field_id ) {
					if ( empty( $field_id ) ) {
						continue;
					}

					//Maybe this column was moved
					$settings_index = array_search( $field_id, $field_map_settings );

					if ( strpos( $field_id, 'gf_custom:' ) === 0 ) {
						$column_mapping[] = array(
							'key'          => gpgs_number_to_column_letters( $index + 1 ),
							'custom_key'   => '',
							'value'        => 'gf_custom',
							'custom_value' => str_replace( 'gf_custom:', '', $field_map_settings[ $settings_index ] ),
						);
					} else {
						$column_mapping[] = array(
							'key'          => gpgs_number_to_column_letters( $index + 1 ),
							'custom_key'   => '',
							'value'        => $field_id,
							'custom_value' => '',
						);
					}
				}

				if ( $feed['meta']['column_mapping'] != $column_mapping ) {
					$feed['meta']['column_mapping'] = $column_mapping;
					$this->update_feed_meta( $feed['id'], $feed['meta'] );
				}
			}

			return $feed;
		}

		/**
		 * @param string  $feed_id  The ID of the feed which was saved.
		 * @param int     $form_id  The current form ID associated with the feed.
		 * @param array   $settings An array containing the settings and mappings for the feed.
		 * @param \GFAddOn $addon    The current instance of the GFAddOn object which extends GFFeedAddOn or GFPaymentAddOn (i.e. GFCoupons, GF_User_Registration, GFStripe).
		 * @return void
		 */
		public function update_sheet_after_field_map_change( $feed_id, $form_id, $settings, $addon ) {
			$new_sheet = false;

			if ( empty( $settings['google_sheet_url'] ) ) {
				// Persist the sheet_url and sheet_id if the user just created a new sheet
				$post_url = rgpost( '_gform_setting_google_sheet_url' );
				if ( ! empty( $post_url ) && rgpost( '_gform_setting_google_sheet_id' ) == 'add' ) {
					$settings['google_sheet_url'] = $post_url;
					$settings['google_sheet_id']  = '0';

					$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed(
						array( 'meta' => array( 'google_sheet_url' => $post_url ) )
					);

					$new_sheet = true;
				} else {
					return;
				}
			}

			//Get the map out of the sheet
			$feed = $this->get_feed( $feed_id );

			if ( ! $feed ) {
				return;
			}

			$spreadsheet = Spreadsheet::from_feed( $feed );

			if ( empty( $spreadsheet_id ) ) {
				$spreadsheet_id = $spreadsheet->get_id();
			}

			/**
			 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets|null
			 */
			$spreadsheets = $spreadsheet->get_sheets_resource( 'spreadsheets' );

			if ( ! $spreadsheets ) {
				gp_google_sheets()->log_error( 'Could not get Google Spreadsheets Resource.' );
				return;
			}

			$metadata_field_map = $spreadsheet->metadata_field_map();

			if ( $metadata_field_map === false ) {
				$metadata_field_map = array();
			}

			$field_map_sheet = $metadata_field_map;

			if ( is_array( $field_map_sheet ) && ! empty( $field_map_sheet ) ) {
				$field_map_sheet = gpgs_fill_missing_array_keys( $field_map_sheet );
			}

			if ( ! is_array( rgars( $settings, 'column_mapping' ) ) ) {
				return;
			}

			//Has the field map changed?
			if ( $spreadsheet->flatten_column_mapping() == $field_map_sheet
			&& ! $this->map_contains_custom_key( $settings['column_mapping'] ) ) {
				//No
				return;
			}

			//Build an array of requests to send in a batch
			$requests = array();

			$claimed_columns_count = 0;

			for ( $c = 0; $c < sizeof( $settings['column_mapping'] ); $c++ ) {
				$column = $settings['column_mapping'][ $c ];

				//Is the map value stored in the field map or a field id?
				$column_metadata_value = ( $column['value'] == 'gf_custom' ? 'gf_custom:' . $column['custom_value'] : $column['value'] );

				//Is this a column the user just added? Or already mapped?
				$column_to_claim = '';
				if ( $column['key'] == 'gf_custom' ) {
					//User is adding this column
					//Are there empty columns in the sheet?
					$column_to_claim = $spreadsheet->find_first_empty_column_index();

					/*
					 * If we're creating a new sheet entirely, we need to claim any columns after A-Z.
					 *
					 * In this context since we're batching requests, append_column() will keep returning 0 as the index.
					 */
					if ( -1 == $column_to_claim || ( $new_sheet && $c > 25 ) ) {
						//Append a column to the right of the sheet
						$column_to_claim = $spreadsheet->append_column();

						if ( $new_sheet && $column_to_claim === 0 ) {
							$column_to_claim = $c;
						}
					} else {
						/**
						 * $column_to_claim doesn't take into consideration the
						 * columns we are going to add in $requests array that
						 * we have not yet processed, so add our counter.
						 */
						$column_to_claim += $claimed_columns_count;
					}
				} else {
					//This column was moved maybe, make sure $column_metadata_value is somewhere
					$new_place = array_search( $column_metadata_value, $field_map_sheet );
					if ( $new_place === false
					|| $field_map_sheet[ $new_place ] != $column_metadata_value
					|| gpgs_number_to_column_letters( $new_place + 1 ) != $column['key'] ) {
						//This column isn't in the sheet yet or has moved
						$column_to_claim = Range_Parser::letters_to_index( $column['key'] );
						unset( $field_map_sheet[ $column_to_claim ] );
					} else {
						//This column is where we expect it
						unset( $field_map_sheet[ $new_place ] );
						continue;
					}
				}

				//Claim column $column_to_claim for this field
				// - row 1 value $column['custom_key']
				// - column developer metadata of $column_metadata_value

				// Write metadata, first delete the old metadata then create.
				// This is easier/more reliable than an update request.
				if ( ! empty( $metadata_field_map ) ) {
					$requests[] = $spreadsheet->create_request_delete_column( $column_to_claim );
				}

				$requests[] = $spreadsheet->create_request_write_column( $column_to_claim, $column_metadata_value );
				$claimed_columns_count++;

				if ( ! empty( $column['custom_key'] ) ) {
					$sheet_name = $spreadsheet->get_sheet_name();

					//Write contents of row 1
					$write_range = gpgs_number_to_column_letters( $column_to_claim + 1 ) . '1';
					$write_range = "{$sheet_name}!{$write_range}";

					$requests[] = $spreadsheet->create_write_rows_request( $write_range, array( array( $column['custom_key'] ) ) );
				}

				//Save column letter in $column['key']
				$settings['column_mapping'][ $c ]['key'] = gpgs_number_to_column_letters( $column_to_claim + 1 );
				//$settings['column_mapping'][$c]['custom_key'] = '';
			}

			//Update the edited $settings
			$this->update_feed_meta( $feed_id, $settings );

			/**
			 * The items left in $field_map_sheet are mapped columns that are
			 * no longer in the feed's map. Stop updating them by removing the
			 * metadata value.
			 */
			foreach ( $field_map_sheet as $column_index => $metadata_value ) {
				if ( $metadata_value === '' ) {
					//User-added column
					continue;
				}
				//delete column metadata
				$requests[] = $spreadsheet->create_request_delete_column( $column_index );
			}
			if ( ! empty( $requests ) ) {
				$body = new GP_Google_Sheets\Dependencies\Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
				$body->setRequests( $requests );

				try {
					$spreadsheets->batchUpdate( $spreadsheet_id, $body );
				} catch ( \Exception $e ) {
					GFCommon::add_error_message(
						// translators: %s is an error message.
						sprintf( __( 'Failed to update sheet columns. Error: %s', 'gp-google-sheets' ), $e->getMessage() )
					);

					gp_google_sheets()->log_error( __METHOD__ . '(): ' . $e->getMessage() );
				}
			}
		}

		public function settings_metadata_output_column( $field, $echo = true ) {
			$html        = '';
			$spreadsheet = Spreadsheet::from_feed( $this->get_current_feed() );

			if ( $spreadsheet ) {
				$map = $spreadsheet->metadata_field_map();

				$html .= '<pre>' . print_r( $map, true ) . '</pre>';
			}

			if ( $echo ) {
				echo $html;

				return;
			}

			return $html;
		}

		public function settings_metadata_output_row( $field, $echo = true ) {
			$html        = '';
			$feed        = $this->get_current_feed();
			$spreadsheet = Spreadsheet::from_feed( $feed );

			if ( $$spreadsheet ) {
				try {
					$map = $spreadsheet->metadata_map_rows();

					$html .= '<pre>' . print_r( $map, true ) . '</pre>';
				} catch ( \Exception $e ) {
					$html .= '<pre>' . $e->getMessage() . '</pre>';
				}
			}

			if ( $echo ) {
				echo $html;

				return;
			}

			return $html;
		}

		protected function map_contains_custom_key( $column_mapping ) {
			return is_array( $column_mapping ) && in_array( 'gf_custom', array_column( $column_mapping, 'key' ) );
		}

		public function register_entry_meta_box( $meta_boxes, $entry, $form ) {
			if ( $this->has_feed( $form['id'] ) ) {
				$meta_boxes['gp_google_sheets'] = array(
					'title'         => esc_html__( 'Google Sheets', 'gp-notification-scheduler' ),
					'callback'      => array( $this, 'entry_meta_box' ),
					'context'       => 'side',
					'callback_args' => array( $entry, $form ),
				);
			}

			return $meta_boxes;
		}

		/**
		 * Meta box for getting the status of each feed and the ability to process it if it failed.
		 *
		 * @see GFEntryDetail::meta_box_notifications()
		 */
		public function entry_meta_box( $args, $metabox ) {
			$form    = $args['form'];
			$form_id = $form['id'];
			$entry   = $args['entry'];

			$feeds = $this->get_active_feeds( $form_id );
			?>
			<div class="gpgs-entry-feeds">
				<?php
				foreach ( $feeds as $feed ) :
					$spreadsheet = Spreadsheet::from_feed( $feed );

					if ( ! $spreadsheet ) {
						continue;
					}

					$fetched_sheet_name = $spreadsheet->get_name();
					$sheet_name         = $fetched_sheet_name ? $fetched_sheet_name : 'Open Sheet';
					$loaded_sheet       = $fetched_sheet_name;

					$inserted_timestamp = gform_get_meta( $entry['id'], "{$this->_slug}_{$feed['id']}_inserted" );
					$updated_timestamp  = gform_get_meta( $entry['id'], "{$this->_slug}_{$feed['id']}_updated" );

					$inserted_time = wp_date( __( 'M j, Y \a\t g:ia', 'gp-google-sheets' ), $inserted_timestamp );
					$updated_time  = wp_date( __( 'M j, Y \a\t g:ia', 'gp-google-sheets' ), $updated_timestamp );

					$process_feed_url = wp_nonce_url( add_query_arg( array(
						'gpgs_process_feed' => $feed['id'],
					) ), 'gpgs_process_feed_' . $feed['id'] );
					?>
					<div class="alert">
						<h4><?php echo esc_html( rgars( $feed, 'meta/feed_name' ) ); ?></h4>
						<div><span>Sheet:</span><span><a href="<?php echo esc_html( rgars( $feed, 'meta/google_sheet_url' ) ); ?>" target="_blank"><?php echo esc_html( $sheet_name ); ?></a></span></div>
						<?php if ( $inserted_timestamp ) : ?>
							<div><span>Inserted:</span><span><?php echo esc_html( $inserted_time ); ?></span></div>
						<?php endif; ?>

						<?php if ( $updated_timestamp ) : ?>
							<div><span>Updated:</span><span><?php echo esc_html( $updated_time ); ?></span></div>
						<?php endif; ?>

						<?php if ( ! $inserted_timestamp && $loaded_sheet ) : ?>
							<a class="button" href="<?php echo $process_feed_url; ?>">Process Feed</a>
						<?php endif; ?>

						<?php if ( ! $inserted_timestamp && ! $loaded_sheet ) : ?>
							<p class="alert error"><?php esc_html_e( 'To reprocess this feed, please reauthenticate with Google Sheets.', 'gp-google-sheets' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php
		}

		public function entry_details_maybe_process_feed( $form, $entry ) {
			$feed_id = rgget( 'gpgs_process_feed' );

			// Don't try to process if we don't have an ID
			if ( ! $feed_id ) {
				return;
			}

			// Security
			if ( ! check_admin_referer( 'gpgs_process_feed_' . $feed_id ) ) {
				return;
			}

			// Don't reprocess if it is already inserted
			$inserted_timestamp = gform_get_meta( $entry['id'], "{$this->_slug}_{$feed_id}_inserted" );

			if ( $inserted_timestamp ) {
				return;
			}

			$feed = $this->get_feed( $feed_id );
			$this->process_feed( $feed, $entry, $form );

			gp_google_sheets()->log_debug( __METHOD__ . '(): Processing feed via manual click from entry details.' );

			// Redirect back to the same page but without the gpgs_process_feed query arg.
			$redirect_url = remove_query_arg( 'gpgs_process_feed' );
			wp_safe_redirect( $redirect_url );
		}

		public function maybe_display_http_warning() {
			// Don't display the warning if the site is using HTTPS.
			if ( wp_is_using_https() ) {
				return;
			}

			$is_plugin_settings_view = rgget( 'page' ) === 'gf_settings' && rgget( 'subview' ) === 'gp-google-sheets';
			$is_form_settings_view   =
				rgget( 'page' ) === 'gf_edit_forms'
				&& rgget( 'view' ) === 'settings'
				&& rgget( 'subview' ) === 'gp-google-sheets'
				&& rgget( 'fid' ) === '0';

			// only display the warning on pages where you are able to oauth with Google.
			if ( ! $is_plugin_settings_view && ! $is_form_settings_view ) {
				return;
			}

			$markup = '<div class="notice notice-warning gf-notice" id="gpgs_site_served_over_http_notice"><p><strong>This site is served over HTTP which may result in browser warnings when attempting to authenticate with Google.</strong></p></div>';

			printf( $markup );
		}
	}

	GFFeedAddOn::register( 'GP_Google_Sheets' );

	function gp_google_sheets() {
		return GP_Google_Sheets::get_instance();
	}
}
