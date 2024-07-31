import { createRoot, render } from '@wordpress/element';
import GoogleSheetSettings from './components/settings-feed/GoogleSheetSettings';
import ColumnAutoMapping from './components/settings-feed/ColumnAutoMapping';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

function addGoogleSheetsSettings() {
	const el = document.querySelector(
		'#gform-settings-section-google-sheets-settings .gform-settings-panel__content'
	);

	if (typeof createRoot === 'function') {
		const root = createRoot(el!);
		root.render(<GoogleSheetSettings />);
	} else {
		render(<GoogleSheetSettings />, el);
	}
}

function addAutoMappingSection() {
	const feedId = new URLSearchParams(window.location.search).get('fid');
	if (feedId !== '0') {
		// only show this as an "onboarding" step for new feeds.
		return;
	}

	const mappingsSectionParent = document.querySelector(
		'#gform-settings-section-column-mapping .gform-settings-panel__content'
	);
	const autoMapReactRoot = document.createElement('div');
	autoMapReactRoot.id = 'gform_setting_column_auto_mapping_section_root';
	const mappingSection = document.getElementById(
		'gform_setting_column_mapping'
	);
	mappingsSectionParent!.insertBefore(autoMapReactRoot, mappingSection);
	const autoMapRoot = document.getElementById(
		'gform_setting_column_auto_mapping_section_root'
	);

	if (typeof createRoot === 'function') {
		const root = createRoot(autoMapRoot!);
		root.render(<ColumnAutoMapping />);
	} else {
		render(<ColumnAutoMapping />, autoMapRoot);
	}
}

// Spin up React
addGoogleSheetsSettings();
addAutoMappingSection();

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
