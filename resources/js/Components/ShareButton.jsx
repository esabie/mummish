import { useCallback, useState } from 'react';

function IconShare(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566 5.314m-9.566-5.314L15.75 4.5"
            />
        </svg>
    );
}

export default function ShareButton({ url, title, text, label = 'Share', copiedLabel = 'Link copied', className = '' }) {
    const [copied, setCopied] = useState(false);

    const share = useCallback(async () => {
        const shareText = text ?? title;

        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({ title, text: shareText, url });
                return;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
            }
        }

        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
            return;
        } catch {
            // Clipboard API unavailable — fall through to legacy copy.
        }

        const textarea = document.createElement('textarea');
        textarea.value = url;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } finally {
            document.body.removeChild(textarea);
        }
    }, [url, title, text]);

    return (
        <button
            type="button"
            onClick={share}
            className={`inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-sm font-semibold text-[#5c4d3d] shadow-sm transition hover:border-stone-300 hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-market/25 ${className}`}
            aria-label={copied ? copiedLabel : `Share ${title}`}
        >
            <IconShare className="h-4 w-4 shrink-0" aria-hidden />
            {copied ? copiedLabel : label}
        </button>
    );
}
