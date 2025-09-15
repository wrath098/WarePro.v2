<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { reactive, ref, computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import ApprovedButton from '@/Components/Buttons/ApprovedButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';

    const {hasAnyRole, hasPermission} = useAuthPermission();
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
    const closeModal = () => { modalState.value = null; }
    const isEditPPModalOpen = computed(() => modalState.value === 'edit');
    const isDropPrModalOpen = computed(() => modalState.value === 'drop');
    const isApprovedPrModalOpen = computed(() => modalState.value === 'approved');
    const isFailedAllModalOpen = computed(() => modalState.value === 'failedAll');
    const isApprovedAllModalOpen = computed(() => modalState.value === 'approvedAll');

    const editParticular = useForm({
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

    const dropParticular = useForm({pId: '',});
    const approveParticular = useForm({pId: ''});
    const failedAllParticular = useForm({pId: ''});
    const approvedAllParticular = useForm({pId: ''});

    const openDropModal = (particular) => {
        dropParticular.pId = particular.id;
        modalState.value = 'drop';
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

    const submitForm = (method, url, formData) => {
        isLoading.value = true;

        formData[method](url, {
            preserveScroll: true,
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    }).then(() => {
                        isLoading.value = false;
                    });
                } else {
                    formData.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        isLoading.value = false;
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.info('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submitEdit = () => submitForm('put', '../particular/update', editParticular);
    const submitDrop = () => {
        const prParticular = dropParticular.pId;
        submitForm('delete', `../particular/trash/${prParticular}`, dropParticular)
    };

    const submitApproved = () => {
        const prParticular = approveParticular.pId;
        submitForm('put', `../particular/approve/${prParticular}`, approveParticular)
    };

    const submitFailedAll = () => {
        const prTransaction = failedAllParticular.pId;
        submitForm('put', `../particular/failedAll/${prTransaction}`, failedAllParticular)
    };

    const submitApprovedAll = () => {
        const prTransaction = approvedAllParticular.pId;
        submitForm('put', `../particular/approvedAll/${prTransaction}`, approvedAllParticular)
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
            data: 'prodCode',
            title: 'Stock No#',
            width: '10%'
        },
        {
            data: 'revised_specs',
            title: 'Description',
            width: '33%',
        },
        {
            data: 'unitMeasure',
            title: 'Unit Of Measurement',
            width: '10%'
        },
        {
            data: 'qty',
            title: 'Quantity',
            width: '10%'
        },
        {
            data: function(row) {
                return `₱ ${formatDecimal(row.unitPrice)}`;
            },
            title: 'Price',
            width: '10%'
        },
        {
            data: function(row) {
                return `₱ ${formatDecimal(row.qty * row.unitPrice)}`;
            },
            title: 'Total Amount',
            width: '12%'
        },
        {
            data: null,
            title: 'Action/s',
            width: '15%',
            render: '#action',
        },
    ];
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
                            Purchase Request
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('pr.display.transactions')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Pending For Approval
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                PR No# {{ pr.pr_no }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <button v-if="hasPermission('accept-all-pr-particular') ||  hasAnyRole(['Developer'])" @click="openApprovedAllModal(props.pr.id)" class="text-sm px-4 py-1 mx-1 my-1 lg:my-0 min-w-[120px] text-center text-white bg-indigo-600 border-2 border-indigo-600 rounded active:text-indigo-500 hover:bg-transparent hover:text-indigo-600 focus:outline-none focus:ring">
                            Accept All
                        </button>
                        <button v-if="hasPermission('reject-all-pr-particular') ||  hasAnyRole(['Developer'])" @click="openFailedAllModal(props.pr.id)" class="text-sm px-4 py-1 mx-1 my-1 lg:my-0 min-w-[120px] text-center text-indigo-600 border-2 border-indigo-600 rounded hover:bg-indigo-600 hover:text-white active:bg-indigo-500 focus:outline-none focus:ring">
                            Reject All
                        </button>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-full mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="shadow sm:rounded-lg bg-zinc-300 mb-5">
                        <div class="bg-zinc-400 px-4 py-5 sm:px-6 rounded-t-lg">
                            <h3 class="flex items-center font-bold text-lg leading-6 text-zinc-900">
                                Purchase Request Information

                                <span v-if="hasPermission('print-purchase-request') || hasAnyRole(['Developer'])" class="ml-3 rounded-full p-1 hover:bg-indigo-800 transition-colors duration-500 cursor-pointer">
                                    <a v-if="pr.pr_desc != 'PS-DBM'" :href="route('generatePdf.PurchaseRequestDraft', { pr: pr.id })" target="_blank" class="flex items-center rounded-full transition" aria-label="Print Purchase Request Draft" rel="noopener noreferrer">
                                        <svg class="w-7 h-7 text-zinc-800 hover:text-zinc-100 transition duration-150" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" >
                                            <path stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z" />
                                        </svg>
                                    </a>

                                    <a v-else :href="route('generatePdf.pr.psDbm', { pr: pr.id })" target="_blank" class="flex items-center rounded-full transition" aria-label="Print PS-DBM Purchase Request" rel="noopener noreferrer" >
                                        <svg class="w-7 h-7 text-indigo-100 hover:text-indigo-600 transition duration-150" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" >
                                            <path d="M13 13.15V10q0-.425-.288-.712T12 9t-.712.288T11 10v3.15l-.9-.875Q9.825 12 9.413 12t-.713.3q-.275.275-.275.7t.275.7l2.6 2.6q.3.3.7.3t.7-.3l2.6-2.6q.275-.275.287-.687T15.3 12.3q-.275-.275-.687-.288t-.713.263zM6 22q-.825 0-1.412-.587T4 20V8.825q0-.4.15-.762t.425-.638l4.85-4.85q.275-.275.638-.425t.762-.15H18q.825 0 1.413.588T20 4v16q0 .825-.587 1.413T18 22z" />
                                        </svg>
                                    </a>
                                </span>
                            </h3>
                            <p class="text-sm text-zinc-700">
                                ID# [{{ pr.pr_no }}] 
                                <span :class="{
                                    'bg-amber-200 text-amber-700 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-amber-700': pr.pr_status === 'Draft',
                                    'bg-emerald-200 text-emerald-700 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-emerald-700': pr.pr_status === 'Approved',
                                    }">
                                    {{ pr.pr_status }}
                                </span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:gap-2">
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Mode of Procurement
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.pr_desc }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Milestone
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.semester }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Procured Items
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.pr_remarks }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        PPMP-Based
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        No# {{ pr.ppmp_controller.ppmp_code }}
                                    </dd>
                                </div>
                            </dl>
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Date Created
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.created_at_formatted }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Created By
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.updater.name }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        No. Of Product Items
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ pr.totalItems }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Total Amount
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        ₱ {{ pr.formattedOverallPrice }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="w-full col-span-3 bg-zinc-300 rounded-md shadow mt-5 lg:mt-0">
                        <div class="overflow-hidden px-4 mt-5">
                            <div class="relative overflow-x-auto md:overflow-hidden">
                                <DataTable
                                    class="display table-hover table-striped bg-zinc-100"
                                    :columns="columns"
                                    :data="props.particulars"
                                    :options="{  
                                        paging: true,
                                        searching: true,
                                        ordering: false
                                    }">
                                        <template #action="props">
                                            <div v-if="props.cellData.status != 'approved'" class="px-3 py-1 text-center">
                                                <EditButton v-if="hasPermission('edit-pr-particular') ||  hasAnyRole(['Developer'])" @click="openEditModal(props.cellData)" tooltip="Edit" />
                                                <ApprovedButton v-if="hasPermission('accept-pr-particular') ||  hasAnyRole(['Developer'])" @click="openApprovedModal(props.cellData)" tooltip="Approved" />
                                                <RemoveButton v-if="hasPermission('reject-pr-particular') ||  hasAnyRole(['Developer'])" @click="openDropModal(props.cellData)" tooltip="Failed" />
                                            </div>
                                            <div v-else class="px-3 py-1 text-center">
                                                <span class='bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300'>
                                                    In Progress
                                                </span>
                                            </div>
                                        </template>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isEditPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Update PR Particular</h3>
                            <p class="text-sm text-zinc-700"> Enter the details you want to update on the input field.</p>
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Information: </p>
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
                                    <textarea v-model="editParticular.prodDesc" type="text" id="description" class="pl-24 p-2.5 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required></textarea>
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
                                        <input v-model="editParticular.prodPrice" type="text" id="price" class="mt-2 pl-20 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]"> Quantity: </p>
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
        <Modal :show="isApprovedPrModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitApproved">
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Approved!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will change the Product status to approved. This means that the product will proceed to Purchase Order. <br> This action can't be undone.</p>
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
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Move All Draft to Trash!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will change all Product with draft status will be trashed. <br> This action can't be undone.</p>
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
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Approved all Draft Items!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will change all Product with approved status. <br> This action can't be undone.</p>
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

:deep(table.dataTable tbody > tr > td:nth-child(2)) {
    text-align: left !important;
}

:deep(table.dataTable tbody > tr > td:nth-child(5)) {
    text-align: right !important;
}

:deep(table.dataTable tbody > tr > td:nth-child(6)) {
    text-align: right !important;
}
</style>