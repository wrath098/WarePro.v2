<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { reactive, ref, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import PrintIcon from '@/Components/Buttons/PrintIcon.vue';
    import RecycleIcon from '@/Components/Buttons/RecycleIcon.vue';
    import ApprovedButton from '@/Components/Buttons/ApprovedButton.vue';
    import Swal from 'sweetalert2';


    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        pr: Object,
        particulars: Object,
        trashed: Object,
    });

    const formatDecimal = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isEditPPModalOpen = computed(() => modalState.value === 'edit');
    const isDropPrModalOpen = computed(() => modalState.value === 'drop');
    const isRestorePrModalOpen = computed(() => modalState.value === 'restore');
    const isApprovedPrModalOpen = computed(() => modalState.value === 'approved');
    const isFailedAllModalOpen = computed(() => modalState.value === 'failedAll');
    const isApprovedAllModalOpen = computed(() => modalState.value === 'approvedAll');

    const editParticular = reactive({
        partId: '',
        prodId:'',
        prodCode: '',
        prodDesc: '',
        prodPrice:'',
        prodMeasure: '',
        prodQty: '',
    });

    const openEditModal = (particular) => {
        editParticular.partId = particular.id;
        editParticular.prodId = particular.prod_id;
        editParticular.prodCode = particular.prodCode;
        editParticular.prodDesc = particular.revised_specs;
        editParticular.prodPrice = particular.unitPrice;
        editParticular.prodMeasure = particular.unitMeasure;
        editParticular.prodQty = particular.qty;
        modalState.value = 'edit';
    }

    const dropParticular = reactive({pId: '',});
    const restoreParticular = reactive({pId: ''});
    const approveParticular = reactive({pId: ''});
    const failedAllParticular = reactive({pId: ''});
    const approvedAllParticular = reactive({pId: ''});

    const openDropModal = (particular) => {
        dropParticular.pId = particular.id;
        modalState.value = 'drop';
    }

    const openRestoreModal = (particular) => {
        restoreParticular.pId = particular.id;
        modalState.value = 'restore';
    }

    const openApprovedModal = (particular) => {
        approveParticular.pId = particular.id;
        modalState.value = 'approved';
    }

    const openFailedAllModal = (transaction) => {
        failedAllParticular.pId = transaction;
        modalState.value = 'failedAll';
    }

    const openApprovedAllModal = (transaction) => {
        approvedAllParticular.pId = transaction;
        modalState.value = 'approvedAll';
    }

    const submitForm = (action, url, data) => {
        let method;

        switch (action) {
            case 'post':
                method = 'post';
                break;
            case 'put':
                method = 'put';
                break;
            case 'delete':
                method = 'delete';
                break;
            default:
                throw new Error('Invalid action specified');
        }

        isLoading.value = true;
        Inertia[method](url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
        });
    };

    const submitEdit = () => submitForm('put', '../particular/update', editParticular);
    const submitDrop = () => {
        const prParticular = dropParticular.pId;
        submitForm('delete', `../particular/trash/${prParticular}`, null)
    };

    const submitRestore = () => {
        const prParticular = restoreParticular.pId;
        submitForm('post', `../particular/restore/${prParticular}`, null)
    };

    const submitApproved = () => {
        const prParticular = approveParticular.pId;
        submitForm('put', `../particular/approve/${prParticular}`, null)
    };

    const submitFailedAll = () => {
        const prTransaction = failedAllParticular.pId;
        submitForm('put', `../particular/failedAll/${prTransaction}`, null)
    };

    const submitApprovedAll = () => {
        const prTransaction = approvedAllParticular.pId;
        submitForm('put', `../particular/approvedAll/${prTransaction}`, null)
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
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li class="after:content-[':'] after:ml-2 text-[#86591e]" aria-current="page">Transaction No.</li> 
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">{{ pr.pr_no }}</li> 
                    <li class="flex flex-col lg:flex-row">
                        <button @click="openApprovedAllModal(props.pr.id)" class="text-sm px-4 py-1 mx-1 my-1 lg:my-0 min-w-[120px] text-center text-white bg-indigo-600 border-2 border-indigo-600 rounded active:text-indigo-500 hover:bg-transparent hover:text-indigo-600 focus:outline-none focus:ring">
                            Accept All
                        </button>
                        <button @click="openFailedAllModal(props.pr.id)" class="text-sm px-4 py-1 mx-1 my-1 lg:my-0 min-w-[120px] text-center text-indigo-600 border-2 border-indigo-600 rounded hover:bg-indigo-600 hover:text-white active:bg-indigo-500 focus:outline-none focus:ring">
                            Reject All
                        </button>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="w-full md:w-3/12 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="flex-1 flex items-start justify-between bg-indigo-300 p-2 rounded-t-xl md:mb-10">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-lg justify-center font-semibold text-[#161555] mb-4">Purchase Request Information</h2>
                                </div>
                                <div class="flex items-center">
                                    <div class="rounded-full">
                                        <PrintIcon :href="route('generatePdf.PurchaseRequestDraft', { pr: pr.id})" target="_blank" class="bg-gray-50" ></PrintIcon>
                                    </div>
                                </div>
                            </div>

                            <div class="m-2">
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Purchase Request No.:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ pr.pr_no }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Consolidated PPMP No.:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ pr.ppmp_controller.ppmp_code }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Description:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{  pr.semester }} - {{ pr.qty_adjustment }}% <br> {{ pr.pr_desc }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Total Items Listed:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ pr.totalItems }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Total Amount:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ pr.formattedOverallPrice }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Create/Updated By:</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ pr.updater.name }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Status</dt>
                                            <dd class="text-gray-700 sm:col-span-2">
                                                <span :class="{
                                                    'bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-yellow-300': pr.pr_status === 'Draft',
                                                    'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': pr.pr_status === 'Approved',
                                                    }">
                                                    {{ pr.pr_status }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>                               
                            </div>
                        </div>
                        
                        <div class="mx-2 w-full md:w-9/12 bg-white rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-indigo-300 p-2 rounded-t-xl">
                                <h2 class="text-lg font-semibold text-[#171858] mb-4">Purchase Request Particulars</h2>
                            </div>
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg px-4 mt-5">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-50 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-3/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit of Measure</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Quantity</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Total Amount</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in particulars" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3">{{ particular.revised_specs }}</td>
                                                <td class="px-6 py-3">{{ particular.unitMeasure }}</td>
                                                <td class="px-6 py-3">{{ particular.qty }}</td>
                                                <td class="px-6 py-3">{{ formatDecimal(particular.unitPrice) }}</td>
                                                <td class="px-6 py-3">{{ formatDecimal(particular.qty * particular.unitPrice) }}</td>
                                                <td v-if="particular.status != 'approved'" class="px-6 py-3 text-center">
                                                    <EditButton @click="openEditModal(particular)" tooltip="Edit" />
                                                    <ApprovedButton @click="openApprovedModal(particular)" tooltip="Approved" />
                                                    <RemoveButton @click="openDropModal(particular)" tooltip="Failed" />
                                                </td>
                                                <td v-else class="px-6 py-3 text-center">
                                                    <span class='bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300'>
                                                        In Progress
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </DataTable>
                                </div>
                            </div>
                        </div>

                        <div class="w-full md:w-2/12 bg-white rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-red-300 p-2 rounded-t-xl">
                                <h2 class="text-lg font-semibold text-[#862020] mb-4">Trashed Particulars</h2>
                            </div>
                            <div class="overflow-x-auto bg-white shadow-md">
                                <table class="w-full text-sm text-gray-700">
                                    <thead class="bg-gray-200 text-gray-700 text-sm uppercase">
                                        <tr>
                                            <th class="w-2/3 px-6 py-3 text-left font-medium">Stock No.</th>
                                            <th class="w-1/3 px-6 py-3 text-left font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="trashed.length > 0" v-for="(particular, index) in trashed" :key="particular.id" class="border-b hover:bg-gray-50">
                                            <td class="px-6 py-4">{{ particular.prodCode }}</td>
                                            <td class="px-6 py-4 flex space-x-2">
                                                <RecycleIcon @click="openRestoreModal(particular)" tooltip="Restore"/>
                                            </td>
                                        </tr>
                                        <tr v-else class="text-gray-400">
                                            <td class="px-6 py-4 text-right">No Data Found</td>
                                            <td class="px-6 py-4"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isEditPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Quantity</h3>
                            <p class="text-sm text-gray-500"> Enter the quantity you want to update on the input field.</p>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500"> Product Information: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Code: </span>
                                    </div>
                                    <input v-model="editParticular.prodCode" type="text" id="code" class="mt-2 pl-20 p-2.5 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" readonly>
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Description: </span>
                                    </div>
                                    <textarea v-model="editParticular.prodDesc" type="text" id="description" class="pl-20 p-2.5 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500"></textarea>
                                </div>
                                <div class="md:flex">
                                    <div class="relative mt-1 md:w-1/2 md:mr-1">
                                        <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-600 text-sm font-semibold">Unit: </span>
                                        </div>
                                        <input v-model="editParticular.prodMeasure" type="text" id="unit" class="mt-2 pl-20 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    </div>
                                    <div class="relative mt-1 md:w-1/2 md:ml-1">
                                        <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-600 text-sm font-semibold">Price: </span>
                                        </div>
                                        <input v-model="editParticular.prodPrice" type="number" id="price" class="mt-2 pl-20 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Quantity: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">1st Qty: </span>
                                    </div>
                                    <input v-model="editParticular.prodQty" type="number" id="quantity" class="mt-2 pl-20 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
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
        <Modal :show="isDropPrModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDrop">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Product from the list. This action can't be undone.</p>
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
        <Modal :show="isRestorePrModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitRestore">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-green-600 w-16 h-16 mx-auto my-6" fill="currentColor" viewBox="0 -0.5 25 25" xmlns="http://www.w3.org/2000/svg">
                            <path d="m11.538 16.444-.211 5.178-.028.31-5.91-.408c-.37-.039-.696-.201-.943-.443-.277-.253-.501-.56-.654-.905l-.007-.017c-.095-.225-.167-.486-.202-.759l-.002-.015c-.009-.085-.014-.184-.014-.284 0-.223.026-.441.074-.65l-.004.019q.106-.521.165-.774c.102-.368.205-.667.324-.959l-.021.059q.239-.647.267-.743 1.099.167 7.164.392zm-5.447-8.245 2.533 5.333-2.068-1.294c-.536.606-1.051 1.269-1.524 1.964l-.044.069c-.352.503-.692 1.08-.986 1.682l-.034.077q-.338.743-.555 1.33c-.104.251-.194.548-.255.856l-.005.031-.056.295-2.673-5.023c-.15-.222-.243-.493-.253-.786v-.003c-.003-.039-.005-.085-.005-.131 0-.19.032-.372.091-.541l-.003.012.112-.253q.495-.886 1.604-2.641l-1.967-1.215zm17.32 7.275-2.641 5.051c-.109.268-.286.49-.509.652l-.004.003c-.172.136-.378.236-.602.286l-.01.002-.253.056q-.999.098-3.081.165l.112 2.311-3.236-5.164 2.971-5.094.098 2.434c.711.083 1.534.131 2.368.131.568 0 1.131-.022 1.687-.065l-.074.005c.875-.058 1.69-.224 2.462-.485l-.068.02zm-11.042-13.002q-.66.886-3.729 6.121l-4.457-2.631-.267-.165 3.166-5.009c.2-.298.49-.521.831-.632l.011-.003c.261-.097.562-.152.876-.152.088 0 .175.004.261.013l-.011-.001c.251.022.483.081.698.171l-.015-.006c.227.092.419.192.601.306l-.016-.009c.218.146.409.299.585.466l-.002-.002q.338.31.507.485t.507.555q.341.38.454.493zm9.216 4.319 2.983 5.108c.122.238.194.519.194.817 0 .09-.007.179-.019.266l.001-.01c-.058.392-.194.744-.393 1.052l.006-.01c-.133.199-.286.371-.461.518l-.004.003c-.158.137-.333.267-.517.383l-.018.01c-.194.115-.42.219-.656.301l-.027.008q-.429.155-.66.225t-.725.197l-.647.165q-.479-1.013-3.729-6.135l4.404-2.744zm-2.012-3.18 1.998-1.168-3.095 5.249-5.897-.281 2.125-1.21c-.355-.933-.71-1.702-1.109-2.443l.054.11c-.348-.671-.701-1.239-1.091-1.779l.028.041q-.485-.655-.908-1.126c-.204-.238-.42-.452-.652-.648l-.008-.007-.239-.183 5.695.014c.047-.005.101-.008.157-.008.24 0 .468.058.668.16l-.008-.004c.217.098.4.234.55.401l.001.002.155.211q.549.854 1.576 2.669z"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Restore Purchase Request Particular!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will restore the selected Product from the trash list. <br> Are you sure you want to restore?</p>
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
        <Modal :show="isApprovedPrModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitApproved">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will change the Product status to approved. This means that the product is for Purchase Order. <br> This action can't be undone.</p>
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
        <Modal :show="isFailedAllModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitFailedAll">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move All Draft to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will change all Product with draft status will be trashed. <br> This action can't be undone.</p>
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
        <Modal :show="isApprovedAllModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitApprovedAll">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Approved all Draft Items!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will change all Product with approved status. <br> This action can't be undone.</p>
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
    </AuthenticatedLayout>
    </div>
</template>
 