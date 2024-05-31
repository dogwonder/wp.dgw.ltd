import SettingsSection from '../SettingsSection';
import { __ } from '@wordpress/i18n';

const { gpgs_settings_plugin_strings: strings } = window;

const SectionGPPA = () => {
	if (
		(!strings.gppa_installed && !strings.show_gppa_integration) ||
		strings.gppa_activated
	) {
		return null;
	}

	let action;

	if (!strings.gppa_installed) {
		if (strings.has_available_perks) {
			action = (
				<a
					className="button gpgs-manage-perk"
					style={{ marginRight: '0.5rem' }}
					href={strings.install_gppa_url}
				>
					{__('Install Populate Anything', 'gp-google-sheets')}
				</a>
			);
		} else {
			action = (
				<a
					className="button gpgs-manage-perk"
					style={{ marginRight: '0.5rem' }}
					href={strings.upgrade_license_url}
				>
					{__('Upgrade License', 'gp-google-sheets')}
				</a>
			);
		}
	} else {
		action = (
			<a
				className="button gpgs-manage-perk"
				style={{ marginRight: '0.5rem' }}
				href={strings.activate_gppa_url}
			>
				{__('Activate Populate Anything', 'gp-google-sheets')}
			</a>
		);
	}

	return (
		<SettingsSection title="GP Populate Anything" id="gppa-integration">
			<p style={{ marginTop: 0 }}>
				<strong>
					{__(
						'Want to populate data from Google Sheets into your forms?',
						'gp-google-sheets'
					)}
				</strong>
			</p>

			<p>
				{__(
					'Populate Anything pulls data directly from Google Sheets into Gravity Forms field choices that users can select, or, as values — for calculations, conditional logic, or as defaults. Filter Google Sheets data live on your form based on conditions you set, or, dynamically with user input. Basically, your favorite Populate Anything features, but with data from Google Sheets.',
					'gp-google-sheets'
				)}
			</p>

			<div
				style={{
					marginTop: '1rem',
					display: 'flex',
					alignItems: 'center',
				}}
			>
				{action}
			</div>
		</SettingsSection>
	);
};

export default SectionGPPA;
