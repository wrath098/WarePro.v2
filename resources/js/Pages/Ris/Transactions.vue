<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { computed, onMounted } from 'vue';
    import { DataTable } from 'datatables.net-vue3';
    import Swal from 'sweetalert2';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';

    const page = usePage();

    const props = defineProps({
        transactions: Object,
    });

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
            data: 'risNo',
            title: 'RIS No.',
            width: '15%',
        },
        {
            data: 'officeRequestee',
            title: 'Office <br> (Requestee)',
            width: '12%',
        },
        {
            data: 'requesteeRemarks',
            title: 'Office <br> (Remarks)',
            width: '12%',
        },
        {
            data: 'noOfItems',
            title: 'No Of Items',
            width: '10%',
        },
        {
            data: 'issuedTo',
            title: 'Issued To',
            width: '20%',
        },
        {
            data: 'releasedBy',
            title: 'Issued By',
            width: '20%',
        },
        {
            data: null,
            title: 'Action',
            width: '11%',
            render: '#action',
        },
        
    ];
</script>

<template>
    <Head title="Requisition and Issuances" />
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
                            Request and Issuances
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('ris.display.logs')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Issuances
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="w-full mx-auto sm:mx-4 lg:mx-0 mt-4">
            <div class="bg-white shadow-md sm:rounded-lg p-4">
                <div class="relative overflow-x-auto md:overflow-hidden">
                    <DataTable 
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="props.transactions"
                        :options="{  
                            paging: true,
                            searching: true,
                            ordering: false,
                            order: [[0, 'desc']]
                        }"
                    >
                        <template #action="props">
                            <ViewButton :href="route('ris.show.items', { transactionId: props.cellData.risNo, issuedTo: props.cellData.issuedTo })" tooltip="View"/>
                        </template>
                    </DataTable>
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

    :deep(table.dataTable tbody > tr > td:nth-child(5)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(6)) {
        text-align: left !important;
    }
</style>