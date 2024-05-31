<?php
defined( 'ABSPATH' ) or exit;

// Polyfill for https://www.php.net/manual/en/function.array-key-first.php
function gpgs_array_key_first( $array ) {
	if ( ! is_array( $array ) || empty( $array ) ) {
		return null;
	}

	foreach ( $array as $key => $unused ) {
		return $key;
	}
}

// Polyfill for https://www.php.net/manual/en/function.array-key-last.php
function gpgs_array_key_last( $array ) {
	if ( ! is_array( $array ) || empty( $array ) ) {
		return null;
	}

	return array_keys( $array )[ count( $array ) - 1 ];
}

function gpgs_fill_missing_array_keys( $arr, $starting_key = 0, $fill_value = '' ) {
	//Fill missing keys in the field map array, those are user columns
	for ( $c = $starting_key; $c <= max( array_keys( $arr ) ); $c++ ) {
		if ( ! isset( $arr[ $c ] ) ) {
			$arr[ $c ] = $fill_value;
		}
	}

	//Sort by key to put the values we just set in the correct order
	ksort( $arr );
	return $arr;
}


/**
 * Build a link to a specific sheet in a spreadsheet.
 *
 * @param string $spreadsheet_id The ID of the spreadsheet.
 * @param string $sheet_id The ID of the sheet.
 *
 * @return string|null The link to the sheet. Null if no spreadsheet ID was provided.
 */
function gpgs_build_sheet_url( $spreadsheet_id, $sheet_id = null ) {
	if ( ! $spreadsheet_id ) {
		return null;
	}

	$url = sprintf(
		'https://docs.google.com/spreadsheets/d/%s/edit',
		$spreadsheet_id
	);

	if ( ! rgblank( $sheet_id ) ) {
		$url .= '#gid=' . $sheet_id;
	}

	return $url;
}

function gpgs_has_leading_zero( $number ) {
	// If we're not working with a string/number, return false.
	// If the number is just zero, then we want to still treat it as a number so return false here, too.
	if ( ! is_scalar( $number ) || $number == '0' ) {
		return false;
	}

	return GFCommon::safe_substr( $number, 0, 1 ) === '0';
}

/**
 * Gets a spreadsheet ID from a URL.
 *
 * @param string $url URL to a spreadsheet.
 *
 * @return string|null
 */
function gpgs_get_spreadsheet_id_from_url( $url ) {
	// Example: https://docs.google.com/spreadsheets/d/1K_rFhe9i6XIvIXnfE8L4rzt4ivpBhOcPkigDmYTQ1rQ/edit#gid=0
	$url_pieces = explode( '/', $url );

	if ( sizeof( $url_pieces ) >= 6 ) {
		return $url_pieces[5];
	}

	return null;
}

/**
 * Gets a spreadsheet ID from a feed.
 *
 * @param array $feed GPGS feed.
 *
 * @return string|null
 */
function gpgs_get_spreadsheet_id_from_feed( $feed ) {
	$google_sheet_url = rgars( $feed, 'meta/google_sheet_url' );

	if ( ! $google_sheet_url ) {
		return null;
	}

	return gpgs_get_spreadsheet_id_from_url( $google_sheet_url );
}


/**
 * Converts a number to an equivalent Google Sheet column letter name.
 * Passing 1 returns A, 27 returns AA, and 53 returns BA.
 */
function gpgs_number_to_column_letters( $number ) {
	$temp   = 0;
	$letter = '';

	while ( $number > 0 ) {
		$temp   = ( $number - 1 ) % 26;
		$letter = chr( $temp + 65 ) . $letter;
		$number = ( $number - $temp - 1 ) / 26;
	}

	return $letter;
}
