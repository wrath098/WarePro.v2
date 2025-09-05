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

        <div v-if="status" class="mb-4 font-medium text-sm text-gray-300">
            {{ status }}
        </div>

        <div class="relative py-3 sm:max-w-xl sm:mx-auto">
            <div class="relative py-5 sm:rounded-3xl ">
                <div class="flex justify-center items-center py-5">
                    <img src="/WarePro.v2/assets/images/Winvexis.png" alt="Winvexis">
                </div>
                <form class="px-4 sm:px-16" @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Email"/>

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
                            <span class="ms-2 text-lg text-gray-200">Remember me</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <PrimaryButton class="ms-4 bg-gradient-to-r from-purple-400 to-indigo-500 w-full text-center" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Log in
                        </PrimaryButton>
                    </div>
                </form>

                <div class="flex flex-col justify-center items-center bg-primary pt-10 text-sm">
                    <p class="text-gray-300 font-bold text-center">© Provincial General Services Office</p>
                    <p class="text-gray-500">Built by IT Dev Team, inspired by users.</p>
                </div>

            </div>
        </div>

    </GuestLayout>
</template>
