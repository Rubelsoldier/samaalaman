<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Clipboard copy functionality with feedback

const copied = ref({ email: false, pass: false });

function copyToClipboard(text, type) {
    if (
        typeof navigator !== 'undefined' &&
        navigator.clipboard &&
        typeof navigator.clipboard.writeText === 'function'
    ) {
        navigator.clipboard.writeText(text).then(() => {
            copied.value[type] = true;
            setTimeout(() => {
                copied.value[type] = false;
            }, 1200);
        }).catch(() => {
            // Optionally handle error
        });
    } else {
        // Fallback for unsupported browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            copied.value[type] = true;
            setTimeout(() => {
                copied.value[type] = false;
            }, 1200);
        } catch (e) {
            // Optionally handle error
        }
        document.body.removeChild(textarea);
    }
}

</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        Use below credentials to log in to the demo account.
        <div class="mt-2 mb-2 m-auto w-64">
            <div class="flex items-center dark:text-gray-400 text-xs">
                <span>Email: demo-login@saalam.site</span>
                <button @click="copyToClipboard('demo-login@saalam.site', 'email')" class="ml-2 text-xs underline" title="Copy email">
                    {{ copied.email ? 'copied' : 'copy' }}
                </button>
            </div>
            <div class="flex items-center dark:text-gray-400 text-xs">
                <span>Pass: 11111111</span>
                <button @click="copyToClipboard('11111111', 'pass')" class="ml-2 text-xs underline" title="Copy password">
                    {{ copied.pass ? 'copied' : 'copy' }}
                </button>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
