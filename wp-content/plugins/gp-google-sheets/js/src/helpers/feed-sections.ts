import $ from 'jquery';

const strings = window.gpgs_settings_strings;

interface SheetSelectedResponse {
	success: boolean;
	data: {
		controlsHTML: string;
		spreadsheetLinkMarkup: string;
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

export function updateColumnMappingSection({
	sheetId,
	sheetName,
	sheetUrl,
}: {
	sheetId: string;
	sheetName: string;
	sheetUrl: string;
}) {
	jQuery.get(
		strings.ajax_url,
		{
			action: 'gpgs_select_sheet',
			_ajax_nonce: strings.nonce,
			id: strings.form_id,
			feed_id: strings.feed_id,
			sheet_url: sheetUrl,
			sheet_id: sheetId,
			sheet_name: sheetName,
		},
		(response: SheetSelectedResponse) => {
			// Extract the <script block out of the controls
			const matches = response.data.controlsHTML.match(
				/<script type=\"text\/javascript\">(.+)<\/script>/
			);

			if (matches?.length) {
				// Remove the script from the markup
				const controlsHTML = response.data.controlsHTML.replace(
					matches[0],
					''
				);

				$('#gform_setting_column_mapping').html(controlsHTML);

				enableColumnMappingSection();

				// Run the initialization script for the field map field
				try {
					// eslint-disable-next-line no-eval
					eval(matches[1]);
				} catch (e) {
					// eslint-disable-next-line no-console
					console.warn(e);
				}
			}
		}
	);
}
