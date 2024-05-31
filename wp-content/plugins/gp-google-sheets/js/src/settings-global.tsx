import { createRoot, render } from '@wordpress/element';
import SettingsGlobal from './components/settings-global/SettingsGlobal';

// Spin up React
const el = document.getElementById('gform-settings');

if (typeof createRoot === 'function') {
	const root = createRoot(el!);
	root.render(<SettingsGlobal />);
} else {
	render(<SettingsGlobal />, el);
}
