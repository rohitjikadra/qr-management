export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface AdminTeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    email_verified_at: string | null;
    last_login_at: string | null;
    created_at: string;
}

export interface AdminUserListItem {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    billing_discount_percent: number | null;
    qr_codes_count: number;
    created_at: string;
}

export interface AdminStats {
    total_users: number;
    new_users_30d: number;
    paid_users: number;
    mrr: number;
    revenue_this_month: number;
    revenue_growth_percent: number;
    total_qr_codes: number;
    active_qr_codes: number;
    total_scans: number;
    scans_this_month: number;
}

export interface SignupsChartData {
    labels: string[];
    values: number[];
}
