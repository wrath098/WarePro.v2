<script setup>
    import { computed, onMounted} from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { DataTable } from 'datatables.net-vue3';
    import Swal from 'sweetalert2';

    const props = defineProps({
        products: Object,
        countExpired: Number,
        countExpiring: Number,
    }); 

    const page = usePage();
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });


    const columns = [
        {
            data: null,
            title: 'Transaction',
            width: '20%',
            render: function(data, type, row) {
                return `<span>IAR No# ${row.description.sdi_iar_id}</span> <br> <span>PO No# ${row.description.po_no}</span>`;
            }
        },
        {
            data: 'stockNo',
            title: 'Stock No#',
            width: '10%',
        },
        {
            data: 'prodDesc',
            title: 'Description',
            width: '20%',
        },
        {
            data: 'qtyLeft',
            title: 'Quantity',
        },
        {
            data: 'status',
            title: 'Status',
            render: function(data, type, row) {
                let badgeClass = '';

                switch (data) {
                    case 'Expired':
                        badgeClass = 'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-red-300';
                        break;
                    case 'Expiring':
                        badgeClass = 'bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-yellow-300';
                        break;
                    default:
                        badgeClass = 'bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-green-300';
                        break;
                }

                return `<span class="${badgeClass}">${data}</span>`;
            }
        },
        {
            data: 'dateExpired',
            title: 'Expiration Date',
        },
    ];
</script>

<template>
    <Head title="Expiring Products" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Product Inventory
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('show.expiry.products')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Expiring Products
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="py-4 w-full px-4 lg:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="flex items-center bg-zinc-300 border border-zinc-400 rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-yellow-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                            <path fill="currentColor" d="M19.59 15.86L12.007 1.924C11.515 1.011 10.779.5 9.989.5c-.79 0-1.515.521-2.016 1.434L.409 15.861c-.49.901-.544 1.825-.138 2.53c.405.707 1.216 1.109 2.219 1.109h15.02c1.003 0 1.814-.402 2.22-1.108c.405-.706.351-1.619-.14-2.531ZM10 4.857c.395 0 .715.326.715.728v6.583c0 .402-.32.728-.715.728a.721.721 0 0 1-.715-.728V5.584c0-.391.32-.728.715-.728Zm0 11.624c-.619 0-1.11-.51-1.11-1.14c0-.63.502-1.141 1.11-1.141c.619 0 1.11.51 1.11 1.14c0 .63-.502 1.141-1.11 1.141Z"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Items Due to Expire Within 90 Days</h3>
                        <p class="text-3xl">{{ countExpiring }}</p>
                    </div>
                </div>
                <div class="flex items-center bg-zinc-300 border border-zinc-400 rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 1024 1023" stroke="currentColor">
                            <path fill="currentColor" d="m882 566l142 160l-174 47l46 122l-165-46l-59 174l-158-105l-74 105l-67-128H192l16-116H52l73-173L0 536l95-76L1 324l191-47V128l87 57L324 0l161 135L633 0l32 115l115-64l28 170l152-29l-68 154l131 93zM576 288q0-14-9.5-23t-22.5-9h-64q-13 0-22.5 9t-9.5 23v256q0 13 9.5 22.5T480 576h64q13 0 22.5-9.5T576 544V288zm0 384q0-14-9.5-23t-22.5-9h-64q-13 0-22.5 9t-9.5 23v63q0 14 9.5 23t22.5 9h64q13 0 22.5-9t9.5-23v-63z"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Items Exceeding the Expiration Date</h3>
                        <p class="text-3xl">{{ countExpired }}</p>
                    </div>
                </div>
            </div>
            <div class="my-4">
                <div class="bg-zinc-300 relative p-4 overflow-x-auto md:overflow-hidden shadow-md rounded-md">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="products"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }"
                    />
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

    :deep(table.dataTable tbody > tr > td:nth-child(1)) {
            text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(3)) {
            text-align: left !important;
    }
</style>