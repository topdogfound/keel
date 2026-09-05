import { Head, usePage } from '@inertiajs/react';
import DashboardOverview from '@/components/home/dashboard-overview';
import GuestHome from '@/components/home/guest-home';

export default function Home() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={auth.user ? 'Dashboard' : 'Welcome'} />

            {auth.user ? <DashboardOverview /> : <GuestHome />}
        </>
    );
}
