<script setup>
import { Head } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';

const props = defineProps({
    generalFund: Object,
});

const formatDecimal = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };
</script>

<template>
    <Head title="General Fund" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">General Fund</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-indigo-400 my-2 italic">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-gray-400 my-2 italic">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                            <li v-for="(yearData, year) in generalFund" :key="year" class="">
                                <div class="flex justify-center align-middle bg-indigo-600 p-2 rounded-t-xl mb-2">
                                    <h2 class="text-lg font-semibold text-[#ededee] mb-4">Calendar Year {{ year  }}</h2>
                                </div>
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">General Fund</dt>
                                            <dd class="text-gray-700 sm:col-span-2 text-right">{{ formatDecimal(yearData.totalAmount) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <h6 class="py-3 px-2 text-gray-400">Allocation</h6>
                                <div v-for="account in yearData.funds" :key="account.id" class="flow-root border border-gray-100 py-3 shadow-sm mb-3">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900 sm:col-span-2">{{ account.accountClass }} </dt>
                                            <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(account.amount) }}</dd>
                                        </div>
                                        <div v-for="allocation in account.allocations" :key="allocation.id" class="grid grid-cols-1 gap-1 pl-10 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900 sm:col-span-2">{{ allocation.semester }} - {{ allocation.description }}</dt>
                                            <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(allocation.amount) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </li>
                            <!-- <button @click="showModal('add')" class="flex items-center justify-center h-48 rounded-lg bg-gray-100 shadow-md transition-transform transform hover:scale-105">
                                <span class="text-xl font-semibold text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-6 h-6 text-indigo-900 transition duration-75 ml-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18M3 12h18"/>
                                    </svg>
                                    New Fund Cluster
                                </span>
                            </button> -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 