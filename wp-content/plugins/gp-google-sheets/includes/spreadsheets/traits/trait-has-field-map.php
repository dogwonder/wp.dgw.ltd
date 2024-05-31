<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Spreadsheets\Range_Parser;
trait Has_Field_Map {

	public function get_column_mapping() {
		return rgars( $this->get_feed(), 'meta/column_mapping' );
	}

	/**
	 * @return bool
	 */
	public function field_map_is_empty() {
		$field_map_meta = $this->get_column_mapping();

		return empty( $field_map_meta )
			|| ( sizeof( $field_map_meta ) == 1
			&& ( ( empty( $field_map_meta[0]['value'] )
			&& empty( $field_map_meta[0]['custom_value'] ) ) ) );
	}

	public function field_map_key_field_choices( $first_row_data = array() ) {
		if ( empty( $first_row_data ) ) {
			$first_row_data = $this->get_first_row();
		}

		if ( empty( $first_row_data ) || empty( $first_row_data[0] ) ) {
			return array();
		}

		$choices = array();

		for ( $c = 0; $c < sizeof( $first_row_data[0] ); $c++ ) {
			$letters   = gpgs_number_to_column_letters( $c + 1 );
			$choices[] = array(
				'label' => sprintf(
					'%s. %s',
					$letters,
					$first_row_data[0][ $c ]
				),
				'value' => $letters,
			);
		}

		return $choices;
	}

	/**
	 * flatten_column_mapping
	 *
	 * Takes the setting value of $feed['meta']['column_mapping'] as GF
	 * stores it and returns a one-dimensional array of strings where keys
	 * are keys and values are values. Not useful before field map changes
	 * are saved because 'gf_custom' is the key and this method does not
	 * know where that column will be added in the sheet.
	 *
	 * @return array
	 */
	public function flatten_column_mapping() {
		if ( empty( $this->get_column_mapping() ) || $this->field_map_is_empty() ) {
			return array();
		}

		$meta_column_mapping = $this->get_column_mapping();

		$letter_keyed_map     = array();
		$highest_column_index = 0;
		foreach ( $meta_column_mapping as $column ) {
			//Ack, this column isn't mapped yet
			if ( $column['key'] == 'gf_custom' ) {
				$column['key'] = '?';
				continue;
			}

			$letters                      = $column['key'];
			$letter_keyed_map[ $letters ] = $column['custom_value'] ? 'gf_custom:' . $column['custom_value'] : $column['value'];
			$column_index                 = Range_Parser::letters_to_index( $letters );
			$highest_column_index         = max( $highest_column_index, $column_index );
		}

		$map = array();
		for ( $i = 0; $i <= $highest_column_index; $i++ ) {
			$letters   = gpgs_number_to_column_letters( $i + 1 );
			$map[ $i ] = empty( $letter_keyed_map[ $letters ] ) ? '' : $letter_keyed_map[ $letters ];
		}

		return $map;
	}

}
