import MarketingFooter from '@/components/marketing/marketing-footer';
import MarketingHeader from '@/components/marketing/marketing-header';
import { type ReactNode } from 'react';

interface MarketingLayoutProps {
    children: ReactNode;
}

export default function MarketingLayout({ children }: MarketingLayoutProps) {
    return (
        <div className="bg-background flex min-h-screen flex-col">
            <MarketingHeader />
            <main className="flex-1">{children}</main>
            <MarketingFooter />
        </div>
    );
}
