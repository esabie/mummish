import { useEffect, useId, useMemo, useRef, useState } from 'react';

function formatCityLabel(city, region) {
    const cityLabel = String(city).toUpperCase();
    if (!region) {
        return cityLabel;
    }

    return `${cityLabel} (${String(region).toUpperCase()})`;
}

export default function SearchableCitySelect({
    id,
    value,
    onChange,
    cities = [],
    region = '',
    disabled = false,
    required = false,
    className = '',
    placeholder = 'Select city',
    searchPlaceholder = 'Search city...',
}) {
    const listId = useId();
    const containerRef = useRef(null);
    const searchRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    useEffect(() => {
        setSearch('');
        setOpen(false);
    }, [region]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (!containerRef.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [open]);

    useEffect(() => {
        if (open) {
            searchRef.current?.focus();
        }
    }, [open]);

    const filteredCities = useMemo(() => {
        const term = search.trim().toLowerCase();
        if (!term) {
            return cities;
        }

        return cities.filter((city) => city.toLowerCase().includes(term));
    }, [cities, search]);

    const isDisabled = disabled || !region || cities.length === 0;
    const displayValue = value ? formatCityLabel(value, region) : '';

    return (
        <div ref={containerRef} className="relative">
            <button
                id={id}
                type="button"
                disabled={isDisabled}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-controls={listId}
                onClick={() => {
                    if (!isDisabled) {
                        setOpen((current) => !current);
                    }
                }}
                className={`${className} flex w-full items-center justify-between text-left disabled:cursor-not-allowed disabled:bg-stone-50 disabled:text-stone-400`}
            >
                <span className={displayValue ? 'text-stone-900' : 'text-stone-400'}>
                    {!region ? 'Select a region first' : displayValue || placeholder}
                </span>
                <span className="ml-2 text-stone-400" aria-hidden>
                    ▾
                </span>
            </button>

            {open && (
                <div className="absolute z-30 mt-1.5 w-full overflow-hidden rounded-lg border border-stone-200 bg-white shadow-lg">
                    <div className="border-b border-stone-100 p-2">
                        <div className="relative">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400" aria-hidden>
                                ⌕
                            </span>
                            <input
                                ref={searchRef}
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder={searchPlaceholder}
                                className="w-full rounded-md border border-stone-200 py-2 pl-9 pr-3 text-sm text-stone-900 placeholder:text-stone-400 focus:border-market focus:outline-none focus:ring-1 focus:ring-market"
                            />
                        </div>
                    </div>

                    <ul id={listId} role="listbox" className="max-h-56 overflow-y-auto py-1">
                        {filteredCities.length === 0 ? (
                            <li className="px-3 py-2 text-sm text-stone-500">No cities match your search.</li>
                        ) : (
                            filteredCities.map((city) => {
                                const selected = city === value;

                                return (
                                    <li key={city} role="option" aria-selected={selected}>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                onChange(city);
                                                setOpen(false);
                                                setSearch('');
                                            }}
                                            className={`flex w-full px-3 py-2 text-left text-sm transition hover:bg-stone-100 ${
                                                selected ? 'bg-stone-100 font-semibold text-stone-900' : 'text-stone-700'
                                            }`}
                                        >
                                            {formatCityLabel(city, region)}
                                        </button>
                                    </li>
                                );
                            })
                        )}
                    </ul>
                </div>
            )}

            {required && (
                <input
                    tabIndex={-1}
                    className="sr-only"
                    value={value}
                    required
                    onChange={() => {}}
                    aria-hidden
                />
            )}
        </div>
    );
}
