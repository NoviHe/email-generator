<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    generatedEmail: Object,
    receivedEmails: Object,
});

const emails = computed(() => props.receivedEmails?.data || []);
const loading = ref(false);
const copiedEmail = ref(false);
let pollInterval = null;

function copyToClipboard() {
    navigator.clipboard.writeText(props.generatedEmail.email_address).then(() => {
        copiedEmail.value = true;
        setTimeout(() => {
            copiedEmail.value = false;
        }, 2000);
    });
}

function checkNewEmails() {
    loading.value = true;
    router.reload({
        only: ['receivedEmails'],
        preserveScroll: true,
        onFinish: () => {
            loading.value = false;
        },
    });
}

function deleteEmail(receivedEmailId) {
    if (confirm('Delete this email?')) {
        router.delete(route('inbox.destroy', [props.generatedEmail.id, receivedEmailId]), {
            preserveScroll: true,
        });
    }
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

function timeSince(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

onMounted(() => {
    // Poll every 10 seconds
    pollInterval = setInterval(checkNewEmails, 10000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <Head :title="`Inbox - ${generatedEmail.email_address}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">📬 Inbox</h2>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg">
                        <span class="text-sm font-mono text-indigo-600">{{ generatedEmail.email_address }}</span>
                        <button @click="copyToClipboard" class="text-gray-400 hover:text-indigo-600">
                            <span v-if="copiedEmail" class="text-green-500 text-xs">✓</span>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <button
                        @click="checkNewEmails"
                        :disabled="loading"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                    >
                        <svg :class="{ 'animate-spin': loading }" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                <!-- Auto-refresh indicator -->
                <div class="flex items-center justify-between mb-4 px-1">
                    <span class="text-sm text-gray-500">
                        {{ emails.length }} email(s)
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span class="text-xs text-gray-400">Auto-refresh every 10s</span>
                    </div>
                </div>

                <!-- Email List -->
                <div v-if="emails.length > 0" class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                    <Link
                        v-for="email in emails"
                        :key="email.id"
                        :href="route('inbox.show', [generatedEmail.id, email.id])"
                        class="block p-4 hover:bg-gray-50 transition-colors"
                        :class="{ 'bg-indigo-50/30': !email.is_read }"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span v-if="!email.is_read" class="inline-block w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                                    <span class="text-sm font-medium text-gray-900" :class="{ 'font-bold': !email.is_read }">
                                        {{ email.from_name || email.from_address }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ timeSince(email.received_at) }}</span>
                                </div>
                                <p class="text-sm mt-1" :class="!email.is_read ? 'text-gray-900 font-semibold' : 'text-gray-700'">
                                    {{ email.subject || '(No Subject)' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1 truncate">
                                    {{ email.from_address }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <span class="text-xs text-gray-400">{{ formatDate(email.received_at) }}</span>
                                <button
                                    @click.prevent.stop="deleteEmail(email.id)"
                                    class="text-gray-300 hover:text-red-500 transition-colors p-1"
                                    title="Delete"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h3 class="text-lg font-medium text-gray-900">Waiting for emails...</h3>
                    <p class="text-gray-500 mt-2">Emails sent to <span class="font-mono text-indigo-600">{{ generatedEmail.email_address }}</span> will appear here.</p>
                    <p class="text-sm text-gray-400 mt-4">Auto-refreshing every 10 seconds</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
