const SettingsHiddenInput = ({
	name,
	value,
}: {
	name: string;
	value: string;
}) => {
	return (
		<input
			type="hidden"
			name={`_gform_setting_${name}`}
			value={value}
			id={name}
		/>
	);
};

export default SettingsHiddenInput;
