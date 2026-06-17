import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    is_super_admin?: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    branding?: {
        logo_url: string | null;
        favicon_url: string | null;
    };
    quote: { message: string; author: string };
    auth: Auth;
    billing_discount_percent?: number | null;
    impersonation?: { admin_id: number; admin_name: string } | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    billing_discount_percent?: number | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
