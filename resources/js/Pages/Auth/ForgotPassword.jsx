import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout
            breadcrumbs={[
                { label: 'Home', href: '/' },
                { label: 'Log in', href: route('login') },
                { label: 'Forgot password' },
            ]}
        >
            <Head title="Forgot Password" />

            <div className="mb-4 text-sm text-gray-600">
                Forgot your password? No problem. Enter the email address for your seller account and we will text a
                password reset link to the phone number on your seller profile.
            </div>

            {status && <div className="mb-4 font-medium text-sm text-green-600">{status}</div>}

            <form onSubmit={submit}>
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="flex items-center justify-end mt-4">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Text Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
