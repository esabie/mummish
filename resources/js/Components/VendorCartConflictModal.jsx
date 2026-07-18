import Modal from '@/Components/Modal';
import { useCart } from '@/context/CartContext';

export default function VendorCartConflictModal() {
    const { vendorConflict, confirmVendorSwitch, cancelVendorSwitch } = useCart();

    const show = Boolean(vendorConflict);
    const currentName = vendorConflict?.currentVendor?.name ?? 'another seller';
    const newName = vendorConflict?.newVendor?.name ?? 'this seller';

    return (
        <Modal show={show} onClose={cancelVendorSwitch} maxWidth="md">
            <div className="px-6 py-6">
                <h2 className="text-lg font-bold text-stone-900">Switch seller?</h2>
                <p className="mt-3 text-sm leading-relaxed text-stone-600">
                    Your cart contains items from <span className="font-semibold text-stone-900">{currentName}</span>.
                    You can only checkout from one seller at a time.
                </p>
                <p className="mt-2 text-sm leading-relaxed text-stone-600">
                    Clear your cart and add items from <span className="font-semibold text-stone-900">{newName}</span> instead?
                </p>
                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onClick={cancelVendorSwitch}
                        className="rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
                    >
                        Keep current cart
                    </button>
                    <button
                        type="button"
                        onClick={confirmVendorSwitch}
                        className="rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                    >
                        Clear cart &amp; switch
                    </button>
                </div>
            </div>
        </Modal>
    );
}
