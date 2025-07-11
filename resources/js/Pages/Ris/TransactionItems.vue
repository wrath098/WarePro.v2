<script setup>
import EditButton from '@/Components/Buttons/EditButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    transactions: Object,
    risInfo: Object
});

const page = usePage();
const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

const isLoading = ref(false);
const modalState = ref(false);
const closeModal = () => { modalState.value = false; }
const isEditModalOpen = computed(() => modalState.value === 'edit');
const isParticularModalOpen = computed(() => modalState.value === 'particular');

const editRis = useForm({
    risNo: '',
    issuedTo: '',
    oldData: ''
});

const editRisParticular = useForm({
    risParticularId: '',
    stockNo: '',
    proDesc: '',
    requestedQty: '',
});

const openEditModal = (ris) => {
    editRis.risNo = ris.risNo;
    editRis.issuedTo = ris.issuedTo;
    editRis.oldData = ris;
    modalState.value = 'edit';
}

const openEditParticularModal = (particular) => {
    editRisParticular.risParticularId = particular.id;
    editRisParticular.stockNo = particular.product_details.prod_newNo;
    editRisParticular.proDesc = particular.product_details.prod_desc;
    editRisParticular.requestedQty = particular.qty;
    modalState.value = 'particular';
}

const columns = [
    {
        data: 'product_details.prod_newNo',
        title: 'Stock No#',
        width: '15%',
    },
    {
        data: 'product_details.prod_desc',
        title: 'Product Description',
        width: '50%',
    },
    {
        data: 'unit',
        title: 'Unit of Measurement',
        width: '15%',
    },
    {
        data: 'qty',
        title: 'Requested Quantity',
        width: '10%',
    },
    {
        data: null,
        title: 'Action',
        width: '10%',
        render: '#action',
    },
];

const submitForm = (url, formData) => {
    isLoading.value = true;

    formData.post(url, {
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            if (errMessage.value) {
                Swal.fire({
                    title: 'Failed',
                    text: errMessage.value,
                    icon: 'error',
                });
            } else {
                formData.reset();
                Swal.fire({
                    title: 'Success',
                    text: message.value,
                    icon: 'success',
                }).then(() => closeModal());
            }
        },
        onError: (errors) => {
            isLoading.value = false;
            console.log('Error: ' + JSON.stringify(errors));
        },
    });
};

const submit = () => submitForm(route('update.ris'), editRis);
const submitParticular = () => submitForm(route('update.ris.particular'), editRisParticular);

</script>
<template>
    <Head title="Purchase Request" />
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
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('ris.display.logs')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Issuances
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                RIS No# {{ risInfo.risNo }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <button
                            @click="openEditModal(risInfo)"
                            :class="{ 'opacity-25': isLoading }" :disabled="isLoading"
                            class="flex items-center justify-center text-white bg-gradient-to-r from-sky-400 via-sky-600 to-sky-800 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
                            <span class="mr-2">Edit</span>
                            <svg class="w-6 h-6" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M21 12a1 1 0 0 0-1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h6a1 1 0 0 0 0-2H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-6a1 1 0 0 0-1-1m-15 .76V17a1 1 0 0 0 1 1h4.24a1 1 0 0 0 .71-.29l6.92-6.93L21.71 8a1 1 0 0 0 0-1.42l-4.24-4.29a1 1 0 0 0-1.42 0l-2.82 2.83l-6.94 6.93a1 1 0 0 0-.29.71m10.76-8.35l2.83 2.83l-1.42 1.42l-2.83-2.83ZM8 13.17l5.93-5.93l2.83 2.83L10.83 16H8Z"/>
                            </svg>
                        </button>
                    </li>
                </ol>
            </nav>
        </template>
        
        <div class="my-4 w-full mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-4 lg:gap-4">
                        <div class="w-full bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="bg-indigo-600 text-white p-4 flex justify-between rounded-t-md">
                                <div class="font-bold text-lg">Request and Issuances Information</div>
                            </div>

                            <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                <dl class="-my-3 divide-y divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">RIS No#</dt>
                                        <dd class="text-gray-700">: {{ risInfo.risNo }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">Office Requestee</dt>
                                        <dd class="text-gray-700">: {{ risInfo.officeRequestee }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">Other Details:</dt>
                                        <dd class="text-gray-700">: {{ risInfo.remarks }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">No. of Items Issued:</dt>
                                        <dd class="text-gray-700">: {{ risInfo.noOfItems }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">Issued to:</dt>
                                        <dd class="text-gray-700">: {{ risInfo.issuedTo }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">Issued By:</dt>
                                        <dd class="text-gray-700">: {{ risInfo.issuedBy }}</dd>
                                    </div>

                                    <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-2 sm:gap-4">
                                        <dt class="font-medium text-gray-900">Date Issued:</dt>
                                        <dd class="text-gray-700">: {{ risInfo.dateIssued }}</dd>
                                    </div>
                                </dl>
                            </div>                               
                        </div>
                        
                        <div class="w-full col-span-3 bg-white rounded-md shadow mt-5 lg:mt-0">
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg px-4 mt-5">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable
                                        class="display table-hover table-striped shadow-lg rounded-lg"
                                        :columns="columns"
                                        :data="props.transactions"
                                        :options="{  
                                            paging: true,
                                            searching: true,
                                            ordering: false
                                        }">
                                            <template #action="props">
                                                <EditButton @click="openEditParticularModal(props.cellData)" tooltip="Edit" />
                                            </template>
                                    </DataTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isEditModalOpen" @close="closeModal"> 
        <form @submit.prevent="submit">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Request and Issuance Details</h3>
                        <p class="text-sm text-gray-500">Enter the details you'd like to update.</p>
                        
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]">RIS Information: </p>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editRis.risNo" type="text" name="editRisNo" id="editRisNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                <label for="editRisNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">RIS Transaction No:</label>
                                <InputError class="mt-2" :message="editRis.errors.risNo" />
                            </div>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editRis.issuedTo" type="text" name="editIssuedTo" id="editIssuedTo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                <label for="editIssuedTo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Issued To:</label>
                                <InputError class="mt-2" :message="editRis.errors.issuedTo" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton>
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
    <Modal :show="isParticularModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitParticular">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Request and Issuance Details</h3>
                        <p class="text-sm text-gray-500">Enter the details you'd like to update.</p>
                        
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]">RIS Particular Information: </p>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editRisParticular.stockNo" type="text" name="risParticularStockNo" id="risParticularStockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required readonly/>
                                <label for="risParticularStockNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock No#:</label>
                                <InputError class="mt-2" :message="editRisParticular.errors.stockNo" />
                            </div>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editRisParticular.proDesc" type="text" name="risParticularDesc" id="risParticularDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required readonly/>
                                <label for="risParticularDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Particular Description:</label>
                                <InputError class="mt-2" :message="editRisParticular.errors.proDesc" />
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]">Requested Quantity: </p>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editRisParticular.requestedQty" type="number" name="risParticularQty" id="risParticularQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                <label for="risParticularQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Particular Quantity</label>
                                <InputError class="mt-2" :message="editRisParticular.errors.requestedQty" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton>
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

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }
</style>