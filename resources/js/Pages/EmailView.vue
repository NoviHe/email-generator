<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    generatedEmail: Object,
    email: Object,
});

const showHtml = ref(true);
const copiedBody = ref(false);

const displayBody = computed(() => {
    if (showHtml.value && props.email.body_html) {
        return props.email.body_html;
    }
    return null;
});

const textBody = computed(() => {
    return props.email.body_text || 'No text content available.';
});

function formatDate(date) {
    return new Date(date).toLocaleString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function deleteEmail() {
    if (confirm('Delete this email?')) {
        router.delete(route('inbox.destroy', [props.generatedEmail.id, props.email.id]), {
            onSuccess: () => {
                router.visit(route('inbox.index', props.generatedEmail.id));
            },
        });
    }
}

function copyBody() {
    const text = props.email.body_text || props.email.body_html?.replace(/<[^>]*>/g, '') || '';
    navigator.clipboard.writeText(text).then(() => {
        copiedBody.value = true;
        setTimeout(() => copiedBody.value = false, 2000);
    });
}

function extractCode() {
    const text = props.email.body_text || props.email.body_html?.replace(/<[^>]*>/g, '') || '';
    // Try to find verification codes (4-8 digit numbers or alphanumeric codes)
    const patterns = [
        /\b(\d{4,8})\b/g,  // 4-8 digit numbers
        /\b([A-Z0-9]{6,8})\b/g,  // 6-8 char alphanumeric codes
    ];

    for (const pattern of patterns) {
        const matches = text.match(pattern);
        if (matches && matches.length > 0) {
            return matches[0];
        }
    }
    return null;
}

const verificationCode = computed(() => extractCode());

function copyCode() {
    if (verificationCode.value) {
        navigator.clipboard.writeText(verificationCode.value);
    }
}
</script>

<template>
    <Head :title="email.subject || '(No Subject)'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('inbox.index', generatedEmail.id)" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">📧 {{ email.subject || '(No Subject)' }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

                <!-- Verification Code Banner -->
                <div v-if="verificationCode" class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🔑</span>
                        <div>
                            <p class="text-sm font-medium text-green-800">Verification Code Detected</p>
                            <p class="text-2xl font-mono font-bold text-green-900 tracking-wider">{{ verificationCode }}</p>
                        </div>
                    </div>
                    <button
                        @click="copyCode"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                    >
                        📋 Copy Code
                    </button>
                </div>

                <!-- Email Header -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-xl font-semibold text-gray-900">{{ email.subject || '(No Subject)' }}</h1>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="copyBody" class="text-sm text-gray-500 hover:text-indigo-600 transition-colors">
                                    {{ copiedBody ? '✓ Copied!' : '📋 Copy' }}
                                </button>
                                <button @click="deleteEmail" class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                                    🗑 Delete
                                </button>
                            </div>
                        </div>

                        <div class="border-t pt-3 space-y-1.5">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500 w-16">From:</span>
                                <span class="font-medium text-gray-900">{{ email.from_name || 'Unknown' }}</span>
                                <span class="text-gray-400">&lt;{{ email.from_address }}&gt;</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500 w-16">To:</span>
                                <span class="font-mono text-indigo-600">{{ generatedEmail.email_address }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500 w-16">Date:</span>
                                <span class="text-gray-700">{{ formatDate(email.received_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Toggle -->
                <div v-if="email.body_html && email.body_text" class="flex gap-2 px-1">
                    <button
                        @click="showHtml = true"
                        class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                        :class="showHtml ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'"
                    >
                        HTML View
                    </button>
                    <button
                        @click="showHtml = false"
                        class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                        :class="!showHtml ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'"
                    >
                        Text View
                    </button>
                </div>

                <!-- Email Body -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div v-if="showHtml && email.body_html" class="p-6">
                        <iframe
                            :srcdoc="email.body_html"
                            class="w-full border-0"
                            style="min-height: 500px;"
                            sandbox="allow-same-origin"
                            @load="$event.target.style.height = $event.target.contentWindow.document.body.scrollHeight + 'px'"
                        ></iframe>
                    </div>
                    <div v-else class="p-6">
                        <pre class="whitespace-pre-wrap text-sm text-gray-700 font-mono">{{ textBody }}</pre>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
