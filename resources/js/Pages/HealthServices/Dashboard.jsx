import { Link, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';

function SetupStep({ done, number, title, body }) {
    return (
        <li className="flex gap-3">
            <span
                className={`mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                    done ? 'bg-emerald-600 text-white' : 'bg-stone-200 text-stone-700'
                }`}
            >
                {done ? '✓' : number}
            </span>
            <div>
                <p className={`text-sm font-semibold ${done ? 'text-emerald-800' : 'text-stone-900'}`}>{title}</p>
                <p className="mt-0.5 text-sm text-stone-600">{body}</p>
            </div>
        </li>
    );
}

export default function HealthServicesDashboard({ professionals = [] }) {
    const { flash, auth } = usePage().props;
    const professional = professionals[0] ?? null;
    const needsSetup =
        professional && (professional.services_count === 0 || professional.availability_count === 0);
    const profileDone = Boolean(professional);
    const servicesDone = (professional?.services_count ?? 0) > 0;
    const availabilityDone = (professional?.availability_count ?? 0) > 0;
    const activeDone = Boolean(professional?.is_active);

    return (
        <>
            <SeoHead
                title="Professional Dashboard"
                description="Manage your healthcare profile, services, and availability."
                url={route('health-professionals.dashboard')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <div className="flex items-center gap-2">
                            <Link
                                href={route('health-services.index')}
                                className="rounded-full border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market"
                            >
                                Browse Health Services
                            </Link>
                        </div>
                    </div>
                </header>

                <div className="border-b border-stone-200/80 bg-white/95">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: route('home') },
                                { label: 'Health Services', href: route('health-services.index') },
                                { label: 'Your dashboard' },
                            ]}
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                            {flash.success}
                        </div>
                    )}

                    {!professional ? (
                        <div className="rounded-2xl border border-dashed border-stone-200 bg-white px-6 py-14 text-center">
                            <p className="text-lg font-semibold text-stone-900">No professional profile yet</p>
                            <p className="mt-2 text-sm text-stone-600">
                                Create your healthcare account to manage bookings.
                            </p>
                            <Link
                                href={route('health-professionals.signup')}
                                className="mt-5 inline-flex rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                            >
                                Register as a professional
                            </Link>
                        </div>
                    ) : needsSetup ? (
                        <div className="space-y-6">
                            <div>
                                <p className="text-sm font-medium text-stone-500">
                                    Signed in as {auth?.user?.email ?? professional.name}
                                </p>
                                <h1 className="mt-1 text-2xl font-bold text-stone-900 sm:text-3xl">
                                    Welcome, {professional.name.split(' ')[0]}
                                </h1>
                                <p className="mt-2 text-sm leading-relaxed text-stone-600 sm:text-base">
                                    Your account and profile are saved
                                    {professional.title ? (
                                        <>
                                            {' '}
                                            as <span className="font-semibold text-stone-800">{professional.title}</span>
                                            {professional.specialty ? (
                                                <>
                                                    {' '}
                                                    ({professional.specialty})
                                                </>
                                            ) : null}
                                        </>
                                    ) : null}
                                    . Patients can&apos;t book you yet because your profile is still{' '}
                                    <span className="font-semibold text-stone-800">Inactive</span>.
                                </p>
                            </div>

                            <section className="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 sm:p-6">
                                <h2 className="text-lg font-bold text-stone-900">Finish setup to go live</h2>
                                <p className="mt-1 text-sm text-stone-700">
                                    Complete these steps so families can see your rates and request appointments.
                                </p>

                                <ol className="mt-5 space-y-4">
                                    <SetupStep
                                        done={profileDone}
                                        number={1}
                                        title="Create your account & profile"
                                        body="Done — your login and professional details are saved."
                                    />
                                    <SetupStep
                                        done={(professional.visit_modes?.length ?? 0) > 0}
                                        number={2}
                                        title="Choose visit types"
                                        body="Say whether you offer Virtual, In person, or both."
                                    />
                                    <SetupStep
                                        done={servicesDone}
                                        number={3}
                                        title="Add services & rates"
                                        body="Tell patients what you offer (e.g. consultation) and the price."
                                    />
                                    <SetupStep
                                        done={availabilityDone}
                                        number={4}
                                        title="Set weekly availability"
                                        body="Choose the days and hours you accept bookings."
                                    />
                                    <SetupStep
                                        done={activeDone}
                                        number={5}
                                        title="Mark your profile active"
                                        body="Turn on visibility so you appear on the Health Services page."
                                    />
                                </ol>

                                <Link
                                    href={route('health-professionals.edit', professional.id)}
                                    className="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-[#5c4d3d] px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32] sm:w-auto"
                                >
                                    Continue setup
                                </Link>
                            </section>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            <div>
                                <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">Your dashboard</h1>
                                <p className="mt-1 text-sm text-stone-600">
                                    Manage your public profile, services, and weekly availability.
                                </p>
                            </div>

                            <article className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-bold text-stone-900">{professional.name}</h2>
                                        <p className="text-sm text-stone-600">{professional.title}</p>
                                        <p className="mt-1 text-xs text-stone-500">{professional.specialty}</p>
                                        {(professional.visit_modes?.length ?? 0) > 0 ? (
                                            <div className="mt-3 flex flex-wrap gap-1.5">
                                                {professional.visit_modes.map((mode) => (
                                                    <span
                                                        key={mode}
                                                        className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-100"
                                                    >
                                                        {mode}
                                                    </span>
                                                ))}
                                            </div>
                                        ) : null}
                                    </div>
                                    <span
                                        className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                            professional.is_active
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-stone-200 text-stone-700'
                                        }`}
                                    >
                                        {professional.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>

                                <dl className="mt-4 space-y-1 text-sm">
                                    <div className="flex items-center justify-between">
                                        <dt className="text-stone-500">Visit types</dt>
                                        <dd className="font-semibold text-stone-800">
                                            {(professional.visit_modes?.length ?? 0) > 0
                                                ? professional.visit_modes.join(' · ')
                                                : 'Not set'}
                                        </dd>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <dt className="text-stone-500">Services</dt>
                                        <dd className="font-semibold text-stone-800">{professional.services_count}</dd>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <dt className="text-stone-500">Availability windows</dt>
                                        <dd className="font-semibold text-stone-800">
                                            {professional.availability_count}
                                        </dd>
                                    </div>
                                </dl>

                                <div className="mt-5 flex flex-wrap gap-2">
                                    <Link
                                        href={route('health-professionals.edit', professional.id)}
                                        className="inline-flex rounded-lg bg-[#5c4d3d] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#4a3e32]"
                                    >
                                        Edit profile
                                    </Link>
                                    <Link
                                        href={route('health-services.show', professional.slug)}
                                        className="inline-flex rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market"
                                    >
                                        View public page
                                    </Link>
                                </div>
                            </article>
                        </div>
                    )}
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
