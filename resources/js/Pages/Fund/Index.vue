<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainContentLayout from '@/Layouts/MainContentLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    fundCluster: Array,
});
</script>

<template>
    <Head title="Fund" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-3">Fund</h2>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul>
                        <li v-for="fund in fundCluster" :key="fund.id">
                            {{ fund.name }}
                        </li>
                    </ul>
                    <!-- <MainContentLayout/> -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>