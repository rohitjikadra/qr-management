import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { QR_TYPES } from '@/lib/qr';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, QrCode as QrCodeIcon, ScanLine } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';

interface QrListItem {
    id: number;
    name: string;
    type: string;
    type_label: string;
    is_dynamic: boolean;
    status: 'active' | 'paused';
    scan_count: number;
    payload: string;
    created_at: string;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

interface Props {
    qrCodes: Pagination<QrListItem>;
    filters: { search?: string; type?: string; status?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'QR Codes', href: '/qr' }];

const ALL = '__all';

export default function QrIndex({ qrCodes, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string>) => {
        const params: Record<string, string> = {
            search,
            type: filters.type ?? '',
            status: filters.status ?? '',
            ...next,
        };
        Object.keys(params).forEach((k) => (params[k] === '' || params[k] === ALL) && delete params[k]);
        router.get('/qr', params, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="QR Codes" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">My QR Codes</h1>
                    <Button asChild>
                        <Link href="/qr/create">
                            <Plus className="size-4" /> Create QR
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    <form
                        className="flex-1 min-w-48"
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters({});
                        }}
                    >
                        <Input placeholder="Search by name..." value={search} onChange={(e) => setSearch(e.target.value)} />
                    </form>
                    <Select value={filters.type ?? ALL} onValueChange={(v) => applyFilters({ type: v })}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All types</SelectItem>
                            {QR_TYPES.map((t) => (
                                <SelectItem key={t.id} value={t.id}>
                                    {t.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ALL} onValueChange={(v) => applyFilters({ status: v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="paused">Paused</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {qrCodes.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <QrCodeIcon className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No QR codes yet</p>
                            <p className="text-muted-foreground text-sm">Create your first QR code and start sharing.</p>
                            <Button asChild>
                                <Link href="/qr/create">
                                    <Plus className="size-4" /> Create your first QR
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {qrCodes.data.map((qr) => (
                            <Link key={qr.id} href={`/qr/${qr.id}`}>
                                <Card className="hover:border-primary/50 h-full transition-colors">
                                    <CardContent className="flex gap-3 p-4">
                                        <div className="rounded-md border bg-white p-1.5">
                                            <QRCodeSVG value={qr.payload || ' '} size={72} />
                                        </div>
                                        <div className="flex min-w-0 flex-col gap-1">
                                            <p className="truncate font-medium">{qr.name}</p>
                                            <div className="flex flex-wrap gap-1">
                                                <Badge variant="secondary">{qr.type_label}</Badge>
                                                <Badge variant={qr.is_dynamic ? 'default' : 'outline'}>
                                                    {qr.is_dynamic ? 'Dynamic' : 'Static'}
                                                </Badge>
                                                {qr.status === 'paused' && <Badge variant="destructive">Paused</Badge>}
                                            </div>
                                            {qr.is_dynamic && (
                                                <p className="text-muted-foreground flex items-center gap-1 text-xs">
                                                    <ScanLine className="size-3" /> {qr.scan_count} scans
                                                </p>
                                            )}
                                            <p className="text-muted-foreground text-xs">{qr.created_at}</p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}

                {qrCodes.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {qrCodes.links.map((link, i) =>
                            link.url ? (
                                <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                </Button>
                            ) : (
                                <Button key={i} variant="outline" size="sm" disabled dangerouslySetInnerHTML={{ __html: link.label }} />
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
