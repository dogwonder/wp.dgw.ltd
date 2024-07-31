import $ from 'jquery';

const strings = window.gpgs_settings_strings;

interface SheetSelectedResponse {
	success: boolean;
	data: {
		controlsHTML: string;
		spreadsheetLinkMarkup: string;
		message?: string;
		alertMessage?: string;
	};
}

function disableSettingsSection(selector: string) {
	$(selector)
		.addClass('gpgs-disabled-settings-section')
		.attr('inert', 'true');
}

function enableSettingsSection(selector: string) {
	$(selector)
		.removeClass('gpgs-disabled-settings-section')
		.removeAttr('inert');
}

export function disableColumnMappingSection() {
	disableSettingsSection('#gform-settings-section-column-mapping');
}

export function enableColumnMappingSection() {
	enableSettingsSection('#gform-settings-section-column-mapping');
}

export type ColumnMapRequestVariant =
	| 'from_current_feed_mappings'
	| 'from_form_fields'
	| 'from_sheet_columns';

export interface UpdateColumnMappingSectionArgs {
	sheetId: string;
	sheetName: string;
	sheetUrl: string;
}

export function updateColumnMappingSection(variant: 'from_form_fields'): void;
export function updateColumnMappingSection(
	variant: 'from_sheet_columns' | 'from_current_feed_mappings',
	args: UpdateColumnMappingSectionArgs
): void;

export async function updateColumnMappingSection(
	variant: ColumnMapRequestVariant,
	args?: UpdateColumnMappingSectionArgs
) {
	return new Promise<void>((resolve, reject) => {
		jQuery
			.get(strings.ajax_url, {
				action: 'gpgs_get_column_mappings_html',
				_ajax_nonce: strings.nonce,
				id: strings.form_id,
				feed_id: strings.feed_id,
				variant,
				...(args
					? {
							sheet_data: args.sheetId,
							sheet_url: args.sheetUrl,
							sheet_name: args.sheetName,
					  }
					: {}),
			})
			.done((response: SheetSelectedResponse) => {
				if (!response.success) {
					enableColumnMappingSection();
					reject(
						new Error(
							response.data.message ||
								'Failed to get column mappings.'
						)
					);
				}

				// Extract the <script block out of the controls
				const matches = response.data.controlsHTML.match(
					/<script type=\"text\/javascript\">(.+)<\/script>/
				);

				if (!matches?.length) {
					return resolve();
				}

				// Remove the script from the markup
				const controlsHTML = response.data.controlsHTML.replace(
					matches[0],
					''
				);

				$('#gform_setting_column_mapping').html(controlsHTML);

				enableColumnMappingSection();

				if (response.data.alertMessage) {
					// eslint-disable-next-line no-alert
					alert(response.data.alertMessage);
				}

				// Run the initialization script for the field map field
				try {
					// eslint-disable-next-line no-eval
					eval(matches[1]);
					resolve();
				} catch (e) {
					// eslint-disable-next-line no-console
					console.warn(e);
					reject(
						new Error(
							'Failed to initialize column mapping section.'
						)
					);
				}
			})
			.fail((jqXHR, textStatus, errorThrown) => {
				enableColumnMappingSection();
				const text = jqXHR.responseText || errorThrown;
				reject(
					new Error(
						`Column mapping request failed with status ${jqXHR.status}: ${text}`
					)
				);
			});
	});
}
