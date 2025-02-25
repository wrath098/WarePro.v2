<script setup>
    import { computed, onMounted, reactive, ref, watch } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import { DataTable } from 'datatables.net-vue3';
    import axios from 'axios';
    import { debounce } from 'lodash';
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
            title: 'Transaction #',
            width: '5%',
            render: function(data, type, row, meta) {
                return meta.row + 1;
            },
            className: 'text-center',
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
            title: 'Expiry Date',
        },
    ];
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Product Inventory</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Expiring Products</a></li>
                </ol>
            </nav>
        </template>
        <div class="py-8">
            <div class="grid grid-cols-1 gap-4 px-4 sm:grid-cols-2 sm:px-8">
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-yellow-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                            <path fill="currentColor" d="M19.59 15.86L12.007 1.924C11.515 1.011 10.779.5 9.989.5c-.79 0-1.515.521-2.016 1.434L.409 15.861c-.49.901-.544 1.825-.138 2.53c.405.707 1.216 1.109 2.219 1.109h15.02c1.003 0 1.814-.402 2.22-1.108c.405-.706.351-1.619-.14-2.531ZM10 4.857c.395 0 .715.326.715.728v6.583c0 .402-.32.728-.715.728a.721.721 0 0 1-.715-.728V5.584c0-.391.32-.728.715-.728Zm0 11.624c-.619 0-1.11-.51-1.11-1.14c0-.63.502-1.141 1.11-1.141c.619 0 1.11.51 1.11 1.14c0 .63-.502 1.141-1.11 1.141Z"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Total Expiring Items</h3>
                        <p class="text-3xl">{{ countExpired }}</p>
                    </div>
                </div>
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 1024 1023" stroke="currentColor">
                            <path fill="currentColor" d="m882 566l142 160l-174 47l46 122l-165-46l-59 174l-158-105l-74 105l-67-128H192l16-116H52l73-173L0 536l95-76L1 324l191-47V128l87 57L324 0l161 135L633 0l32 115l115-64l28 170l152-29l-68 154l131 93zM576 288q0-14-9.5-23t-22.5-9h-64q-13 0-22.5 9t-9.5 23v256q0 13 9.5 22.5T480 576h64q13 0 22.5-9.5T576 544V288zm0 384q0-14-9.5-23t-22.5-9h-64q-13 0-22.5 9t-9.5 23v63q0 14 9.5 23t22.5 9h64q13 0 22.5-9t9.5-23v-63z"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Total Expired Items</h3>
                        <p class="text-3xl">{{ countExpiring }}</p>
                    </div>
                </div>
            </div>
            <div class="mt-8 sm:px-6 lg:px-8">
                <div class="bg-white p-2 lg:overflow-hidden shadow-md sm:rounded-lg">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
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
    </div>
</template>
<style scoped>
:deep(table.dataTable) {
    border: 2px solid #555555;
}

:deep(table.dataTable thead > tr > th) {
    background-color: #555555;
    text-align: center;
    color: aliceblue;
}

:deep(table.dataTable tbody > tr > td) {
    text-align: center;
}
</style>