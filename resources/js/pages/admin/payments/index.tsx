import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Banknote } from 'lucide-react';
import { useState } from 'react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface PaymentItem {
    id: number;
    user: UserSummary | null;
    invoice_number: string | null;
    amount: number;
    currency: string;
    status: string;
    gateway: string;
    paid_at: string | null;
    created_at: string | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

interface Props {
    payments: Pagination<PaymentItem>;
    filters: { search?: string; status?: string };
    totalPayments: number;
    statusOptions: { value: string; label: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Payments', href: '/admin/payments' },
];

const ALL = '__all';

function formatAmount(amount: number, currency: string): string {
    if (currency === 'INR') return `₹${amount.toLocaleString('en-IN')}`;
    return `${currency} ${amount}`;
}

export default function AdminPaymentsIndex({ payments, filters, totalPayments, statusOptions }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string>) => {
        const params: Record<string, string> = { search, status: filters.status ?? '', ...next };
        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === ALL) delete params[key];
        });
        router.get('/admin/payments', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Payments" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Payments</h1>
                        <p className="text-muted-foreground text-sm">
                            Showing {payments.total} of {totalPayments} payments
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href="/admin/payments/export/csv">Export CSV</a>
                    </Button>
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
                            placeholder="Search invoice, gateway ID, or user..."
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
                </div>

                {payments.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <Banknote className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No payments found</p>
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
                                            <th className="px-4 py-3 font-medium">Invoice</th>
                                            <th className="px-4 py-3 font-medium">Amount</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payments.data.map((payment) => (
                                            <tr key={payment.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/payments/${payment.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {payment.user?.email ?? '—'}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {payment.invoice_number ?? `#${payment.id}`}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {formatAmount(payment.amount, payment.currency)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">{payment.status}</Badge>
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {payment.paid_at ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {payments.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {payments.links.map((link, i) =>
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
