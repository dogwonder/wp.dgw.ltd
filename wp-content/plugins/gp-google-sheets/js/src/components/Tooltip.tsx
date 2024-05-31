import { useEffect } from 'react';

interface TooltipProps {
	content: string;
}

const Tooltip: React.FC<TooltipProps> = ({ content }) => {
	// Run useEffect one time to run gform_initialize_tooltips() after the component mounts
	useEffect(() => {
		window.gform_initialize_tooltips();
	}, []);

	const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
	};

	const handleKeyPress = (e: React.KeyboardEvent<HTMLButtonElement>) => {
		e.preventDefault();
	};

	return (
		<button
			onClick={handleClick}
			onKeyPress={handleKeyPress}
			className="gf_tooltip tooltip "
			aria-label={content}
		>
			<i
				className="gform-icon gform-icon--question-mark"
				aria-hidden="true"
			></i>
		</button>
	);
};

export default Tooltip;
