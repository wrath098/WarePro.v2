<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, computed, reactive, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import Print from '@/Components/Buttons/Print.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    
    const props = defineProps({
        ppmpTransaction: Object,
        ppmp: Object,
        types: Object,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isPrintModalOpen = computed(() => modalState.value === 'print');

    const printPpmp = reactive({
        transactionNo: '',
        printType: '',
        qtyAdjust: 100,
        threshold: 100,
    });

    const openPrintModal = (transactId) => {
        printPpmp.transactionNo = transactId;
        modalState.value = 'print';
    }

    const submitPrint = () => {
        const queryString = new URLSearchParams(printPpmp).toString();
        const url = `../pdf/draft-adjusted-ppmp-list?${queryString}`;
        window.open(url, '_blank');
        window.location.reload();
    };

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
            data: 'ppmp_code',
            title: 'Transaction No#',
            width: '12%'
        },
        {
            data: 'requestee.office_code',
            title: 'Office Code',
            width: '10%'
        },
        {
            data: 'requestee.office_name',
            title: 'Office Name',
            width: '25%',
        },
        {
            data: 'ppmp_year',
            title: 'PPMP for Year',
            width: '10%'
        },
        {
            data: 'ppmp_type',
            title: 'Type',
            width: '8%',
            render: function(data) {
                return data 
                    ? data == 'individual' 
                        ?'Office'
                        : 'Emergency'
                    : '';
            }
        },
        {
            data: 'updater.name',
            title: 'Updated By',
            width: '20%'
        },
        {
            data: null,
            title: 'Action',
            width: '15%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="PPMP" />
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
                            Project Procurement Management Plan
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('indiv.ppmp.type', { type: 'individual' , status: 'draft'})" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Office's PPMP List
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                {{ props.ppmp.status }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-white shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="ppmpTransaction"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }"> 
                            <template #action="props">
                                <ul v-if="ppmp.status == 'Draft'">
                                    <li v-if="hasPermission('print-office-ppmp') ||  hasAnyRole(['Developer'])">
                                        <button v-if="props.cellData.ppmp_type == 'individual'"  @click="openPrintModal(props.cellData.id)" title="Print">
                                            <svg class="w-6 h-6 text-gray-800 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                                            </svg>
                                        </button>
                                    </li>
                                </ul>

                                <ul v-if="ppmp.status == 'Approved'">
                                    <li v-if="hasPermission('print-office-ppmp') ||  hasAnyRole(['Developer'])">
                                        <div v-if="props.cellData.ppmp_type == 'individual'">
                                            <details class="group">
                                                <summary class="flex items-center justify-center gap-2 p-2 marker:content-none hover:cursor-pointer bg-gray-700 hover:bg-gray-900 rounded-md">
                                                    <span class="flex gap-2 text-sm text-gray-100">Print</span>
                                                    <svg class="w-3 h-3 text-gray-100 transition group-open:rotate-90" xmlns="http://www.w3.org/2000/svg"
                                                        width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z">
                                                        </path>
                                                    </svg>
                                                </summary>

                                                <article class="pb-2">
                                                    <ul class="flex flex-col">
                                                        <li class="my-1 rounded-md bg-gray-300 hover:bg-gray-600  hover:text-white">
                                                            <a :href="route('generatePdf.ApprovedOfficePpmp', {type: 'original', ppmp: props.cellData.id})" target="_blank">
                                                                Original Qty
                                                            </a>
                                                        </li>
                                                        <li v-if="props.cellData.qty_adjustment < 1" class="my-1 rounded-md bg-gray-300 hover:bg-gray-600  hover:text-white">
                                                            <a :href="route('generatePdf.ApprovedOfficePpmp', {type: 'adjusted', ppmp: props.cellData.id})" target="_blank">
                                                                Adjustment Qty
                                                            </a>
                                                        </li>
                                                        <li v-if="props.cellData.tresh_adjustment < 1" class="my-1 rounded-md bg-gray-300 hover:bg-gray-600  hover:text-white">
                                                            <a :href="route('generatePdf.ApprovedOfficePpmp', {type: 'threshold', ppmp: props.cellData.id})" target="_blank">
                                                                Maximum Allowed Qty
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </article>
                                            </details>
                                        </div>
                                        <div v-else>
                                            <Print :href="route('generatePdf.emergencyPpmp', { ppmp: props.cellData.id})" tooltip="Print" />
                                        </div>
                                    </li>
                                </ul>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isPrintModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitPrint">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Print Office Project Procurement Management Plan</h3>
                        <p class="text-sm text-gray-500">
                            Default setting is 'Original Office PPMP' type with '100' Office Maximum Allowed Quantity.
                        </p>
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]">PPMP Version Type: <span class="text-sm text-gray-500">Select a version type to determine how the adjustments will be applied.</span></p>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="printPpmp.printType" name="selectedVersion" id="selectedVersion" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select the version type</option>
                                    <option value="original">Original Office PPMP</option>
                                    <option value="adjustment">Adjusted Office PPMP</option>
                                </select>
                                <label for="selectedVersion" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select Version Type</label>
                            </div>
                            <div v-if="printPpmp.printType" >
                                <div v-if="printPpmp.printType == 'adjustment'" class="mt-5">
                                    <p class="text-sm text-[#86591e]">Adjustment to the Original Office PPMP: <span class="text-sm text-gray-500">This will adjust the quantity of each item requested by the various offices.</span></p>
                                    <div class="relative z-0 w-full group my-2">
                                        <input v-model="printPpmp.qtyAdjust" type="number" min="50" max="99" name="qtyAdjust" id="qtyAdjust" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                        <label for="qtyAdjust" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Value must be within 50 to 99</label>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Office Maximum Allowed Quantity: <span class="text-sm text-[#666666]">(Use this setting to define quantity limits, in percentage terms, for each item requested across offices.)</span></p>
                                    <div class="relative z-0 w-full group my-2">
                                        <input v-model="printPpmp.threshold" type="number" min="50" max="100" name="threshold" id="threshold" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                        <label for="threshold" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Value must be within 50 to 100</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Print 
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
    .upload-area {
        border: 2px dashed #007BFF;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }
    .upload-area:hover {
        border-color: #08396d;
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

    :deep([data-v-0737ed87] table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }

    :deep([data-v-0737ed87] table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
    }
</style>