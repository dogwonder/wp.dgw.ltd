import { showPicker } from '../../helpers/oauth';
import { __ } from '@wordpress/i18n';

// eslint-disable-next-line import/no-unresolved
import type { OAuthResponseData } from '../../typings/global';
import { GoogleAccount } from '../../store/slices/google-accounts';

let strings = window.gpgs_settings_strings;

if (!strings) {
	strings = window.gpgs_settings_plugin_strings;
}

const AddSpreadsheetButton = ({
	doesNotHaveLicense,
	onSelection,
	onError,
	token,
}: {
	doesNotHaveLicense?: boolean;
	onSelection: (data: OAuthResponseData) => void;
	onError: (message: string) => void;
	token: GoogleAccount['token'];
}) => {
	const addSpreadsheet = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();

		showPicker({
			token: token as any,
			showErrorMessage: onError,
			onDataFound: onSelection,
			strings,
		});
	};

	return (
		<div
			style={{ marginTop: '1rem', display: 'flex', alignItems: 'center' }}
		>
			<button
				className="button"
				id="gp-google-sheets-add-spreadsheet"
				disabled={doesNotHaveLicense}
				onClick={addSpreadsheet}
				style={{ marginRight: '.5rem' }}
			>
				{__('Add Spreadsheet', 'gp-google-sheets')}
			</button>

			{doesNotHaveLicense && (
				<span
					style={{ alignSelf: 'center', marginLeft: '.5rem' }}
					dangerouslySetInnerHTML={{
						// translators: First placeholder is the plugin name, second is the error message
						__html: __(
							'Connecting to Google Sheets requires a valid <a href="https://gravitywiz.com/pricing" target="_blank">Gravity Perks license</a>.',
							'gp-google-sheets'
						),
					}}
				></span>
			)}
		</div>
	);
};

export default AddSpreadsheetButton;
