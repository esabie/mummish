const LOGO_SRC = '/images/logo.png?v=3';

export default function ApplicationLogo({ className = 'h-16 w-auto', ...props }) {
    return (
        <img
            src={LOGO_SRC}
            alt="Mummish"
            className={`object-contain ${className}`}
            {...props}
        />
    );
}
