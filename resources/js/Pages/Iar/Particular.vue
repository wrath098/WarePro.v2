<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';

    const props = defineProps({
        iar: Object,
        particulars: Object,
    });
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-green-700">Inspection and Acceptance</a></li>
                    <li class="after:content-[':'] after:ml-2 text-green-700" aria-current="page">Transactions</li>
                    <li class="text-green-700" aria-current="page">IAR No. {{ iar.sdi_iar_id }} - P.O. No. {{ iar.po_no }}</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="flex flex-wrap bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-for="particular in particulars" class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 p-6 m-2 bg-white border border-gray-200 rounded-lg shadow">
                        <a href="#">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight">Item No. {{ particular.item_no }}</h5>
                        </a>
                        <p class="mb-3 font-normal text-gray-700">Stock No: {{ particular.stock_no }}</p>
                        <p class="mb-3 font-normal text-gray-700">Description: {{ particular.description }}</p>
                        <p class="mb-3 font-normal text-gray-700">Quantity: {{ particular.quantity }}</p>
                        <p class="mb-3 font-normal text-gray-700">Unit Cost: {{ particular.unit_cost }}</p>
                        <p class="mb-3 font-normal text-gray-700">Total Cost: {{ particular.total_cost }}</p>
                        <input type="hidden" name="particulars[]" :value="particular.air_particular_id">

                        <div class="flex md:flex-row justify-between">
                            <button type="submit" name="action" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300">
                                Reject
                                <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                </svg>
                            </button>
                            <a href="#" class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300">
                                Accept
                                <svg class="rtl:rotate-180 w-3.5 h-3.5 ms-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>