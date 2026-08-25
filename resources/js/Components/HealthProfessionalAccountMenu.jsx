import Dropdown from '@/Components/Dropdown';
import { usePage } from '@inertiajs/react';

/**
 * Avatar menu for logged-in health professionals (Profile + Log out).
 */
export default function HealthProfessionalAccountMenu({ professional = null }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const name = professional?.name || user?.name || 'Account';
    const imageUrl = professional?.image_url || null;
    const initial = (name.trim()[0] || '?').toUpperCase();
    const profileHref = professional?.id
        ? route('health-professionals.edit', professional.id)
        : route('profile.edit');

    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-full py-1 pl-1 pr-1 transition hover:bg-stone-100 sm:pr-2"
                    aria-label="Open account menu"
                >
                    <span className="hidden max-w-[9rem] truncate text-sm font-semibold text-stone-800 sm:inline">
                        {name}
                    </span>
                    {imageUrl ? (
                        <img
                            src={imageUrl}
                            alt=""
                            className="h-9 w-9 rounded-full object-cover ring-1 ring-stone-200"
                        />
                    ) : (
                        <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#5c4d3d] text-sm font-bold text-white ring-1 ring-[#5c4d3d]/20">
                            {initial}
                        </span>
                    )}
                </button>
            </Dropdown.Trigger>

            <Dropdown.Content
                align="right"
                contentClasses="py-1 bg-white rounded-xl overflow-hidden"
            >
                <Dropdown.Link href={profileHref}>Profile</Dropdown.Link>
                <Dropdown.Link href={route('logout')} method="post" as="button">
                    Log Out
                </Dropdown.Link>
            </Dropdown.Content>
        </Dropdown>
    );
}
