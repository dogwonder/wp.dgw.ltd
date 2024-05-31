import SettingsSection from '../SettingsSection';
import { __ } from '@wordpress/i18n';

const { gpgs_settings_plugin_strings: strings } = window;

const SectionDangerZone = () => {
	if (!strings.purge_action_scheduler_url || !strings.show_danger_zone) {
		return null;
	}

	const purge = function (
		this: HTMLLinkElement,
		e: React.MouseEvent<HTMLButtonElement, MouseEvent>
	) {
		e.preventDefault();

		// eslint-disable-next-line no-alert
		const confirmed = confirm(
			// eslint-disable-next-line @wordpress/i18n-no-collapsible-whitespace,
			__(
				'Are you sure you want to purge all uncomplete GP Google Sheets actions?\n\nIf entries are not already in Google Sheets, you will need to reprocess the feeds to sync these entries.',
				'gp-google-sheets'
			)
		);

		if (!confirmed) {
			return;
		}

		// Update gpgs_purge_action_timestamp in the URL with the current timestamp in seconds.
		const purgeUrl = strings.purge_action_scheduler_url.replace(
			/TIMESTAMP_PLACEHOLDER/,
			Math.floor(Date.now() / 1000).toString()
		);

		// Send user to the purge URL using JS
		// @ts-ignore
		window.location = purgeUrl;
	};

	return (
		<SettingsSection title="Danger Zone" id="danger-zone">
			<p style={{ marginTop: 0 }}>
				<strong style={{ color: 'red' }}>
					{__(
						"Danger Zone! Make sure you know what you're doing here.",
						'gp-google-sheets'
					)}
				</strong>
			</p>
			<p>
				<button onClick={purge} className="button button-secondary">
					{__('Purge Action Scheduler', 'gp-google-sheets')}
				</button>
			</p>
		</SettingsSection>
	);
};

export default SectionDangerZone;
