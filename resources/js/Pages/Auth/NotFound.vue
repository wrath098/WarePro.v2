<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    isAuth: Boolean,
})
</script>

<template>
    <GuestLayout>
        <Head title="Page Not Found" />

        <div class="rounded-3xl relative py-3 sm:max-w-xl sm:mx-auto bg-gradient-to-b from-indigo-400 via-gray-100 to-gray-100 inline-block p-2">            
            <div class="p-4 bg-gray-100 rounded-3xl sm:p-16 text-center"> 
                <div class="px-4 my-3 flex justify-center sm:px-16">
                    <img src="/WarePro.v2/assets/images/Winvexis.png" alt="Winvexis" class="w-20 object-contain">
                </div>

                <div class="text-center text-gray-600 py-5">
                    <p class="text-5xl my-7 text-indigo-600 font-bold">404</p>
                    <h3 class="text-5xl font-bold mb-1">Not Found</h3>
                    <p class="text-lg">Requested resource is not available.</p>
                </div>

                <div v-if="isAuth" class="py-5">
                    <a class="px-6 py-2 min-w-[120px] text-center text-white bg-violet-600 border border-violet-600 rounded active:text-violet-500 hover:bg-transparent hover:text-violet-600 focus:outline-none focus:ring"
                        :href="route('dashboard')">
                        Dashboard
                    </a>
                </div>

                <div v-else class="py-5">
                    <a class="px-6 py-2 min-w-[120px] text-center text-white bg-violet-600 border border-violet-600 rounded active:text-violet-500 hover:bg-transparent hover:text-violet-600 focus:outline-none focus:ring"
                        :href="route('login')">
                        Login
                    </a>
                </div>

                <div class="flex flex-col justify-center items-center bg-primary pt-10 text-sm">
                    <p class="text-gray-500 font-bold">Copyright © Provincial General Services Office</p>
                    <p class="text-gray-300">Built by IT Dev Team, inspired by users.</p>
                </div>

            </div>
        </div>

    </GuestLayout>
</template>
