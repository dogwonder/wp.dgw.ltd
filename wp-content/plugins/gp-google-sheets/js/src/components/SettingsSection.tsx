type Props = {
	children: React.ReactNode;
	title: string;
	id: string;
};

const SettingsSection = ({ children, title, id }: Props) => {
	return (
		<fieldset
			id={`gform-settings-section-${id}`}
			className="gform-settings-panel gform-settings-panel--with-title"
		>
			<legend className="gform-settings-panel__title gform-settings-panel__title--header">
				{title}
			</legend>
			<div className="gform-settings-panel__content">{children}</div>
		</fieldset>
	);
};

export default SettingsSection;
