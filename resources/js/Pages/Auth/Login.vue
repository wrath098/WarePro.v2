<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    canRgister: {
        type:Boolean,
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
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <div class="relative py-3 sm:max-w-xl sm:mx-auto">
            <div
            class="absolute inset-0 bg-gradient-to-r from-indigo-400 to-purple-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl">
            </div>
            <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                <div class="flex justify-center items-center mb-10">
                    <svg class="w-20 h-20 fill-current text-indigo-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32">
                        <path fill="currentColor" d="m22.504 17.636l-6-3.5a1 1 0 0 0-1.008 0l-6 3.5A1 1 0 0 0 9 18.5v7a1 1 0 0 0 .496.864l6 3.5a1 1 0 0 0 1.008 0l6-3.5A1 1 0 0 0 23 25.5v-7a1 1 0 0 0-.496-.864ZM21 23.834l-2.532-1.519c.013-.104.032-.207.032-.315a2.502 2.502 0 0 0-1.5-2.288v-2.97l4 2.332v4.76Zm-6-7.093v2.97A2.502 2.502 0 0 0 13.5 22c0 .108.019.21.032.315L11 23.834v-4.76l4-2.333Zm1 11.101l-3.952-2.305l2.507-1.504c.41.291.906.467 1.445.467s1.036-.176 1.445-.467l2.507 1.504L16 27.842Z"/>
                        <path fill="currentColor" d="M24.8 9.14C23.93 5.02 20.28 2 16 2S8.07 5.02 7.2 9.14C4.23 9.74 2 12.4 2 15.5c0 3.07 2.14 5.63 5 6.31V19.7c-1.74-.62-3-2.24-3-4.2c0-2.33 1.82-4.31 4.14-4.49l.82-.06l.1-.81C9.49 6.64 12.47 4 16 4s6.51 2.64 6.94 6.14l.1.81l.82.06c2.32.19 4.14 2.16 4.14 4.49c0 1.95-1.26 3.59-3 4.21v2.11c2.86-.68 5-3.26 5-6.32c0-3.11-2.23-5.76-5.2-6.36Z"/>
                    </svg>
                    <h1 class="text-3xl font-extrabold text-indigo-700">WarePro</h1>
                </div>

                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Email" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full text-indigo"
                            v-model="form.email"
                            required
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
                            <span class="ms-2 text-lg text-gray-600">Remember me</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <PrimaryButton class="ms-4 bg-gradient-to-r from-purple-400 to-indigo-500 w-full text-center" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Log in
                        </PrimaryButton>
                    </div>
                </form>

            </div>
        </div>

    </GuestLayout>
</template>
