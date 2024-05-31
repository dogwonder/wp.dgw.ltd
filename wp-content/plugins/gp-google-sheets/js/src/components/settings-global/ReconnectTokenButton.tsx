import React from 'react';
import { __ } from '@wordpress/i18n';
import { usePluginSettingsStore } from '../../store/plugin-settings';

const ReconnectTokenButton = () => {
	const reconnectAccount = usePluginSettingsStore.use.reconnectAccount();

	const onClick = (e: React.MouseEvent<HTMLButtonElement>) => {
		reconnectAccount();
		e.preventDefault();
	};

	return (
		<button
			id="gpgs_reconnect_token"
			className="button button-secondary"
			onClick={onClick}
		>
			{__('Reconnect', 'gp-google-sheets')}
		</button>
	);
};

export default ReconnectTokenButton;
