import Tooltip from '../Tooltip';
import { __ } from '@wordpress/i18n';

interface OptionGroup {
	text: string;
	options: Option[];
}

interface Option {
	text: string;
	value: string;
}

interface SettingSelectProps {
	label: string;
	name: string;
	value: string;
	required?: boolean;
	tooltip?: string;
	options: (OptionGroup | Option)[];
	placeholder?: string;
	disabled?: boolean;
	onChange: (value: string) => void;
}

const SettingSelect = ({
	name,
	label,
	value,
	required,
	tooltip,
	options,
	placeholder,
	disabled,
	onChange,
}: SettingSelectProps) => {
	return (
		<div
			id={`gform_setting_${name}`}
			className="gform-settings-field gform-settings-field__select"
		>
			<div className="gform-settings-field__header">
				<label className="gform-settings-label" htmlFor={name}>
					{label}
					{required && (
						<span className="required">({__('Required')})</span>
					)}
				</label>{' '}
				{tooltip && <Tooltip content={tooltip} />}
			</div>
			<span className="gform-settings-input__container">
				<select
					name={`_gform_setting_${name}`}
					required={required}
					id={name}
					value={value}
					disabled={disabled}
					onChange={(e) => onChange(e.target.value)}
				>
					{placeholder && (
						<option value="" disabled>
							{placeholder}
						</option>
					)}
					{options.map((option) => {
						if ('options' in option) {
							return (
								<optgroup label={option.text} key={option.text}>
									{option.options.map((opt) => (
										<option
											value={opt.value}
											key={opt.value}
										>
											{opt.text}
										</option>
									))}
								</optgroup>
							);
						}

						return (
							<option value={option.value} key={option.value}>
								{option.text}
							</option>
						);
					})}
				</select>
			</span>
		</div>
	);
};

export default SettingSelect;
