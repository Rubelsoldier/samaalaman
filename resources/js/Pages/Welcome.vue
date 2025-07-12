<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

function handleImageError() {
    document.getElementById('screenshot-container')?.classList.add('!hidden');
    document.getElementById('docs-card')?.classList.add('!row-span-1');
    document.getElementById('docs-card-content')?.classList.add('!flex-row');
    document.getElementById('background')?.classList.add('!hidden');
}
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gradient-to-br from-gray-100 to-gray-300 dark:from-zinc-900 dark:to-zinc-800 min-h-screen text-black/70 dark:text-white/80">
        <img
            id="background"
            class="absolute -left-20 top-0 max-w-[877px] opacity-10 pointer-events-none hidden sm:block"
            src="https://laravel.com/assets/img/welcome/background.svg"
        />
        <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-4 sm:px-6 lg:max-w-7xl">
                <!-- Hero Section -->
                <div class="flex flex-col items-center py-8 sm:py-10">
                    <img
                        src="/images/samalaman.png"
                        alt="Samaalaman Logo"
                        class="h-16 w-auto mb-4 sm:h-24 sm:mb-6"
                        @error="handleImageError"
                    />
                    <h1 class="text-2xl sm:text-4xl font-bold text-[#FF2D20] mb-2 text-center">Welcome to Samaalaman</h1>
                    <p class="text-base sm:text-lg text-gray-700 dark:text-gray-300 mb-6 text-center">
                        File Management System.<br>
                        Keep your files organized and accessible with ease.
                    </p>
                    <nav v-if="canLogin" class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-4 w-full sm:w-auto justify-center items-center">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('myFiles')"
                            class="w-full sm:w-auto px-5 py-2 rounded-md bg-[#FF2D20] text-white font-semibold shadow hover:bg-[#e62a1c] transition text-center"
                        >
                            My all files
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="w-full sm:w-auto px-5 py-2 rounded-md bg-[#FF2D20] text-white font-semibold shadow hover:bg-[#e62a1c] transition text-center"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="w-full sm:w-auto px-5 py-2 rounded-md bg-gray-200 text-gray-900 font-semibold shadow hover:bg-gray-300 transition dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700 text-center"
                            >
                                Register
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
