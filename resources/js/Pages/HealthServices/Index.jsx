import { Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';

function filterTodaySlots(slots) {
    const now = new Date(); // always fresh — called at render time
    return (slots ?? []).map((day) => {
        const isToday = day.date.toLowerCase() === 'today';
        const filteredTimes = isToday
            ? day.times.filter((t) => {
                  const [time, meridiem] = t.split(' ');
                  let [hours, minutes] = time.split(':').map(Number);
                  if (meridiem === 'PM' && hours !== 12) hours += 12;
                  if (meridiem === 'AM' && hours === 12) hours = 0;
                  const slotTime = new Date(now);
                  slotTime.setHours(hours, minutes, 0, 0);
                  return slotTime > now;
              })
            : day.times;
        return { ...day, times: filteredTimes };
    }).filter((day) => day.times.length > 0);
}

export default function HealthServicesIndex({ professionals = [] }) {
    const { flash } = usePage().props;
    const specialties = useMemo(() => {
        return [...new Set(professionals.map((p) => p.specialty).filter(Boolean))];
    }, [professionals]);

    return (
        <>
            <SeoHead
                title="Health Services"
                description="Book trusted healthcare professionals for your family on Mummish."
                url={route('health-services.index')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#f7f5f2] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <Link
                            href={route('home')}
                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:text-market hover:underline"
                        >
                            ← Back to home
                        </Link>
                        <LogoMark variant="shop" className="min-w-0 flex-1 justify-center" />
                        <Link
                            href={route('shop.index')}
                            className="shrink-0 rounded-full border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market"
                        >
                            Shop
                        </Link>
                    </div>
                </header>

                <div className="border-b border-stone-200/80 bg-white/95">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: route('home') },
                                { label: 'Health Services' },
                            ]}
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                    {flash?.success && (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                            {flash.success}
                        </div>
                    )}
                    <div className="overflow-hidden rounded-3xl bg-gradient-to-br from-[#d7f0ff] via-[#ecf8ff] to-[#f4fbff] p-6 sm:p-8 lg:p-10">
                        <p className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700 ring-1 ring-sky-100">
                            Health Services
                        </p>
                        <h1 className="mt-4 max-w-3xl text-3xl font-bold tracking-tight text-[#1f2937] sm:text-4xl lg:text-5xl">
                            Find the right healthcare professional for your family.
                        </h1>
                        <p className="mt-3 max-w-2xl text-sm leading-relaxed text-stone-600 sm:text-base">
                            Compare providers by specialty, rates, visit mode, and next available time. Open any provider to review details and request a booking.
                        </p>
                        <div className="mt-5 flex flex-wrap items-center gap-3">
                            <Link
                                href={route('health-professionals.signup')}
                                className="inline-flex rounded-full bg-[#5c4d3d] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#4a3e32]"
                            >
                                Join as a Healthcare Professional
                            </Link>
                            <Link
                                href={route('login')}
                                className="text-xs font-semibold text-stone-600 underline-offset-2 hover:text-market hover:underline"
                            >
                                Professional login
                            </Link>
                        </div>
                        <div className="mt-6 flex flex-wrap gap-2">
                            {specialties.map((specialty) => (
                                <span
                                    key={specialty}
                                    className="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200"
                                >
                                    {specialty}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="mb-5 mt-8 flex items-center justify-between gap-3 sm:mb-6">
                        <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">Meet Our Providers</h2>
                        <p className="text-sm text-stone-500">{professionals.length} available</p>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3">
                        {professionals.map((professional) => (
                            <article
                                key={professional.slug}
                                className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                            >
                                <Link href={route('health-services.show', professional.slug)} className="block">
                                    <div className="aspect-[4/3] bg-stone-100">
                                        <img
                                            src={professional.image}
                                            alt={professional.name}
                                            className="h-full w-full object-cover"
                                            loading="lazy"
                                        />
                                    </div>
                                    <div className="space-y-2 p-3 sm:space-y-3 sm:p-5">
                                        <div>
                                            <h2 className="text-sm font-bold text-stone-900 sm:text-lg">{professional.name}</h2>
                                            <p className="text-xs font-medium text-stone-600 sm:text-sm">{professional.title}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-1.5 sm:gap-2">
                                            <span className="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-100 sm:px-2.5 sm:py-1 sm:text-xs">
                                                {professional.specialty}
                                            </span>
                                            {professional.visit_modes?.map((mode) => (
                                                <span
                                                    key={`${professional.slug}-${mode}`}
                                                    className="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-semibold text-stone-700 sm:px-2.5 sm:py-1 sm:text-xs"
                                                >
                                                    {mode}
                                                </span>
                                            ))}
                                        </div>
                                        <dl className="space-y-1 text-xs sm:text-sm">
                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="text-stone-500">From</dt>
                                                <dd className="font-semibold text-stone-800">GH₵{professional.rate_value}</dd>
                                            </div>
                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="text-stone-500">Service</dt>
                                                <dd className="truncate text-right font-semibold text-market">{professional.service}</dd>
                                            </div>
                                            <div className="flex items-center justify-between gap-2">
                                                <dt className="shrink-0 text-stone-500">Next available</dt>
                                                <dd className="text-right text-stone-700">
                                                    {professional.next_available}
                                                </dd>
                                            </div>
                                        </dl>
                                        {(() => {
                                            const upcoming = filterTodaySlots(professional.slots);
                                            const firstDay = upcoming[0];
                                            if (!firstDay?.times?.length) return null;
                                            return (
                                                <div className="flex flex-wrap gap-1.5 sm:gap-2">
                                                    {firstDay.times.slice(0, 3).map((time) => (
                                                        <span
                                                            key={`${professional.slug}-${firstDay.date}-${time}`}
                                                            className="rounded-md border border-stone-200 px-1.5 py-0.5 text-[10px] font-medium text-stone-700 sm:px-2 sm:py-1 sm:text-xs"
                                                        >
                                                            {time}
                                                        </span>
                                                    ))}
                                                </div>
                                            );
                                        })()}
                                        <span className="inline-flex rounded-full bg-market-muted px-2.5 py-0.5 text-[10px] font-semibold text-market sm:px-3 sm:py-1 sm:text-xs">View profile</span>
                                    </div>
                                </Link>
                            </article>
                        ))}
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
