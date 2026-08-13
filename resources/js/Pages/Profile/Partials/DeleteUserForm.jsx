import { useRef, useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

export default function DeleteUserForm({ className = '', isVendor = false }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);
        reset();
    };

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Delete Account</h2>

                <p className="mt-1 text-sm text-gray-600">
                    Once your account is deleted, you will be signed out and will not be able to log in again.
                    {isVendor
                        ? ' All of your shop listings will be removed from the marketplace.'
                        : ''}
                </p>
            </header>

            <DangerButton onClick={confirmUserDeletion}>Delete Account</DangerButton>

            <Modal show={confirmingUserDeletion} onClose={closeModal} maxWidth="md">
                <form onSubmit={deleteUser} className="p-6 sm:p-8">
                    <div className="flex flex-col items-center text-center">
                        <ApplicationLogo className="h-14 w-auto sm:h-16" />

                        <h2 className="mt-5 text-xl font-semibold text-stone-900">
                            {isVendor ? 'We are sad to see you go!' : 'Are you sure you want to leave?'}
                        </h2>

                        <p className="mt-3 max-w-sm text-sm leading-relaxed text-stone-600">
                            {isVendor
                                ? 'Thank you for selling with Mummish. It has been a pleasure having your shop in our marketplace, and we are truly sorry to see you leave. If you delete your account, your shop listings will be removed and you will not be able to sign back in.'
                                : 'We’re sorry to see you go. Deleting your account cannot be undone, and you won’t be able to sign back in with this email.'}
                        </p>
                    </div>

                    <div className="mt-6">
                        <InputLabel htmlFor="password" value="Password" className="sr-only" />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="mt-1 block w-full"
                            isFocused
                            placeholder="Enter your password to confirm"
                        />

                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Keep my account
                        </SecondaryButton>

                        <DangerButton className="sm:ms-0" disabled={processing}>
                            Delete Account
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
