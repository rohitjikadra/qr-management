import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavGroup, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    CreditCard,
    Flag,
    Globe,
    LayoutGrid,
    Package,
    QrCode,
    ScrollText,
    Settings,
    ShieldBan,
    ShieldCheck,
    ToggleLeft,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';

function buildNavGroups(isSuperAdmin: boolean): NavGroup[] {
    const managementItems = [
        { title: 'Users', url: '/admin/users', icon: Users },
        { title: 'QR Codes', url: '/admin/qr-codes', icon: QrCode },
    ];

    if (isSuperAdmin) {
        managementItems.push({ title: 'Admin Team', url: '/admin/team', icon: ShieldCheck });
    }

    const billingItems = [
        { title: 'Plans', url: '/admin/plans', icon: Package },
        { title: 'Subscriptions', url: '/admin/subscriptions', icon: CreditCard },
        { title: 'Payments', url: '/admin/payments', icon: Banknote },
    ];

    if (isSuperAdmin) {
        billingItems.push({ title: 'Payment controls', url: '/admin/billing/payment-controls', icon: ToggleLeft });
    }

    return [
        {
            title: 'Overview',
            items: [{ title: 'Dashboard', url: '/admin/dashboard', icon: LayoutGrid }],
        },
        {
            title: 'Management',
            items: managementItems,
        },
        {
            title: 'Billing',
            items: billingItems,
        },
        {
            title: 'Abuse',
            items: [
                { title: 'QR Reports', url: '/admin/qr-reports', icon: Flag },
                { title: 'Blocked Domains', url: '/admin/blocked-domains', icon: ShieldBan },
            ],
        },
        {
            title: 'System',
            items: [
                { title: 'Settings', url: '/admin/settings', icon: Settings },
                { title: 'Branding & SEO', url: '/admin/settings/branding', icon: Globe },
                { title: 'Audit Logs', url: '/admin/audit-logs', icon: ScrollText },
            ],
        },
    ];
}

function isNavActive(url: string, currentUrl: string): boolean {
    return currentUrl === url || currentUrl.startsWith(`${url}/`);
}

export function AdminSidebar() {
    const page = usePage<SharedData>();
    const isSuperAdmin = Boolean(page.props.auth.is_super_admin);
    const navGroups = buildNavGroups(isSuperAdmin);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/admin/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <SidebarGroup key={group.title} className="px-2 py-0">
                        <SidebarGroupLabel>{group.title}</SidebarGroupLabel>
                        <SidebarMenu>
                            {group.items.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton asChild isActive={isNavActive(item.url, page.url)}>
                                        <Link href={item.url} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
