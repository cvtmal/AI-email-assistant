import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Activity, LayoutGrid, Mail } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Email Activity',
        href: '/email-activity',
        icon: Activity,
    },
];

const inboxNavItems: NavItem[] = [
    {
        title: 'lucasmbaldauf@myitjob.ch',
        href: '/imapengine-inbox',
        icon: Mail,
    },
    {
        title: 'damian.ermanni@myitjob.ch',
        href: '/imapengine-inbox?account=damian',
        icon: Mail,
    },
    {
        title: 'info@myitjob.ch',
        href: '/imapengine-inbox?account=info',
        icon: Mail,
    }
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <div className="mt-6">
                    <h2 className="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase">Inboxes</h2>
                    <NavMain items={inboxNavItems} />
                </div>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
