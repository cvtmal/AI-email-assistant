import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CheckCircle, XCircle, Clock, Mail, TrendingUp, Calendar, AlertTriangle } from 'lucide-react';

interface EmailActivity {
  id: number;
  email_id: string;
  account: string;
  status: 'sent' | 'failed' | 'sending' | 'draft';
  recipient_email: string;
  subject: string;
  sent_at: string | null;
  failed_at: string | null;
  error_message: string | null;
}

interface ActivityStats {
  total_sent: number;
  sent_today: number;
  sent_this_week: number;
  failed_count: number;
}

interface AccountStats {
  [account: string]: {
    sent: number;
    failed: number;
  };
}

interface EmailActivityProps extends PageProps {
  recentActivity: EmailActivity[];
  stats: ActivityStats;
  accountStats: AccountStats;
  currentAccount?: string;
}

const StatusIcon = ({ status }: { status: string }) => {
  switch (status) {
    case 'sent':
      return <CheckCircle className="h-4 w-4 text-green-600" />;
    case 'failed':
      return <XCircle className="h-4 w-4 text-red-600" />;
    case 'sending':
      return <Clock className="h-4 w-4 text-yellow-600" />;
    default:
      return <Mail className="h-4 w-4 text-gray-400" />;
  }
};

const StatusBadge = ({ status }: { status: string }) => {
  const variants = {
    sent: 'default',
    failed: 'destructive',
    sending: 'secondary',
    draft: 'outline',
  } as const;

  return (
    <Badge variant={variants[status as keyof typeof variants] || 'outline'}>
      <StatusIcon status={status} />
      <span className="ml-1 capitalize">{status}</span>
    </Badge>
  );
};

export default function Index({ recentActivity, stats, accountStats, currentAccount }: EmailActivityProps) {
  return (
    <>
      <Head title="Email Activity" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Email Activity</h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Monitor your email sending activity and status
              {currentAccount && (
                <span className="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-sm">
                  {currentAccount}
                </span>
              )}
            </p>
          </div>

          {/* Account Filter Buttons */}
          <div className="mb-8 flex flex-wrap gap-2">
            <Button variant={!currentAccount ? "default" : "outline"} asChild>
              <Link href="/email-activity">All Accounts</Link>
            </Button>
            <Button variant={currentAccount === 'default' ? "default" : "outline"} asChild>
              <Link href="/email-activity?account=default">lucasmbaldauf@</Link>
            </Button>
            <Button variant={currentAccount === 'info' ? "default" : "outline"} asChild>
              <Link href="/email-activity?account=info">info@</Link>
            </Button>
            <Button variant={currentAccount === 'damian' ? "default" : "outline"} asChild>
              <Link href="/email-activity?account=damian">damianermanni@</Link>
            </Button>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card className="p-6">
              <div className="flex items-center">
                <Mail className="h-8 w-8 text-blue-600" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sent</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{stats.total_sent}</p>
                </div>
              </div>
            </Card>

            <Card className="p-6">
              <div className="flex items-center">
                <Calendar className="h-8 w-8 text-green-600" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Sent Today</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{stats.sent_today}</p>
                </div>
              </div>
            </Card>

            <Card className="p-6">
              <div className="flex items-center">
                <TrendingUp className="h-8 w-8 text-purple-600" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">This Week</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{stats.sent_this_week}</p>
                </div>
              </div>
            </Card>

            <Card className="p-6">
              <div className="flex items-center">
                <AlertTriangle className="h-8 w-8 text-red-600" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Failed</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{stats.failed_count}</p>
                </div>
              </div>
            </Card>
          </div>

          {/* Account Stats */}
          {!currentAccount && Object.keys(accountStats).length > 0 && (
            <Card className="p-6 mb-8">
              <h3 className="text-lg font-semibold mb-4">Activity by Account</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {Object.entries(accountStats).map(([account, stats]) => (
                  <div key={account} className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 className="font-medium text-gray-900 dark:text-gray-100 mb-2 capitalize">
                      {account === 'default' ? 'Lucas (Default)' : account}
                    </h4>
                    <div className="flex justify-between text-sm">
                      <span className="text-green-600">Sent: {stats.sent}</span>
                      <span className="text-red-600">Failed: {stats.failed}</span>
                    </div>
                  </div>
                ))}
              </div>
            </Card>
          )}

          {/* Recent Activity */}
          <Card className="p-6">
            <h3 className="text-lg font-semibold mb-4">Recent Activity</h3>
            
            {recentActivity.length === 0 ? (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <Mail className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No email activity found.</p>
                <p className="text-sm mt-2">Start by sending some email replies!</p>
              </div>
            ) : (
              <div className="space-y-4">
                {recentActivity.map((activity) => (
                  <div key={activity.id} className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <StatusBadge status={activity.status} />
                          <span className="text-sm text-gray-500 dark:text-gray-400 capitalize">
                            {activity.account === 'default' ? 'lucasmbaldauf@' : activity.account}
                          </span>
                        </div>
                        
                        <h4 className="font-medium text-gray-900 dark:text-gray-100 mb-1">
                          {activity.subject || 'No subject'}
                        </h4>
                        
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                          To: {activity.recipient_email}
                        </p>
                        
                        {activity.error_message && (
                          <div className="p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-sm text-red-700 dark:text-red-300">
                            <strong>Error:</strong> {activity.error_message}
                          </div>
                        )}
                      </div>
                      
                      <div className="text-right text-sm text-gray-500 dark:text-gray-400">
                        {activity.sent_at ? (
                          <div>
                            <p>Sent</p>
                            <p>{formatDate(activity.sent_at)}</p>
                          </div>
                        ) : activity.failed_at ? (
                          <div>
                            <p>Failed</p>
                            <p>{formatDate(activity.failed_at)}</p>
                          </div>
                        ) : (
                          <p>Pending</p>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </Card>
        </div>
      </div>
    </>
  );
}

Index.layout = (page: React.ReactNode) => (
  <AppLayout
    children={page}
    breadcrumbs={[
      { title: 'Dashboard', href: '/dashboard' },
      { title: 'Email Activity', href: '/email-activity' },
    ]}
  />
);