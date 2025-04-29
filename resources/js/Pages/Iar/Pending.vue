<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { computed, onMounted, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Refresh from '@/Components/Buttons/Refresh.vue';
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import useAuthPermission from '@/Composables/useAuthPermission';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const props = defineProps({
        iar: Object,
        lastId: Number,
    });

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);


    const API_BASE_URL = 'http://192.168.2.16/pgso-pms/api/fetch-iar';
    const newTransactionsList = ref([]);
    const fetchArchives = async (id) => {
        try {
            const response = await axios.get(`${API_BASE_URL}/ ${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                timeout: 10000
            });

            if (!response.data) {
                throw new Error('No data received from server');
            }

            newTransactionsList.value = response.data;

            const saveResponse = await axios.post('iar/collect-transactions', {
                param: newTransactionsList.value
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (newTransactionsList.value) {
                Swal.fire({
                    title: 'Success',
                    text: newTransactionsList.value.length + ' new transaction',
                    icon: 'success',
                    confirmButtonText: 'OK',
                });
            }

            return saveResponse;
        } catch (error) {
            const errorMessage = error.response?.data?.message || 
                            error.message || 
                            'Failed to fetch IAR Transactions';

            Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK',
            });
            
            console.error('Error:', {
                message: errorMessage,
                status: error.response?.status,
                url: error.config?.url
            });

            throw {
                userMessage: 'Failed to load IAR Transactions data. Please try again later.',
                technicalMessage: errorMessage,
                code: error.response?.status || 'NETWORK_ERROR'
            };
        }
    }

    const columns = [
        {
            data: 'airId',
            title: 'Inspection and Acceptance No#',
            width: '10%'
        },
        {
            data: 'poId',
            title: 'Purchase Order No#',
            width: '15%'
        },
        {
            data: 'supplier',
            title: 'Supplier',
            width: '35%'
        },
        {
            data: 'date',
            title: 'Created At',
            width: '15%'
        },
        {
            data: 'status',
            title: 'Transaction Status',
            width: '15%',
            render: (data, type, row) => {
                return `
                <span class="${data === 'Pending' 
                    ? 'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-300' 
                    : ''}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: null,
            title: 'Action',
            width: '10%',
            render: '#action',
        },
    ];

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

</script>

<template>
    <Head title="Inspection and Acceptance" />
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
                            Inspection and Acceptance
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('iar')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Receiving
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <button v-if="hasAnyRole(['Developer']) || hasPermission('collect-iar-transactions')" @click="fetchArchives(props.lastId)"
                            class="flex items-center justify-center text-white bg-gradient-to-r from-indigo-400 via-indigo-600 to-indigo-800 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
                            <svg class="w-6 h-6 mr-2" fill="none" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 32 32">
                                <path fill="currentColor" d="M16 7L6 17l1.41 1.41L15 10.83V28H2v2h13a2 2 0 0 0 2-2V10.83l7.59 7.58L26 17Z"/>
                                <path fill="currentColor" d="M6 8V4h20v4h2V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4Z"/>
                            </svg>
                            <span>Get Transactions from AssetPRO</span>
                        </button>
                        <!-- <Refresh v-if="hasAnyRole(['Developer']) || hasPermission('collect-iar-transactions')" :href="route('iar.collect.transactions')">Get Transactions from AssetPRO </Refresh> -->
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 w-full bg-white shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="props.iar"
                        :options="{ 
                            paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <ViewButton v-if="hasAnyRole(['Developer']) || hasPermission('view-iar-transaction')" :href="route('iar.particular', { iar: props.cellData.id})" tooltip="View"/>
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

    :deep([data-v-92cfd9dc] table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
    }
</style>