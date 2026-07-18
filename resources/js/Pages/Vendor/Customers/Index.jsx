import VendorLayout from '@/Layouts/VendorLayout';
import { Head } from '@inertiajs/react';

export default function VendorCustomersIndex({ shopName }) {
    return (
        <VendorLayout title="Customers">
            <Head title="Customers" />
            <h1 className="text-2xl font-bold text-stone-900">Customers</h1>
            <p className="mt-2 text-stone-600">
                Customer insights for {shopName} are coming soon.
            </p>
        </VendorLayout>
    );
}
