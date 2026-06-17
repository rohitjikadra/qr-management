import { Head, Link } from '@inertiajs/react';
import { Ban } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function AccountSuspended() {
    return (
        <AuthLayout
            title="Account suspended"
            description="Your access to this platform has been restricted by an administrator."
        >
            <Head title="Account suspended" />

            <Alert variant="destructive">
                <Ban className="size-4" />
                <AlertTitle>Your account is suspended</AlertTitle>
                <AlertDescription>
                    You cannot log in or use the app until an administrator restores your account. If you think this
                    was a mistake, please contact support.
                </AlertDescription>
            </Alert>

            <Button asChild variant="outline" className="mt-6 w-full">
                <Link href={route('home')}>Back to home</Link>
            </Button>
        </AuthLayout>
    );
}
