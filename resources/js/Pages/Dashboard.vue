<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    generatedEmails: Array,
    maxEmails: Number,
    domain: String,
});

const generateForm = useForm({});
const copiedId = ref(null);
const refreshing = ref(false);
let dashboardPoll = null;

const isUnlimited = computed(() => !props.maxEmails || props.maxEmails <= 0);

const canGenerate = computed(() => {
    if (isUnlimited.value) return true;
    return props.generatedEmails.length < props.maxEmails;
});

function refreshDashboard() {
    refreshing.value = true;
    router.reload({
        only: ['generatedEmails'],
        preserveScroll: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

onMounted(() => {
    dashboardPoll = setInterval(() => {
        router.reload({
            only: ['generatedEmails'],
            preserveScroll: true,
        });
    }, 15000);
});

onUnmounted(() => {
    if (dashboardPoll) clearInterval(dashboardPoll);
});

function generateEmail() {
    generateForm.post(route('emails.generate'), {
        preserveScroll: true,
    });
}

function deleteEmail(id) {
    if (confirm('Are you sure you want to deactivate this email address?')) {
        router.delete(route('emails.destroy', id), {
            preserveScroll: true,
        });
    }
}

function copyToClipboard(email, id) {
    navigator.clipboard.writeText(email).then(() => {
        copiedId.value = id;
        setTimeout(() => {
            copiedId.value = null;
        }, 2000);
    });
}

function formatDate(date) {
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function timeUntilExpiry(expiresAt) {
    if (!expiresAt) return 'Permanent';
    const now = new Date();
    const expiry = new Date(expiresAt);
    const diff = expiry - now;
    if (diff <= 0) return 'Expired';
    const minutes = Math.floor(diff / 60000);
    if (minutes < 60) return `${minutes}m left`;
    const hours = Math.floor(minutes / 60);
    return `${hours}h ${minutes % 60}m left`;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    📧 Email Generator
                </h2>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">
                        <template v-if="!isUnlimited">
                            {{ generatedEmails.length }} / {{ maxEmails }} active emails
                        </template>
                        <template v-else>
                            {{ generatedEmails.length }} active emails (Unlimited)
                        </template>
                    </span>
                    <button
                        @click="refreshDashboard"
                        :disabled="refreshing"
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-colors shadow-sm"
                    >
                        <svg :class="{ 'animate-spin': refreshing }" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ refreshing ? 'Syncing...' : 'Refresh' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Generate Button -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Generate New Email</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Get a temporary email address @{{ domain }}
                            </p>
                        </div>
                        <button
                            @click="generateEmail"
                            :disabled="!canGenerate || generateForm.processing"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="generateForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="!generateForm.processing">⚡ Generate Email</span>
                            <span v-else>Generating...</span>
                        </button>
                    </div>
                    <p v-if="!canGenerate && !isUnlimited" class="text-sm text-red-500 mt-2">
                        You've reached the maximum of {{ maxEmails }} active emails.
                    </p>
                </div>

                <!-- Email List -->
                <div v-if="generatedEmails.length > 0" class="space-y-3">
                    <h3 class="text-lg font-medium text-gray-900 px-1">Your Active Emails</h3>

                    <div
                        v-for="email in generatedEmails"
                        :key="email.id"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-mono font-semibold text-indigo-600 truncate">
                                        {{ email.email_address }}
                                    </span>
                                    <button
                                        @click="copyToClipboard(email.email_address, email.id)"
                                        class="text-gray-400 hover:text-indigo-600 transition-colors flex-shrink-0"
                                        :title="copiedId === email.id ? 'Copied!' : 'Copy to clipboard'"
                                    >
                                        <span v-if="copiedId === email.id" class="text-green-500 text-sm font-medium">✓ Copied!</span>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span>📅 {{ formatDate(email.created_at) }}</span>
                                    <span v-if="!email.expires_at" class="inline-flex items-center text-emerald-600 font-semibold text-xs bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                        ♾️ Permanent
                                    </span>
                                    <span v-else :class="{
                                        'text-orange-500': timeUntilExpiry(email.expires_at) !== 'Expired',
                                        'text-red-500': timeUntilExpiry(email.expires_at) === 'Expired',
                                    }">⏱ {{ timeUntilExpiry(email.expires_at) }}</span>
                                    <span v-if="email.unread_count > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ email.unread_count }} unread
                                    </span>
                                    <span class="text-gray-400">
                                        {{ email.received_emails_count }} email(s)
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 ml-4">
                                <Link
                                    :href="route('inbox.index', email.id)"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-indigo-300 rounded-lg text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition-colors"
                                >
                                    📬 Inbox
                                    <span v-if="email.unread_count > 0" class="ml-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">
                                        {{ email.unread_count }}
                                    </span>
                                </Link>
                                <button
                                    @click="deleteEmail(email.id)"
                                    class="inline-flex items-center px-3 py-2 bg-white border border-red-300 rounded-lg text-sm font-medium text-red-700 hover:bg-red-50 transition-colors"
                                    title="Deactivate this email"
                                >
                                    🗑
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-lg font-medium text-gray-900">No active emails yet</h3>
                    <p class="text-gray-500 mt-2">Click "Generate Email" to get a temporary email address.</p>
                </div>

                <!-- How it works -->
                <div class="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-md font-semibold text-blue-900 mb-3">💡 How it works</h3>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                        <li>Click <strong>"Generate Email"</strong> to get a random temporary email address</li>
                        <li><strong>Copy</strong> the email address and use it for sign-ups, verifications, etc.</li>
                        <li>Open the <strong>Inbox</strong> to receive and read incoming emails</li>
                        <li>Emails auto-expire after the set duration</li>
                    </ol>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
