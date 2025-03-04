<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, computed, reactive, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import Copy from '@/Components/Buttons/Copy.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import { Inertia } from '@inertiajs/inertia';
    import Swal from 'sweetalert2';
    
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
    const isCopyModalOpen = computed(() => modalState.value === 'copy');

    const filteredYears = ref([]);
    const makeCopy = reactive({
        selectedType: '',
        selectedYear: '',
        qtyAdjust: '',
    });

    const onTypeChange = (context) => {
        const type = props.types.find(typ => typ.ppmp_type === context.selectedType);
        filteredYears.value = type ? type.years : [];
        makeCopy.selectedYear = '';
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
    const submitCopy = () => submitForm('copy-ppmp', makeCopy);

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
            width: '15%'
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
            data: 'ppmp_version',
            title: 'Version',
            width: '5%'
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
                <ol v-if="props.ppmp.status == 'Draft'">
                    <li class="flex flex-col lg:flex-row justify-center items-center">
                        <button @click="showModal('copy')" class="flex items-center rounded-md text-white bg-gradient-to-r from-indigo-500 via-indigo-600 to-indigo-700 hover:bg-gradient-to-br hover:text-gray-200 p-1 group">
                            <svg class="w-5 h-5 text-gray-100 group-hover:text-gray-200" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M5.503 4.627L5.5 6.75v10.504a3.25 3.25 0 0 0 3.25 3.25h8.616a2.251 2.251 0 0 1-2.122 1.5H8.75A4.75 4.75 0 0 1 4 17.254V6.75c0-.98.627-1.815 1.503-2.123ZM17.75 2A2.25 2.25 0 0 1 20 4.25v13a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-13A2.25 2.25 0 0 1 8.75 2h9Zm0 1.5h-9a.75.75 0 0 0-.75.75v13c0 .414.336.75.75.75h9a.75.75 0 0 0 .75-.75v-13a.75.75 0 0 0-.75-.75Z"/>
                            </svg>
                            <span class="mx-1 text-gray-100 text-sm group-hover:text-gray-200">Make A Copy</span>
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
                        :data="ppmpTransaction"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <Print :href="route('generatePdf.IndividualPpmp', { ppmp: props.cellData.id})" tooltip="Initial" />
                                <Print v-if="props.cellData.tresh_adjustment > 0 && props.cellData.tresh_adjustment < 1" :href="route('generatePdf.IndividualPpmp.withAdjustment', { ppmp: props.cellData.id})" tooltip="Adjusted" />
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
        <Modal :show="isCopyModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitCopy">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>                                            
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Make A Copy</h3>
                            <p class="text-sm text-gray-500"> Enter the details to create a copy of the Individual PPMP and adjust its original quantity. This will serve as the threshold for releasing items per office.</p>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]">PPMP Information</p>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="makeCopy.selectedType" @change="onTypeChange(makeCopy)" name="selectedType" id="selectedType" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select the PPMP Type</option>
                                        <option v-for="type in props.types" :key="type.ppmp_type" :value="type.ppmp_type">{{ type.ppmp_type }}</option>
                                    </select>
                                    <label for="selectedType" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group" v-if="filteredYears.length">
                                    <select v-model="makeCopy.selectedYear" name="selectedYear" id="selectedYear" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select the PPMP Year</option>
                                        <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">{{ year.ppmp_year }}</option>
                                    </select>
                                    <label for="selectedYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Year</label>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]"> Quantity Adjustment: <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span></p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="makeCopy.qtyAdjust" type="number" min="50" max="100" name="qtyAdjust" id="qtyAdjust" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="qtyAdjust" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Qty Adjustment</label>
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