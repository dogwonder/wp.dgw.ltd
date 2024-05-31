import { __ } from '@wordpress/i18n';
import { usePluginSettingsStore } from '../../store/plugin-settings';

const AddGoogleAccountButton = () => {
	const addGoogleAccount = usePluginSettingsStore.use.addGoogleAccount();
	const refetchTokens = usePluginSettingsStore.use.refetchTokens();
	const rescanIssues = usePluginSettingsStore.use.rescanIssues();

	const add = async (event: React.MouseEvent<HTMLButtonElement>) => {
		event.preventDefault();

		try {
			await addGoogleAccount();

			refetchTokens();
			rescanIssues();
		} catch (e: any) {
			// Do nothing, it will already throw an error through a notice.
		}
	};

	return (
		<button
			id="gpgs_add_account"
			className="button button-secondary"
			onClick={add}
		>
			{__('Add Google Account', 'gp-google-sheets')}
		</button>
	);
};

export default AddGoogleAccountButton;
