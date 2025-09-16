<script setup>
    import { Head, usePage} from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted, ref } from 'vue';
    import useAuthPermission from '@/Composables/useAuthPermission';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const show = ref(false);

    const props = defineProps({
        ppmp: Object,
        transaction: Object,
        particulars: Object,
        countAvailableToPr: Number,
    });

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success!',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed!',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });

    const columns = [
        {
            data: 'prodCode',
            title: 'Code No#',
            width: '10%'
        },
        {
            data: 'prodName',
            title: 'Product Description',
            width: '20%'
        },
        {
            data: 'prodUnit',
            title: 'Unit of Measurement',
            width: '10%',
        },
        {
            data: 'totalQtyRequested',
            title: 'Total Qty <br> <span class="text-sm">(Consolidated)</span>',
            width: '10%'
        },
        {
            data: 'firstQty',
            title: 'First Semester <br> <span class="text-sm">(Consolidated)</span>',
            width: '10%'
        },
        {
            data: 'secondQty',
            title: 'Second Semester <br> <span class="text-sm">(Consolidated)</span>',
            width: '10%'
        },
        {
            data: 'firstRemaining',
            title: 'First Semester <br> <span class="text-sm">(Remaining Quantity for PR)</span>',
            width: '10%',
            render: (data, type, row) => {
                return `
                <span class="${data === '0'
                    ? 'text-emerald-800' 
                    : 'text-rose-800'}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: 'secondRemaining',
            title: 'Second Semester <br> <span class="text-sm">(Remaining Quantity for PR)</span>',
            width: '10%',
            render: (data, type, row) => {
                return `
                <span class="${data === '0'
                    ? 'text-emerald-800' 
                    : 'text-rose-800'}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: 'availableQty',
            title: 'Total Qty <br> <span class="text-sm">(Remaining Quantity for PR)</span>',
            width: '10%',
            render: (data, type, row) => {
                return `
                <span class="${data === '0'
                    ? 'text-emerald-800' 
                    : 'text-rose-800'}">
                    ${data}
                </span>
                `;
            },
        },
    ];
</script>

<template>
    <Head title="Purchase Request" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Purchase Request
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('pr.display.procurementBasis')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                PPMP-PR Overview
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                PPMP No# : {{ ppmp.ppmpCode }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="shadow sm:rounded-lg bg-zinc-300 mb-5">
                        <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                            <h3 class="font-bold text-lg leading-6 text-zinc-300">
                                APP Information
                            </h3>
                            <p class="text-sm text-zinc-300">
                                ID# [{{ ppmp.ppmpCode }}]
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:gap-2">
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Type
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.ppmpType }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Calendar Year
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.ppmpYear }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. of Items Available
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ countAvailableToPr }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Cumulative Total Amount
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        ₱ {{ ppmp.totalAmount }}
                                    </dd>
                                </div>
                            </dl>
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. of Purchase Request
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.prCount }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        PR Transaction No#
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        <ul>
                                            <li v-for="pr in transaction" :key="pr.id" class="cursor-pointer relative my-1">
                                                <a :href="hasPermission('view-purchase-request') || hasAnyRole(['Developer']) ? route('pr.show.particular', { prTransaction: pr.id}) : '#'" class="relative inline-flex font-semibold text-gray-800 hover:underline">{{ pr.pr_no }}</a>
                                            </li>
                                        </ul>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-zinc-300 p-4 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="relative overflow-x-auto md:overflow-hidden">
                            <DataTable
                                class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                                :columns="columns"
                                :data="props.particulars"
                                :options="{  paging: true,
                                    searching: true,
                                    ordering: true
                                }" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
    :deep(table.dataTable) {
        border: 2px solid #7393dc;
    }

    :deep(table.dataTable thead > tr > th) {
        background-color: #d8d8f6;
        border: 2px solid #7393dc;
        text-align: center;
        color: #03244d;
    }

    :deep(table.dataTable tbody > tr > td) {
        border-right: 2px solid #7393dc;
        text-align: center;
    }

    :deep(div.dt-container select.dt-input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }
</style>