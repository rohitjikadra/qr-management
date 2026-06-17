import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { UserX } from 'lucide-react';

export function ImpersonationBanner() {
    const { impersonation, auth } = usePage<SharedData>().props;

    if (!impersonation) {
        return null;
    }

    return (
        <Alert className="rounded-none border-x-0 border-t-0">
            <AlertDescription className="flex flex-wrap items-center justify-between gap-2">
                <span>
                    Impersonating <strong>{auth.user?.email}</strong> (admin: {impersonation.admin_name})
                </span>
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => router.post('/impersonation/stop')}
                >
                    <UserX className="size-4" /> Stop impersonating
                </Button>
            </AlertDescription>
        </Alert>
    );
}
