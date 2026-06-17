import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface PlanDetail {
    id: number;
    name: string;
    slug: string;
    price: number;
    currency: string;
    billing_cycle: string;
    razorpay_plan_id: string | null;
    limits: string;
    limits_original: string;
    is_active: boolean;
    sort_order: number;
}

interface Props {
    plan: PlanDetail;
}

export default function AdminPlanEdit({ plan }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Plans', href: '/admin/plans' },
        { title: plan.name, href: `/admin/plans/${plan.id}/edit` },
    ];

    const [confirmOpen, setConfirmOpen] = useState(false);

    const { data, setData, put, errors, processing } = useForm({
        name: plan.name,
        price: plan.price,
        razorpay_plan_id: plan.razorpay_plan_id ?? '',
        limits: plan.limits,
        is_active: plan.is_active,
        sort_order: plan.sort_order,
    });

    const limitsChanged = data.limits.trim() !== plan.limits_original.trim();

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (limitsChanged) {
            setConfirmOpen(true);
            return;
        }
        put(`/admin/plans/${plan.id}`);
    };

    const confirmSave = () => {
        setConfirmOpen(false);
        put(`/admin/plans/${plan.id}`);
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${plan.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/plans">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-semibold">Edit plan</h1>
                        <p className="text-muted-foreground text-sm">
                            {plan.slug} · {plan.billing_cycle}
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Plan settings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="price">Price (₹)</Label>
                                    <Input
                                        id="price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', Number(e.target.value))}
                                        required
                                    />
                                    <InputError message={errors.price} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">Sort order</Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min="0"
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', Number(e.target.value))}
                                        required
                                    />
                                    <InputError message={errors.sort_order} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="razorpay_plan_id">Razorpay Plan ID</Label>
                                <Input
                                    id="razorpay_plan_id"
                                    value={data.razorpay_plan_id}
                                    onChange={(e) => setData('razorpay_plan_id', e.target.value)}
                                />
                                <InputError message={errors.razorpay_plan_id} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked === true)}
                                />
                                <Label htmlFor="is_active">Plan is active</Label>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="limits">Plan limits (JSON)</Label>
                                <textarea
                                    id="limits"
                                    rows={14}
                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring font-mono flex min-h-[200px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    value={data.limits}
                                    onChange={(e) => setData('limits', e.target.value)}
                                />
                                <p className="text-muted-foreground text-xs">
                                    Use -1 for unlimited. Booleans: true/false.
                                </p>
                                <InputError message={errors.limits} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save plan
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Change plan limits?</DialogTitle>
                        <DialogDescription>
                            Changing limits affects what users on this plan can do. Existing subscriptions keep this
                            plan — review carefully before saving.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={confirmSave} disabled={processing}>
                            Yes, save limits
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
