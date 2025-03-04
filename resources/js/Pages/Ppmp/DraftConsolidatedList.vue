<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { reactive, ref, computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import Generate from '@/Components/Buttons/Generate.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        ppmp: Object,
        transactions: Object,
        individualList: Object,
    });

    const edit = reactive({
        ppmpId: '',
        user: props.user,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isConsolidateModalOpen = computed(() => modalState.value === 'copy');
    const isDropPpmpModalOpen = computed(() => modalState.value === 'drop');

    const filteredYears = ref([]);
    const filteredVersion = ref([]);
    const generateConsolidated = reactive({
        selectedType: '',
        selectedYear: '',
        selectedVersion: '',
        selectedCopy: '',
        priceAdjust:''
    });

    const openDropPpmpModal = (ppmp) => {
        edit.ppmpId = ppmp.id;
        modalState.value = 'drop';
    };

    const onTypeChange = (context) => {
        const type = props.individualList.find(typ => typ.ppmp_type === context.selectedType);
        filteredYears.value = type ? type.years : [];
        generateConsolidated.selectedYear = '';
    };

    const onYearChange = (context) => {
        const type = props.individualList.find(typ => typ.ppmp_type === context.selectedType);
        const year = type.years.find(yer => yer.ppmp_year === context.selectedYear);
        filteredVersion.value = year ? Object.entries(year.versions).map(([key, value]) => ({ id: key, version: value })) : [];
        generateConsolidated.selectedVersion = '';
    };

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
                isLoading.value = false;
            },
        });
    };
    const submitConsolidated = () => submitForm('create-consolidated', generateConsolidated);
    const submitDropPpmp = () => submitForm('drop', edit);

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
            data: 'code',
            title: 'Transaction No#',
            width: '15%'
        },
        {
            data: 'ppmpYear',
            title: 'PPMP Year',
            width: '10%'
        },
        {
            data: 'version',
            title: 'Version',
            width: '10%',
        },
        {
            data: 'priceAdjust',
            title: 'Price Adjustment',
            width: '10%'
        },
        {
            data: 'qtyAdjust',
            title: 'Quantity Adjustment',
            width: '5%'
        },
        {
            data: 'updatedBy',
            title: 'Updated By',
            width: '20%'
        },
        {
            data: 'createdAt',
            title: 'Updated At',
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
                            <a :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Consolidated PPMP List
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
                <ol v-if="props.ppmp.status == 'Draft'">
                    <li class="flex flex-col lg:flex-row justify-center items-center">
                        <button @click="showModal('copy')" class="flex items-center rounded-md text-white bg-gradient-to-r from-indigo-500 via-indigo-600 to-indigo-700 hover:bg-gradient-to-br hover:text-gray-200 p-1 group">
                            <svg class="w-5 h-5 text-gray-100 group-hover:text-gray-200" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M3 7V5c0-1.1.9-2 2-2h2m10 0h2c1.1 0 2 .9 2 2v2m0 10v2c0 1.1-.9 2-2 2h-2M7 21H5c-1.1 0-2-.9-2-2v-2"/>
                                    <rect width="7" height="5" x="7" y="7" rx="1"/>
                                    <rect width="7" height="5" x="10" y="12" rx="1"/>
                                </g>
                            </svg>
                            <span class="mx-1 text-gray-100 text-sm group-hover:text-gray-200">Consolidate</span>
                        </button>
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
                        :data="transactions"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <span v-if="ppmp.status == 'Draft'">
                                    <ViewButton :href="route('conso.ppmp.show', { ppmpTransaction: props.cellData.id })" tooltip="View"/>
                                    <RemoveButton @click="openDropPpmpModal(props.cellData)" tooltip="Trash"/>
                                </span>
                                <span v-if="ppmp.status == 'Approved'">
                                    <Print :href="route('generatePdf.ApprovedConsolidatedPpmp', { ppmp: props.cellData.id})" tooltip="Print" />
                                </span>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isConsolidateModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitConsolidated">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Consolidate PPMP</h3>
                        <p class="text-sm text-gray-500"> Enter the details to generate a consolidated PPMP you wish to consolidate.</p>
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]">PPMP Information: </p>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="generateConsolidated.selectedType" @change="onTypeChange(generateConsolidated)" name="selectedType" id="selectedType" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Type</option>
                                    <option v-for="type in props.individualList" :key="type.ppmp_type" :value="type.ppmp_type">{{ type.ppmp_type }}</option>
                                </select>
                                <label for="selectedType" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                            </div>
                            <div class="relative z-0 w-full my-3 group" v-if="filteredYears.length">
                                <select v-model="generateConsolidated.selectedYear" @change="onYearChange(generateConsolidated)" name="selectedYear" id="selectedYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Year</option>
                                    <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">{{ year.ppmp_year }}</option>
                                </select>
                                <label for="selectedYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Year</label>
                            </div>
                            <div class="relative z-0 w-full my-3 group" v-if="filteredVersion.length">
                                <select v-model="generateConsolidated.selectedVersion" name="selectedVersion" id="selectedVersion" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Version</option>
                                    <option v-for="version in filteredVersion" :key="version.version" :value="version.version">{{ version.version }}</option>
                                </select>
                                <label for="selectedVersion" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Version</label>
                            </div>
                            <div class="relative z-0 w-full my-3 group" v-if="filteredVersion.length">
                                <select v-model="generateConsolidated.selectedCopy" name="selectedCopy" id="selectedCopy" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Copy</option>
                                    <option :value="'Initial'">Initial</option>
                                    <option :value="'Adjusted'">Adjusted</option>
                                </select>
                                <label for="selectedCopy" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Copy</label>
                            </div>
                        </div>
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]"> Price Adjustment: <span class="text-sm text-[#8f9091]">Value: 100% - 120%</span></p>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="generateConsolidated.priceAdjust" type="number" min="100" max="120" name="priceAdjust" id="priceAdjust" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="priceAdjust" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Percentage</label>
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
    <Modal :show="isDropPpmpModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitDropPpmp">
            <input type="hidden" v-model="edit.ppmpId">
            <div class="bg-gray-100 h-auto">
                <div class="bg-white p-6  md:mx-auto">
                    <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Drop PPMP?</h3>
                        <p class="text-gray-600 my-2">Confirming this action will permanently remove the selected PPMP including its particulars into the list. This action cannot be redo.</p>
                        <p> Please confirm if you wish to proceed.  </p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
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
                    </div>
                </div>
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
