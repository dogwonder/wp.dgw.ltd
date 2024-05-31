import Tooltip from '../Tooltip';
import { __ } from '@wordpress/i18n';

interface SettingRadioProps {
	label: string;
	name: string;
	value?: string;
	required?: boolean;
	tooltip?: string;
	choices: { text: string; value: string }[];
	onChange?: (value: string) => void;
}

const SettingRadio = ({
	name,
	label,
	value,
	required,
	tooltip,
	onChange,
	choices,
}: SettingRadioProps) => {
	return (
		<div
			id={`gform_setting_${name}`}
			className="gform-settings-field gform-settings-field__radio"
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
				{choices.map((choice, index) => (
					<div
						key={choice.value}
						id={`gform-settings-radio-choice-${name}${index}`}
						className="gform-settings-choice gform-settings-choice--inline"
					>
						<input
							type="radio"
							name={`_gform_setting_${name}`}
							value={choice.value}
							checked={choice.value === value}
							onChange={
								onChange
									? (event) => onChange(event.target.value)
									: undefined
							}
							id={`${name}${index}`}
						/>
						<label htmlFor={`${name}${index}`}>
							<span>
								{' '}
								<span className="gform-settings-choice-label">
									{choice.text}
								</span>{' '}
							</span>
						</label>
					</div>
				))}
			</span>
		</div>
	);
};

export default SettingRadio;
