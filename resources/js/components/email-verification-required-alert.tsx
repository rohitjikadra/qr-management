import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Link } from '@inertiajs/react';
import { MailWarning } from 'lucide-react';

export function EmailVerificationRequiredAlert() {
    return (
        <Alert variant="destructive">
            <MailWarning className="size-4" />
            <AlertTitle>Email verification required</AlertTitle>
            <AlertDescription>
                Please verify your email before creating QR codes. QR creation will be available after email verification.{' '}
                <Link href="/verify-email" className="font-medium underline">
                    Verify your email
                </Link>
            </AlertDescription>
        </Alert>
    );
}
