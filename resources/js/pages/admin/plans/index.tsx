import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Package, Pencil } from 'lucide-react';

interface PlanItem {
    id: number;
    name: string;
    slug: string;
    price: number;
    currency: string;
    billing_cycle: string;
    is_active: boolean;
    sort_order: number;
    subscriptions_count: number;
}

interface Props {
    plans: PlanItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Plans', href: '/admin/plans' },
];

export default function AdminPlansIndex({ plans }: Props) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Plans" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div>
                    <h1 className="text-xl font-semibold">Plans</h1>
                    <p className="text-muted-foreground text-sm">Manage pricing and plan limits</p>
                </div>

                {plans.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <Package className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No plans found</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">Name</th>
                                            <th className="px-4 py-3 font-medium">Slug</th>
                                            <th className="px-4 py-3 font-medium">Price</th>
                                            <th className="px-4 py-3 font-medium">Cycle</th>
                                            <th className="px-4 py-3 font-medium">Active</th>
                                            <th className="px-4 py-3 font-medium">Subs</th>
                                            <th className="px-4 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {plans.map((plan) => (
                                            <tr key={plan.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium">{plan.name}</td>
                                                <td className="px-4 py-3">{plan.slug}</td>
                                                <td className="px-4 py-3">
                                                    ₹{plan.price.toLocaleString('en-IN')}
                                                </td>
                                                <td className="px-4 py-3">{plan.billing_cycle}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={plan.is_active ? 'default' : 'outline'}>
                                                        {plan.is_active ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">{plan.subscriptions_count}</td>
                                                <td className="px-4 py-3">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={`/admin/plans/${plan.id}/edit`}>
                                                            <Pencil className="size-3" /> Edit
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
