<?php
namespace GP_Google_Sheets\Spreadsheets;

defined( 'ABSPATH' ) or exit;

class Range_Parser {

	public static function end_column_index( $range, $column_count = PHP_INT_MAX ) {
		$range       = self::remove_sheet_name_from_range( $range );
		$second_half = '';
		$pieces      = explode( ':', $range );
		if ( sizeof( $pieces ) > 1 ) {
			$second_half = $pieces[1];
		}

		if ( empty( $second_half ) ) {
			//There is no second half of the range. Are there letters in the first?
			$just_numbers = preg_replace( '/[^0-9]/', '', $range );
			if ( $range == $just_numbers ) {
				//No, it's only numbers
				return $column_count;
			} else {
				return self::start_column_index( $range ) + 1;
			}
		}

		$just_letters = preg_replace( '/[^a-zA-Z]/', '', $second_half );
		if ( empty( $just_letters ) ) {
			//It's just a number
			return 0;
		}

		return 1 + self::letters_to_index( $just_letters );
	}

	public static function end_row_index( $range, $row_count = PHP_INT_MAX ) {
		$range       = self::remove_sheet_name_from_range( $range );
		$second_half = '';
		$pieces      = explode( ':', $range );
		if ( sizeof( $pieces ) > 1 ) {
			$second_half = $pieces[1];
		}

		if ( empty( $second_half ) ) {
			//There is no second half of the range. Is there a number in the first?
			$just_letters = preg_replace( '/[^a-zA-Z]/', '', $range );
			if ( $range == $just_letters ) {
				//It's only letters, so a whole column, the end row index is the size of the sheet
				return $row_count;
			} else {
				return self::start_row_index( $range ) + 1;
			}
		}

		if ( is_numeric( $second_half ) ) {
			//It's just a number;
			return ( (int) $second_half ) - 1;
		}

		return (int) preg_replace( '/[^0-9]/', '', $second_half );
	}

	/**
	 * This is an equivalent to JavaScript's charCodeAt().
	 */
	private static function char_code_at( $letter ) {
		$output    = '';
		$converted = iconv( 'UTF-8', 'UTF-16LE', $letter );
		for ( $i = 0; $i < strlen( $converted ); $i += 2 ) {
			$output .= ord( $converted[ $i ] ) + ( ord( $converted[ $i + 1 ] ) << 8 );
		}
		return $output;
	}

	public static function letters_to_index( $letters ) {
		$column = 0;
		$length = strlen( $letters );
		for ( $i = 0; $i < $length; $i++ ) {
			$column += (int) ( self::char_code_at( $letters[ $i ] ) - 64 ) * pow( 26, $length - $i - 1 );
		}
		return $column - 1;
	}

	public static function start_column_index( $range ) {
		$range      = self::remove_sheet_name_from_range( $range );
		$first_half = explode( ':', $range )[0];

		if ( is_numeric( $first_half ) ) {
			//It's just a number
			return 0;
		}

		return self::letters_to_index( preg_replace( '/[^A-Z]/', '', $first_half ) );
	}

	public static function start_row_index( $range ) {
		$range        = self::remove_sheet_name_from_range( $range );
		$first_half   = explode( ':', $range )[0];
		$just_numbers = preg_replace( '/[^0-9]/', '', $first_half );
		return ( (int) $just_numbers ) - 1;
	}

	/**
	 * Removes the sheet name from a range, if it exists.
	 *
	 * An example range with a sheet name: Sheet1!A1:B2
	 * An example range without a sheet name: A1:B2
	 */
	public static function remove_sheet_name_from_range( $range ) {
		$pieces = explode( '!', $range );
		if ( count( $pieces ) === 1 ) {
			return $range;
		}
		return $pieces[1];
	}
}
