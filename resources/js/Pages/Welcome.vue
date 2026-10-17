<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Temporary Email Generator" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-indigo-800">
        <!-- Nav -->
        <nav class="absolute top-0 right-0 p-6">
            <div v-if="canLogin" class="flex items-center gap-4">
                <Link :href="route('login')" class="text-white/80 hover:text-white text-sm transition-colors">
                    Log in
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="bg-white text-indigo-900 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition-colors">
                    Register
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center px-6 max-w-3xl">
                <div class="text-8xl mb-6">📧</div>
                <h1 class="text-5xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                    Temporary Email
                    <span class="bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent">Generator</span>
                </h1>
                <p class="text-xl text-indigo-200 mb-10 leading-relaxed">
                    Get instant disposable email addresses. Receive verification codes, OTP, and emails from social media without exposing your real email.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                    <Link v-if="canRegister" :href="route('register')" class="bg-white text-indigo-900 px-8 py-4 rounded-xl text-lg font-bold hover:bg-indigo-100 transition-all hover:scale-105 shadow-xl">
                        🚀 Get Started Free
                    </Link>
                    <Link v-if="canLogin" :href="route('login')" class="bg-white/10 backdrop-blur text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-white/20 transition-all border border-white/20">
                        Sign In
                    </Link>
                </div>

                <!-- Features -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                        <div class="text-3xl mb-3">⚡</div>
                        <h3 class="text-white font-semibold text-lg mb-2">Instant Generate</h3>
                        <p class="text-indigo-300 text-sm">Get a random email address in one click. No configuration needed.</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                        <div class="text-3xl mb-3">🔑</div>
                        <h3 class="text-white font-semibold text-lg mb-2">Verification Codes</h3>
                        <p class="text-indigo-300 text-sm">Automatically detects and highlights OTP/verification codes from emails.</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                        <div class="text-3xl mb-3">🔒</div>
                        <h3 class="text-white font-semibold text-lg mb-2">Privacy First</h3>
                        <p class="text-indigo-300 text-sm">Keep your real email private. Temporary emails auto-expire and get deleted.</p>
                    </div>
                </div>

                <p class="text-indigo-400 text-sm mt-12">
                    Built with Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}
                </p>
            </div>
        </div>
    </div>
</template>
