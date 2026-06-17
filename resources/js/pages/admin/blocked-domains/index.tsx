import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, ShieldBan } from 'lucide-react';
import { useState } from 'react';

interface DomainItem {
    id: number;
    domain: string;
    reason: string | null;
    created_at: string | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    domains: Pagination<DomainItem>;
    filters: { search?: string };
    totalDomains: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Blocked Domains', href: '/admin/blocked-domains' },
];

export default function AdminBlockedDomainsIndex({ domains, filters, totalDomains }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const applySearch = () => {
        const params: Record<string, string> = {};
        if (search.trim()) {
            params.search = search.trim();
        }
        router.get('/admin/blocked-domains', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Blocked Domains" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Blocked Domains</h1>
                        <p className="text-muted-foreground text-sm">
                            Showing {domains.total} of {totalDomains} blocked domains
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/blocked-domains/create">
                            <Plus className="size-4" /> Add domain
                        </Link>
                    </Button>
                </div>

                <form
                    className="max-w-md"
                    onSubmit={(e) => {
                        e.preventDefault();
                        applySearch();
                    }}
                >
                    <Input
                        placeholder="Search domain or reason..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                {domains.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <ShieldBan className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No blocked domains</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">Domain</th>
                                            <th className="px-4 py-3 font-medium">Reason</th>
                                            <th className="px-4 py-3 font-medium">Added</th>
                                            <th className="px-4 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {domains.data.map((domain) => (
                                            <tr key={domain.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium">{domain.domain}</td>
                                                <td className="px-4 py-3">{domain.reason || '—'}</td>
                                                <td className="text-muted-foreground px-4 py-3">{domain.created_at}</td>
                                                <td className="px-4 py-3">
                                                    <div className="flex gap-2">
                                                        <Button variant="outline" size="sm" asChild>
                                                            <Link href={`/admin/blocked-domains/${domain.id}/edit`}>
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => {
                                                                if (
                                                                    confirm(
                                                                        `Remove ${domain.domain} from blocklist?`,
                                                                    )
                                                                ) {
                                                                    router.delete(
                                                                        `/admin/blocked-domains/${domain.id}`,
                                                                    );
                                                                }
                                                            }}
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {domains.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {domains.links.map((link, i) =>
                            link.url ? (
                                <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                </Button>
                            ) : (
                                <Button
                                    key={i}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
