import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Lock, Pause } from 'lucide-react';

interface Owner {
    id: number;
    name: string;
    email: string;
}

interface QrDetail {
    id: number;
    name: string;
    slug: string | null;
    type: string;
    type_label: string;
    content: Record<string, unknown>;
    destination_url: string | null;
    payload: string;
    redirect_url: string | null;
    is_dynamic: boolean;
    status: string;
    admin_locked: boolean;
    frozen: boolean;
    scan_count: number;
    last_scanned_at: string | null;
    expires_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
    owner: Owner | null;
}

interface Props {
    qr: QrDetail;
    canPause: boolean;
}

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium break-all sm:text-right">{value || '—'}</dd>
        </div>
    );
}

export default function AdminQrCodeShow({ qr, canPause }: Props) {
    const { flash } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'QR Codes', href: '/admin/qr-codes' },
        { title: qr.name, href: `/admin/qr-codes/${qr.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={qr.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/qr-codes">
                                <ArrowLeft className="size-4" /> Back
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-semibold">{qr.name}</h1>
                            <p className="text-muted-foreground text-sm">{qr.type_label}</p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Badge variant={qr.is_dynamic ? 'default' : 'outline'}>
                            {qr.is_dynamic ? 'Dynamic' : 'Static'}
                        </Badge>
                        <Badge variant={qr.status === 'paused' ? 'destructive' : 'outline'}>{qr.status}</Badge>
                        {qr.admin_locked && (
                            <Badge variant="destructive">
                                <Lock className="size-3" /> Admin locked
                            </Badge>
                        )}
                        {qr.frozen && <Badge variant="destructive">Frozen</Badge>}
                        {qr.deleted_at && <Badge variant="destructive">Deleted</Badge>}
                        {canPause && (
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="destructive" size="sm">
                                        <Pause className="size-4" /> Admin pause
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Pause and lock this QR?</DialogTitle>
                                        <DialogDescription>
                                            The owner will not be able to reactivate this QR code.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <Button
                                            variant="destructive"
                                            onClick={() => router.post(`/admin/qr-codes/${qr.id}/pause`)}
                                        >
                                            Confirm pause
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow label="Owner" value={qr.owner?.email} />
                                <DetailRow label="Slug" value={qr.slug} />
                                <DetailRow label="Scans" value={String(qr.scan_count)} />
                                <DetailRow label="Last scanned" value={qr.last_scanned_at} />
                                <DetailRow label="Created" value={qr.created_at} />
                                <DetailRow label="Updated" value={qr.updated_at} />
                                {qr.deleted_at && <DetailRow label="Deleted" value={qr.deleted_at} />}
                            </dl>
                            {qr.owner && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/users/${qr.owner.id}`}>View owner</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Destination & content</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow label="Destination URL" value={qr.destination_url} />
                                <DetailRow label="Redirect URL" value={qr.redirect_url} />
                            </dl>
                            <div>
                                <p className="text-muted-foreground mb-2 text-sm">Content JSON</p>
                                <pre className="bg-muted max-h-48 overflow-auto rounded-lg border p-3 text-xs">
                                    {JSON.stringify(qr.content, null, 2)}
                                </pre>
                            </div>
                            <div>
                                <p className="text-muted-foreground mb-2 text-sm">Encoded payload</p>
                                <pre className="bg-muted max-h-32 overflow-auto rounded-lg border p-3 text-xs break-all">
                                    {qr.payload}
                                </pre>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
