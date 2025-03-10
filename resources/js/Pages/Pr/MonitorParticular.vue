<script setup>
    import { Head, usePage} from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted, ref } from 'vue';
    
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
            width: '22%'
        },
        {
            data: 'prodUnit',
            title: 'Unit of Measurement',
            width: '8%',
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
                                Procurement Basis List
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Transaction No. : {{ ppmp.ppmpCode }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-4 lg:gap-4">
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="bg-indigo-600 text-white p-4 flex justify-between rounded-t-md">
                                <div class="font-bold text-lg">PPMP Information</div>
                            </div>
                            <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">PPMP No.</dt>
                                            <dd class="text-gray-700 sm:col-span-2">: {{ ppmp.ppmpCode }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Year</dt>
                                            <dd class="text-gray-700 sm:col-span-2">: {{ ppmp.ppmpYear}}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Cumulative <br> Total Amount</dt>
                                            <dd class="text-gray-700 sm:col-span-2">: {{ ppmp.totalAmount }}</dd>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">No. of Items Available</dt>
                                            <dd class="text-gray-700 sm:col-span-2">: {{ countAvailableToPr }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">{{ ppmp.prCount }} Purchase Request/s</dt>
                                            <dd class="text-gray-700 sm:col-span-2">
                                                <ul>
                                                    <li v-for="pr in transaction" :key="pr.id" class="cursor-pointer relative">
                                                        <a class="relative">: {{ pr.pr_no }}</a>
                                                    </li>
                                                </ul>
                                            </dd>
                                        </div>
                                    </dl>
                            </div>
                        </div>

                        <div class="col-span-3 bg-white rounded-md shadow mt-5 lg:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable
                                        class="display table-hover table-striped shadow-lg rounded-lg"
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
    .badge-pending {
    background-color: yellow;
    color: black;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-approved {
    background-color: green;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-rejected {
    background-color: red;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

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
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep([data-v-71da71f9] table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }
</style>