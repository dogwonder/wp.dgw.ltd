import SectionGoogleAccountsHealth from './SectionGoogleAccountsHealth';
import SectionGPPA from './SectionGPPA';
import Notices from '../Notices';
import { Fragment } from 'react';
import SectionIssues from './SectionIssues';
import { usePluginSettingsStore } from '../../store/plugin-settings';
import SectionDangerZone from './SectionDangerZone';

const SettingsGlobal = () => {
	const notices = usePluginSettingsStore.use.notices();

	return (
		<Fragment>
			<Notices notices={notices} />
			<SectionGoogleAccountsHealth />
			<SectionIssues />
			<SectionGPPA />
			<SectionDangerZone />
		</Fragment>
	);
};

export default SettingsGlobal;
