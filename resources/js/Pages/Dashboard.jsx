import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ auth, vendorApplication, listingLimit }) {
    const { flash } = usePage().props;
    const vendorSubmitted = Boolean(flash?.vendorApplicationSubmitted);

    return (
        <AuthenticatedLayout
            user={auth.user}
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Dashboard' },
            ]}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {vendorSubmitted && (
                        <div
                            className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                            role="status"
                        >
                            Your vendor application was submitted. You&apos;re logged in — we&apos;ll review your shop
                            and text you at the phone number you provided with next steps.
                        </div>
                    )}

                    {vendorApplication && (
                        <div className="mb-6 rounded-lg border border-stone-200 bg-white px-4 py-4 shadow-sm">
                            <h3 className="text-sm font-semibold text-stone-900">Seller application</h3>
                            <p className="mt-1 text-sm text-stone-600">
                                <span className="font-medium">{vendorApplication.shop_name}</span>
                                {' · '}
                                Status:{' '}
                                <span className="font-semibold text-stone-800">{vendorApplication.status_label}</span>
                            </p>
                            {vendorApplication.status === 'pending' && listingLimit?.max != null && (
                                <p className="mt-2 text-sm text-amber-900">
                                    While pending approval you can list up to {listingLimit.max} products (
                                    {listingLimit.current} used
                                    {listingLimit.remaining != null ? `, ${listingLimit.remaining} remaining` : ''}).
                                </p>
                            )}
                            {vendorApplication.status === 'approved' && (
                                <p className="mt-2 text-sm text-emerald-800">
                                    Your shop is approved. You can list unlimited products once product listing is
                                    available.
                                </p>
                            )}
                            {vendorApplication.status === 'rejected' && vendorApplication.rejection_reason && (
                                <p className="mt-2 text-sm text-red-800">
                                    <span className="font-medium">Reason:</span> {vendorApplication.rejection_reason}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">You&apos;re logged in!</div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
