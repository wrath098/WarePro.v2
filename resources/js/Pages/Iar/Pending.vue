<script setup>
    import { Head, router, useForm } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Swal from 'sweetalert2';
    import axios from 'axios';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const isLoading = ref(false);

    const props = defineProps({
        iar: Object,
        lastId: Number,
    });

    const modalState = ref(null);
    const closeModal = () => { modalState.value = null; }
    const isRetrieveTransactionModalOpen = computed(() => modalState.value === 'retrieve');

    const openRetrieveTransactionModal = () => {
        modalState.value = 'retrieve';
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() - 0;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const retrievedIar = useForm({
        iar_no: '',
        year: '',
    });

    const API_BASE_URL = 'http://192.168.2.16/pgso-pms/api/fetch-iar';
    const newTransactionsList = ref([]);
    const fetchArchives = async (id) => {
        isLoading.value = true;

        try {
            const response = await axios.get(`${API_BASE_URL}/ ${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                timeout: 10000
            });

            if (!response.data) {
                isLoading.value = false;
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
                }).then(() => {
                    isLoading.value = false;
                    router.visit(route('iar'), {
                        preserveScroll: true,
                        preserveState: false,
                    });
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

            isLoading.value = false;

            throw {
                userMessage: 'Failed to load IAR Transactions data. Please try again later.',
                technicalMessage: errorMessage,
                code: error.response?.status || 'NETWORK_ERROR'
            };
        }
    }

    const API_RETRIEVE_BASE_URL = 'http://192.168.2.16/pgso-pms/api/unfetch-iar';
    const submitRetrieveTransaction = async () => {
        isLoading.value = true;

        try {
            const retrieveResponse = await axios.get(API_RETRIEVE_BASE_URL, {
                params: {
                    iar_no: retrievedIar.iar_no,
                    year: retrievedIar.year,
                },
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                timeout: 10000
            });

            const transactionData = retrieveResponse.data;

            if (!transactionData || Object.keys(transactionData).length === 0) {
                throw new Error('No transaction data received from server.');
            }

            const saveResponse = await axios.post('iar/store-retrieve-transaction', {
                param: transactionData
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (!saveResponse.data.success) {
                throw new Error(saveResponse.data.message || 'Failed to store transaction.');
            }

            await Swal.fire({
                title: 'Success',
                text: saveResponse.data.message || 'Transaction retrieved successfully!',
                icon: 'success',
                confirmButtonText: 'OK',
            });

            closeModal();
            router.visit(route('iar'), {
                preserveScroll: true,
                preserveState: false,
            });

            return saveResponse.data;

        } catch (error) {
            const errorMessage = error.response?.data?.message || error.message || 'Failed to retrieve or store transaction.';

            await Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK',
            });

            throw {
                userMessage: 'Failed to load or save IAR Transaction. Please try again later.',
                technicalMessage: errorMessage,
                code: error.response?.status || 'NETWORK_ERROR'
            };

        } finally {
            isLoading.value = false;
        }
    };

    const columns = [
        {
            data: 'airNo',
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
                    ? 'bg-gray-300 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-400' 
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
                            :class="{ 'opacity-25': isLoading }" :disabled="isLoading"
                            class="flex items-center justify-center text-white bg-gradient-to-r from-indigo-400 via-indigo-600 to-indigo-800 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
                            <svg class="w-6 h-6 mr-2" fill="none" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 32 32">
                                <path fill="currentColor" d="M16 7L6 17l1.41 1.41L15 10.83V28H2v2h13a2 2 0 0 0 2-2V10.83l7.59 7.58L26 17Z"/>
                                <path fill="currentColor" d="M6 8V4h20v4h2V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4Z"/>
                            </svg>
                            <span>Get Transactions from AssetPRO</span>
                        </button>
                        <button @click="openRetrieveTransactionModal" class="lg:ms-2 mt-2 lg:mt-0 flex items-center justify-center text-white bg-gradient-to-r from-green-400 via-green-600 to-green-800 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
                            <svg class="w-6 h-6 mr-2" aria-hidden="true" fill="none"  xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M9 19.4q-2.025-.8-3.338-2.512T4.076 13H6.1q.225 1.325.975 2.425T9 17.2zm3.5 2.6q-.625 0-1.062-.437T11 20.5v-6q0-.625.438-1.062T12.5 13h2.2q.375 0 .713.175t.537.5l.55.825h4q.625 0 1.063.438T22 16v4.5q0 .625-.437 1.063T20.5 22zm-9-11q-.625 0-1.062-.437T2 9.5v-6q0-.625.438-1.062T3.5 2h2.2q.375 0 .713.175t.537.5l.55.825h4q.625 0 1.063.438T13 5v4.5q0 .625-.437 1.063T11.5 11zM18 12q0-1.625-.8-3.012T15 6.8V4.6q2.275.925 3.638 2.938T20 12z"/>
                            </svg>
                            <span>Retrieve Transaction</span>
                        </button>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 w-full bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <div v-if="isLoading" class="absolute bg-white text-indigo-800 bg-opacity-60 z-10 h-full w-full flex items-center justify-center">
                        <div class="flex items-center">
                        <span class="text-3xl mr-4">Loading</span>
                        <svg class="animate-spin h-8 w-8 text-indigo-800" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        </div>
                    </div>

                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
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
    <Modal :show="isRetrieveTransactionModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitRetrieveTransaction(retrievedIar)">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" fill="currentColor"  xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M9 19.4q-2.025-.8-3.338-2.512T4.076 13H6.1q.225 1.325.975 2.425T9 17.2zm3.5 2.6q-.625 0-1.062-.437T11 20.5v-6q0-.625.438-1.062T12.5 13h2.2q.375 0 .713.175t.537.5l.55.825h4q.625 0 1.063.438T22 16v4.5q0 .625-.437 1.063T20.5 22zm-9-11q-.625 0-1.062-.437T2 9.5v-6q0-.625.438-1.062T3.5 2h2.2q.375 0 .713.175t.537.5l.55.825h4q.625 0 1.063.438T13 5v4.5q0 .625-.437 1.063T11.5 11zM18 12q0-1.625-.8-3.012T15 6.8V4.6q2.275.925 3.638 2.938T20 12z"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Retrieve Transaction</h3>
                        <p class="text-sm text-zinc-700"> Retrieve a transaction that didn’t appear in the main transaction list.</p>
                        <div class="mt-2">
                            <div class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037] mb-2"> Transaction Information</p>
                                <div class="relative z-0 w-full mb-5 group">
                                    <input v-model="retrievedIar.iar_no" type="text" name="iar_no" id="iar_no" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                    <label for="iar_no" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">IAR Number/Code</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="retrievedIar.year" name="year" id="year" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                        <option value="" disabled selected>Select year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                    <label for="year" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Confirm 
                </SuccessButton>

                <DangerButton @click="closeModal"> 
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Cancel
                </DangerButton>
            </div>
        </form>
    </Modal>
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

    :deep(table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
    }
</style>