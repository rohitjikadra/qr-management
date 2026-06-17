import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { useState } from 'react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface SubscriptionItem {
    id: number;
    user: UserSummary | null;
    plan_name: string | null;
    status: string;
    gateway: string;
    is_complimentary: boolean;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

interface Props {
    subscriptions: Pagination<SubscriptionItem>;
    filters: { search?: string; status?: string; gateway?: string };
    totalSubscriptions: number;
    statusOptions: { value: string; label: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Subscriptions', href: '/admin/subscriptions' },
];

const ALL = '__all';

export default function AdminSubscriptionsIndex({
    subscriptions,
    filters,
    totalSubscriptions,
    statusOptions,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string>) => {
        const params: Record<string, string> = {
            search,
            status: filters.status ?? '',
            gateway: filters.gateway ?? '',
            ...next,
        };
        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === ALL) delete params[key];
        });
        router.get('/admin/subscriptions', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscriptions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Subscriptions</h1>
                    <p className="text-muted-foreground text-sm">
                        Showing {subscriptions.total} of {totalSubscriptions} subscriptions
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <form
                        className="min-w-48 flex-1"
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters({});
                        }}
                    >
                        <Input
                            placeholder="Search user, plan, or gateway ID..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
                    <Select value={filters.status ?? ALL} onValueChange={(v) => applyFilters({ status: v })}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All status</SelectItem>
                            {statusOptions.map((opt) => (
                                <SelectItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filters.gateway ?? ALL} onValueChange={(v) => applyFilters({ gateway: v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Gateway" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All gateways</SelectItem>
                            <SelectItem value="razorpay">Razorpay</SelectItem>
                            <SelectItem value="manual">Manual</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {subscriptions.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <CreditCard className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No subscriptions found</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">User</th>
                                            <th className="px-4 py-3 font-medium">Plan</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Gateway</th>
                                            <th className="px-4 py-3 font-medium">Expires</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {subscriptions.data.map((sub) => (
                                            <tr key={sub.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/subscriptions/${sub.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {sub.user?.email ?? '—'}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">{sub.plan_name ?? '—'}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">{sub.status}</Badge>
                                                    {sub.is_complimentary && (
                                                        <Badge variant="secondary" className="ml-1">
                                                            free
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">{sub.gateway}</td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {sub.expires_at ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {subscriptions.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {subscriptions.links.map((link, i) =>
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
