import InputError from '@/Components/InputError';
import { useForm, usePage } from '@inertiajs/react';

export default function PaymentDetailsSection({ payoutDetails, ghanaBanks = [] }) {
    const { flash } = usePage().props;
    const locked = Boolean(payoutDetails?.payment_method);
    const { data, setData, put, processing, errors } = useForm({
        payment_method: payoutDetails?.payment_method ?? 'bank',
        bank_name: payoutDetails?.bank_name ?? '',
        bank_account_name: payoutDetails?.bank_account_name ?? '',
        bank_account_number: payoutDetails?.bank_account_number ?? '',
        mobile_money_provider: payoutDetails?.mobile_money_provider ?? '',
        mobile_money_name: payoutDetails?.mobile_money_name ?? '',
        mobile_money_number: payoutDetails?.mobile_money_number ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (locked) {
            return;
        }
        put(route('health-professionals.payment-details.update'), { preserveScroll: true });
    };

    const isBank = (locked ? payoutDetails.payment_method : data.payment_method) === 'bank';
    const bankOptions =
        data.bank_name && !ghanaBanks.includes(data.bank_name)
            ? [data.bank_name, ...ghanaBanks]
            : ghanaBanks;

    return (
        <section className="rounded-2xl border border-stone-200/90 bg-white p-4 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-lg font-bold text-stone-900">Payment details</h2>
                    <p className="mt-1 text-sm text-stone-600">
                        {locked
                            ? 'These details are locked after saving. Contact Mummish support if you need a change.'
                            : 'Save where Mummish should send your payouts after completed consultations.'}
                    </p>
                </div>
                {locked ? (
                    <span className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">
                        Saved: {payoutDetails.payment_method_label}
                    </span>
                ) : (
                    <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900">
                        Not saved yet
                    </span>
                )}
            </div>

            {flash?.success ? (
                <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {flash.success}
                </div>
            ) : null}

            {flash?.error ? (
                <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    {flash.error}
                </div>
            ) : null}

            {locked ? (
                <div className="mt-6 grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Payment method</p>
                        <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.payment_method_label}</p>
                    </div>
                    {isBank ? (
                        <>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Bank</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.bank_name}</p>
                            </div>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Account name</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.bank_account_name}</p>
                            </div>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Account number</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.bank_account_number}</p>
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Provider</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.mobile_money_provider}</p>
                            </div>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Account name</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.mobile_money_name}</p>
                            </div>
                            <div className="rounded-xl border border-stone-200 bg-stone-50/70 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Mobile money number</p>
                                <p className="mt-1 text-sm font-medium text-stone-900">{payoutDetails.mobile_money_number}</p>
                            </div>
                        </>
                    )}
                </div>
            ) : (
                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div>
                        <label htmlFor="payment_method" className="text-sm font-medium text-stone-700">
                            Preferred payment method
                        </label>
                        <select
                            id="payment_method"
                            value={data.payment_method}
                            onChange={(e) => setData('payment_method', e.target.value)}
                            className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                        >
                            <option value="bank">Bank</option>
                            <option value="mobile_money">Mobile money</option>
                        </select>
                        <InputError message={errors.payment_method} className="mt-1" />
                    </div>

                    {isBank ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="bank_name" className="text-sm font-medium text-stone-700">
                                    Bank name
                                </label>
                                <select
                                    id="bank_name"
                                    value={data.bank_name}
                                    onChange={(e) => setData('bank_name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                >
                                    <option value="">Select bank</option>
                                    {bankOptions.map((bank) => (
                                        <option key={bank} value={bank}>
                                            {bank}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.bank_name} className="mt-1" />
                            </div>
                            <div>
                                <label htmlFor="bank_account_name" className="text-sm font-medium text-stone-700">
                                    Account name
                                </label>
                                <input
                                    id="bank_account_name"
                                    type="text"
                                    value={data.bank_account_name}
                                    onChange={(e) => setData('bank_account_name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                />
                                <InputError message={errors.bank_account_name} className="mt-1" />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="bank_account_number" className="text-sm font-medium text-stone-700">
                                    Account number
                                </label>
                                <input
                                    id="bank_account_number"
                                    type="text"
                                    value={data.bank_account_number}
                                    onChange={(e) => setData('bank_account_number', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                />
                                <InputError message={errors.bank_account_number} className="mt-1" />
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="mobile_money_provider" className="text-sm font-medium text-stone-700">
                                    Mobile money provider
                                </label>
                                <select
                                    id="mobile_money_provider"
                                    value={data.mobile_money_provider}
                                    onChange={(e) => setData('mobile_money_provider', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                >
                                    <option value="">Select provider</option>
                                    <option value="MTN MoMo">MTN Mobile Money</option>
                                    <option value="Telecel Cash">Telecel Cash</option>
                                    <option value="AirtelTigo Money">AirtelTigo Money</option>
                                </select>
                                <InputError message={errors.mobile_money_provider} className="mt-1" />
                            </div>
                            <div>
                                <label htmlFor="mobile_money_name" className="text-sm font-medium text-stone-700">
                                    Account name
                                </label>
                                <input
                                    id="mobile_money_name"
                                    type="text"
                                    value={data.mobile_money_name}
                                    onChange={(e) => setData('mobile_money_name', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                />
                                <InputError message={errors.mobile_money_name} className="mt-1" />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="mobile_money_number" className="text-sm font-medium text-stone-700">
                                    Mobile money number
                                </label>
                                <input
                                    id="mobile_money_number"
                                    type="text"
                                    value={data.mobile_money_number}
                                    onChange={(e) => setData('mobile_money_number', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                />
                                <InputError message={errors.mobile_money_number} className="mt-1" />
                            </div>
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex w-full items-center justify-center rounded-lg bg-[#5c4d3d] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:py-2.5"
                    >
                        {processing ? 'Saving…' : 'Save payment details'}
                    </button>
                </form>
            )}
        </section>
    );
}
