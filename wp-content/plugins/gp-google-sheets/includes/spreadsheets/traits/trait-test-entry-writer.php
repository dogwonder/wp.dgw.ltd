<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use GFCommon;

trait Test_Entry_Writer {

	/**
	 * When a user clicks the Insert Test Row button, this method creates a junk
	 * entry that matches the form fields and contains garbage data.
	 */
	public function create_test_entry( $form, $fields ) {
		$entry = array();
		if ( is_array( $fields ) ) {
			foreach ( $fields as $id ) {
				$entry[ $id ] = self::create_test_value( $form, $id );
			}
		}
		$entry['id']               = -1;
		$entry['status']           = 'active';
		$entry['form_id']          = empty( $form['id'] ) ? -1 : $form['id'];
		$entry['ip']               = $_SERVER['REMOTE_ADDR'];
		$entry['currency']         = GFCommon::get_currency();
		$entry['post_id']          = '';
		$entry['date_created']     = wp_date( 'Y-m-d h:i:s' );
		$entry['date_updated']     = wp_date( 'Y-m-d h:i:s' );
		$entry['is_starred']       = 0;
		$entry['is_read']          = 0;
		$entry['user_agent']       = $_SERVER['HTTP_USER_AGENT'];
		$entry['payment_status']   = null;
		$entry['payment_date']     = null;
		$entry['payment_amount']   = null;
		$entry['payment_method']   = null;
		$entry['transaction_id']   = null;
		$entry['is_fulfilled']     = null;
		$entry['created_by']       = get_current_user_id();
		$entry['transaction_type'] = null;
		return $entry;
	}

	private function create_test_value( $form, $field_id ) {
		if ( empty( $form ) || ! is_array( $form['fields'] ) ) {
			return '';
		}

		//If $field_id contains a . like 21.1, we might not be providing a value
		$base_field_id = $field_id;
		$dot_position  = strpos( $field_id, '.' );

		$input_id = -1;

		if ( $dot_position !== false ) {
			$base_field_id = substr( $field_id, 0, $dot_position );
			$input_id      = substr( $field_id, $dot_position + 1 );
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field['id'] != $base_field_id ) {
				continue;
			}

			switch ( $field->get_input_type() ) {
				case 'address':
					switch ( $input_id ) {
						case '1':
							//street address
							return '3333 Coyote Hill Road';

						case '2':
							//street address line 2
							return '';

						case '3':
							//city
							return 'Palo Alto';

						case '4':
							//state
							return 'CA';

						case '5':
							//zip
							return '94304';

						case '6':
							//country
							return 'United States';
					}
					return;

				case 'checkbox':
					//only return a value for the first choice
					if ( substr( $field_id, -2 ) == '.1' ) {
						$inputs = $field->get_entry_inputs();
						if ( empty( $inputs ) ) {
							return '';
						}
						return $inputs[0]['label'];
					}
					return '';

				case 'consent':
					switch ( $input_id ) {
						case '1':
							return 'Checked';
						case '2':
							return $field->checkboxLabel;
						case '3':
							return $field->description;
					}
					return '';

				case 'date':
					return wp_date( 'Y-m-d' );

				case 'email':
					return get_option( 'admin_email' );

				case 'fileupload':
					if ( $field->multipleFiles ) {
						return wp_json_encode( array( admin_url( '/images/w-logo-blue.png' ) ) );
					} else {
						return admin_url( '/images/w-logo-blue.png' );
					}
				case 'post_image':
					return admin_url( '/images/w-logo-blue.png' );

				case 'hidden':
					return 'hidden';

				//ignore
				case 'html':
				case 'section':
					return '';

				case 'list':
					return 'item 1|item 2';

				case 'name':
					foreach ( $field->get_entry_inputs() as $input ) {
						if ( $field_id != $input['id'] || ! empty( $input['isHidden'] ) ) {
							continue;
						}

						return $input['label'];
					}
					return '';

				case 'number':
				case 'total':
					return rand();

				case 'phone':
					return '(000) 000-0000';

				case 'post_category':
					return 'Uncategorized:1';

				//text
				case 'post_content':
				case 'post_custom_field':
				case 'post_excerpt':
				case 'post_tags':
				case 'post_title':
				case 'text':
				case 'textarea':
					return __( 'This is a test.', 'gp-google-sheets' );

				case 'radio':
				case 'select':
					if ( empty( $field->choices ) ) {
						return '';
					}
					return $field->choices[0]['text'];

				case 'time':
					return wp_date( 'h:i a' ); //06:27 pm

				case 'website':
					return site_url();

				case 'uid':
					switch ( rgar( $field, 'gp-unique-id_type' ) ) {
						case 'alphanumeric':
							return 'abc123';
						case 'numeric':
							return '123456';
						case 'sequential':
							return '00001';
					}
			}

			return '';
		}
	}

}
