import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface LogDetail {
    id: number;
    action: string;
    entity_type: string | null;
    entity_id: number | null;
    meta_json: string;
    created_at: string | null;
    user: UserSummary | null;
}

interface Props {
    log: LogDetail;
}

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium break-all sm:text-right">{value || '—'}</dd>
        </div>
    );
}

export default function AdminAuditLogShow({ log }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Audit Logs', href: '/admin/audit-logs' },
        { title: log.action, href: `/admin/audit-logs/${log.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Audit: ${log.action}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/audit-logs">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">{log.action}</h1>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Event</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3">
                                <DetailRow label="Actor" value={log.user?.email} />
                                <DetailRow label="Entity type" value={log.entity_type} />
                                <DetailRow
                                    label="Entity ID"
                                    value={log.entity_id !== null ? String(log.entity_id) : null}
                                />
                                <DetailRow label="Created" value={log.created_at} />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Meta (JSON)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre className="bg-muted max-h-96 overflow-auto rounded-lg border p-3 text-xs">
                                {log.meta_json}
                            </pre>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
