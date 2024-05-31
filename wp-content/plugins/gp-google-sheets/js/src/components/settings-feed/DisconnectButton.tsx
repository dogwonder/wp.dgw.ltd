import { __ } from '@wordpress/i18n';
import { useFeedSettingsStore } from '../../store/feed-settings';

const DisconnectButton = () => {
	const isDisconnecting = useFeedSettingsStore.use.isDisconnecting();
	const disconnect = useFeedSettingsStore.use.disconnectFeed();

	const onClick = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		disconnect();
	};

	return (
		<button
			id="disconnect"
			className="button add-new-h2"
			style={{ top: 0 }}
			onClick={onClick}
			disabled={isDisconnecting}
		>
			{__('Disconnect', 'gp-google-sheets')}
			{isDisconnecting && (
				<span className="spinner spinner_insert_test_row is-active"></span>
			)}
		</button>
	);
};

export default DisconnectButton;
