import { createRoot, render } from '@wordpress/element';
import GoogleSheetSettings from './components/settings-feed/GoogleSheetSettings';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

// Spin up React
const el = document.querySelector(
	'#gform-settings-section-google-sheets-settings .gform-settings-panel__content'
);

if (typeof createRoot === 'function') {
	const root = createRoot(el!);
	root.render(<GoogleSheetSettings />);
} else {
	render(<GoogleSheetSettings />, el);
}

// Filters
addFilter(
	'i18n.gettext',
	'gp-google-sheets/override-generic-map-column-labels',
	(translation: string, text: string, domain: string) => {
		if (text === 'Select a Field') {
			return __('Select a Column', 'gp-google-sheets');
		}

		if (text === 'Add Custom Key') {
			return __('Add New Column', 'gp-google-sheets');
		}

		return translation;
	}
);
