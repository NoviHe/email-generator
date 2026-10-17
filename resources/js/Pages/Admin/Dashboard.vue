<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: Object,
    users: Object,
    generatedEmails: Object,
    receivedEmails: Object,
    imapConfig: Object,
});

const activeTab = ref('users'); // 'users', 'emails', 'logs'
const selectedEmail = ref(null);
const syncing = ref(false);
const cleaning = ref(false);

const userSearch = ref('');
const emailSearch = ref('');
const logSearch = ref('');

// Filtered lists
const filteredUsers = computed(() => {
    if (!userSearch.value) return props.users.data;
    const q = userSearch.value.toLowerCase();
    return props.users.data.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    );
});

const filteredEmails = computed(() => {
    if (!emailSearch.value) return props.generatedEmails.data;
    const q = emailSearch.value.toLowerCase();
    return props.generatedEmails.data.filter(e =>
        e.email_address.toLowerCase().includes(q) ||
        e.user?.name.toLowerCase().includes(q) ||
        e.user?.email.toLowerCase().includes(q)
    );
});

const filteredLogs = computed(() => {
    if (!logSearch.value) return props.receivedEmails.data;
    const q = logSearch.value.toLowerCase();
    return props.receivedEmails.data.filter(l =>
        l.from_address.toLowerCase().includes(q) ||
        (l.from_name && l.from_name.toLowerCase().includes(q)) ||
        (l.subject && l.subject.toLowerCase().includes(q)) ||
        (l.generated_email?.email_address && l.generated_email.email_address.toLowerCase().includes(q))
    );
});

function syncImap() {
    syncing.value = true;
    router.post(route('admin.sync-imap'), {}, {
        preserveScroll: true,
        onFinish: () => syncing.value = false,
    });
}

function cleanupExpired() {
    cleaning.value = true;
    router.post(route('admin.cleanup-expired'), {}, {
        preserveScroll: true,
        onFinish: () => cleaning.value = false,
    });
}

function toggleAdmin(user) {
    const action = user.is_admin ? 'demote' : 'promote';
    if (confirm(`Are you sure you want to ${action} ${user.name}?`)) {
        router.post(route('admin.users.toggle-admin', user.id), {}, {
            preserveScroll: true,
        });
    }
}

function deleteUser(user) {
    if (confirm(`WARNING: This will permanently delete user "${user.name}" and all their generated & received emails! Continue?`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
        });
    }
}

function deleteGeneratedEmail(email) {
    if (confirm(`Delete temporary email address "${email.email_address}"?`)) {
        router.delete(route('admin.emails.destroy', email.id), {
            preserveScroll: true,
        });
    }
}

function deleteReceivedEmail(log) {
    if (confirm('Delete this received email record?')) {
        router.delete(route('admin.received-emails.destroy', log.id), {
            preserveScroll: true,
        });
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function viewEmailModal(log) {
    selectedEmail.value = log;
}
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 flex items-center gap-2">
                        <span>🛡️</span> Super Admin Control Center
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Monitoring all users, active disposable addresses, and cPanel incoming mail logs
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="syncImap"
                        :disabled="syncing"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                    >
                        <svg :class="{ 'animate-spin': syncing }" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ syncing ? 'Syncing...' : 'Sync IMAP Now' }}
                    </button>
                    <button
                        @click="cleanupExpired"
                        :disabled="cleaning"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 transition-colors disabled:opacity-50"
                    >
                        🧹 Clean Expired
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- cPanel Server Info Badge -->
                <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/10 p-3 rounded-lg text-2xl">📡</div>
                        <div>
                            <div class="text-xs text-blue-200 uppercase tracking-wider font-semibold">Active cPanel Provider</div>
                            <div class="text-lg font-bold font-mono">{{ imapConfig.host }} : {{ imapConfig.port }}</div>
                            <div class="text-xs text-blue-300">Mailbox: {{ imapConfig.username }} | Domain: @{{ imapConfig.domain }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-300 border border-green-500/30">
                            ● Connected & Catch-All Active
                        </span>
                    </div>
                </div>

                <!-- 6 Metrics Cards Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Total Users</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_users }}</div>
                        <div class="text-xs text-gray-400 mt-1">Registered accounts</div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Generated Emails</div>
                        <div class="text-2xl font-bold text-indigo-600 mt-1">{{ stats.total_generated_emails }}</div>
                        <div class="text-xs text-gray-400 mt-1">All time created</div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Active Emails</div>
                        <div class="text-2xl font-bold text-green-600 mt-1">{{ stats.active_generated_emails }}</div>
                        <div class="text-xs text-green-500 mt-1">Live & receiving</div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Expired Emails</div>
                        <div class="text-2xl font-bold text-gray-400 mt-1">{{ stats.expired_generated_emails }}</div>
                        <div class="text-xs text-gray-400 mt-1">Past lifetime</div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Received Emails</div>
                        <div class="text-2xl font-bold text-purple-600 mt-1">{{ stats.total_received_emails }}</div>
                        <div class="text-xs text-gray-400 mt-1">Inbound messages</div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="text-xs font-medium text-gray-400 uppercase">Received Today</div>
                        <div class="text-2xl font-bold text-amber-500 mt-1">{{ stats.received_today }}</div>
                        <div class="text-xs text-gray-400 mt-1">Since 00:00 today</div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-200 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex space-x-2">
                            <button
                                @click="activeTab = 'users'"
                                :class="activeTab === 'users' ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-600 hover:bg-gray-100'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors flex items-center gap-2"
                            >
                                👥 Users Management ({{ users.total || users.data.length }})
                            </button>
                            <button
                                @click="activeTab = 'emails'"
                                :class="activeTab === 'emails' ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-600 hover:bg-gray-100'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors flex items-center gap-2"
                            >
                                📧 Generated Emails ({{ generatedEmails.total || generatedEmails.data.length }})
                            </button>
                            <button
                                @click="activeTab = 'logs'"
                                :class="activeTab === 'logs' ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-600 hover:bg-gray-100'"
                                class="px-4 py-2 rounded-lg text-sm transition-colors flex items-center gap-2"
                            >
                                📬 Inbound Email Logs ({{ receivedEmails.total || receivedEmails.data.length }})
                            </button>
                        </div>
                    </div>

                    <!-- TAB 1: USERS MANAGEMENT -->
                    <div v-if="activeTab === 'users'" class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <input
                                v-model="userSearch"
                                type="text"
                                placeholder="Search by user name or email..."
                                class="w-72 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <span class="text-xs text-gray-400">Showing {{ filteredUsers.length }} users</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="py-3 px-4">User</th>
                                        <th class="py-3 px-4">Role</th>
                                        <th class="py-3 px-4">Active Emails</th>
                                        <th class="py-3 px-4">Total Generated</th>
                                        <th class="py-3 px-4">Registered Date</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-xs text-gray-400">{{ user.email }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="user.is_admin" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                                🛡️ Super Admin
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                User
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-green-600">
                                            {{ user.active_emails_count }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">
                                            {{ user.generated_emails_count }}
                                        </td>
                                        <td class="py-3 px-4 text-gray-400 text-xs">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button
                                                @click="toggleAdmin(user)"
                                                :class="user.is_admin ? 'text-amber-600 hover:text-amber-800' : 'text-indigo-600 hover:text-indigo-800'"
                                                class="text-xs font-medium"
                                            >
                                                {{ user.is_admin ? 'Demote' : 'Make Admin' }}
                                            </button>
                                            <button
                                                @click="deleteUser(user)"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium ml-2"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: GENERATED EMAILS -->
                    <div v-if="activeTab === 'emails'" class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <input
                                v-model="emailSearch"
                                type="text"
                                placeholder="Search email address or owner..."
                                class="w-72 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <span class="text-xs text-gray-400">Showing {{ filteredEmails.length }} generated addresses</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="py-3 px-4">Temporary Email</th>
                                        <th class="py-3 px-4">Owner (User)</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Inbox Count</th>
                                        <th class="py-3 px-4">Created</th>
                                        <th class="py-3 px-4">Expires At</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="email in filteredEmails" :key="email.id" class="hover:bg-gray-50">
                                        <td class="py-3 px-4 font-mono font-medium text-indigo-600">
                                            {{ email.email_address }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-gray-900 font-medium">{{ email.user?.name || '-' }}</div>
                                            <div class="text-xs text-gray-400">{{ email.user?.email || '-' }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span v-if="email.is_active" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                Deactivated
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-bold text-gray-800">{{ email.received_emails_count }}</span>
                                            <span v-if="email.unread_count > 0" class="text-xs text-red-500 ml-1 font-semibold">
                                                ({{ email.unread_count }} unread)
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-400">
                                            {{ formatDate(email.created_at) }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-400">
                                            {{ formatDate(email.expires_at) }}
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button
                                                @click="deleteGeneratedEmail(email)"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: INCOMING EMAIL LOGS -->
                    <div v-if="activeTab === 'logs'" class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <input
                                v-model="logSearch"
                                type="text"
                                placeholder="Search sender, subject, or recipient..."
                                class="w-72 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            <span class="text-xs text-gray-400">Showing {{ filteredLogs.length }} incoming email records</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="py-3 px-4">From</th>
                                        <th class="py-3 px-4">Subject</th>
                                        <th class="py-3 px-4">Recipient (Generated)</th>
                                        <th class="py-3 px-4">Owner User</th>
                                        <th class="py-3 px-4">Received Time</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-gray-900">{{ log.from_name || log.from_address }}</div>
                                            <div class="text-xs text-gray-400">{{ log.from_address }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <button
                                                @click="viewEmailModal(log)"
                                                class="text-left font-medium text-indigo-600 hover:underline max-w-xs truncate block"
                                            >
                                                {{ log.subject || '(No Subject)' }}
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs text-gray-700">
                                            {{ log.generated_email?.email_address || '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-500">
                                            {{ log.generated_email?.user?.name || '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-gray-400">
                                            {{ formatDate(log.received_at) }}
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button
                                                @click="viewEmailModal(log)"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                            >
                                                View
                                            </button>
                                            <button
                                                @click="deleteReceivedEmail(log)"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium ml-2"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Email Preview Modal -->
        <div v-if="selectedEmail" class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-6 border-b flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ selectedEmail.subject || '(No Subject)' }}</h3>
                        <p class="text-xs text-gray-400 mt-1">
                            From: <strong class="text-gray-700">{{ selectedEmail.from_name }} &lt;{{ selectedEmail.from_address }}&gt;</strong>
                        </p>
                        <p class="text-xs text-gray-400">
                            To: <strong class="font-mono text-indigo-600">{{ selectedEmail.generated_email?.email_address }}</strong>
                        </p>
                    </div>
                    <button @click="selectedEmail = null" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1">
                        ✕
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <div v-if="selectedEmail.body_html" v-html="selectedEmail.body_html" class="prose max-w-none text-sm"></div>
                    <pre v-else class="whitespace-pre-wrap font-mono text-sm text-gray-700">{{ selectedEmail.body_text || 'No content' }}</pre>
                </div>
                <div class="p-4 bg-gray-50 border-t flex justify-end">
                    <button
                        @click="selectedEmail = null"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-semibold transition-colors"
                    >
                        Close Preview
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
