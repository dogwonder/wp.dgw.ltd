import { __ } from '@wordpress/i18n';
import { usePluginSettingsStore } from '../../store/plugin-settings';

const DeleteAccountButton = ({ accountId }: { accountId: string }) => {
	const deleteGoogleAccount =
		usePluginSettingsStore.use.deleteGoogleAccount();

	const deleteAccount = async (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();

		if (
			// eslint-disable-next-line no-alert
			!confirm(
				__(
					'Are you sure you want to delete this Google Account?',
					'gp-google-sheets'
				)
			)
		) {
			return;
		}

		await deleteGoogleAccount(accountId);
	};

	return (
		<button
			id="gpgs_delete_account"
			className="button button-secondary button-danger"
			onClick={deleteAccount}
		>
			{__('Delete', 'gp-google-sheets')}
		</button>
	);
};

export default DeleteAccountButton;
