<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { computed, onMounted, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import Return from '@/Components/Buttons/Icon/return.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const isLoading = ref(false);
    const page = usePage();
    const props = defineProps({
        transaction: Object,
        particulars: Object,
    });
    

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const returnParticular = useForm({
        pid: '',
    });

    const modalState = ref(null);
    const closeModal = () => { modalState.value = null; }
    const isReturnModalOpen = computed(() => modalState.value === 'return');

    const openReturnModal = (particular_id) => {
        returnParticular.pid = particular_id;
        modalState.value = 'return';
    }

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
            data: 'status',
            title: 'Transaction Status',
            width: '10%',
            render: (data, type, row) => {
                return `
                <span class="${data === 'Failed' 
                    ? 'bg-zinc-300 text-zinc-900 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-400' 
                    : 'bg-indigo-300 text-indigo-900 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-400'}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: 'itemNo',
            title: 'Item No#',
            width: '8%'
        },
        {
            data: 'stockNo',
            title: 'Stock No#',
            width: '10%'
        },
        {
            data: 'specs',
            title: 'Specification',
            width: '20%'
        },
        {
            data: 'unit',
            title: 'Unit of Measurement',
            width: '10%'
        },
        {
            data: 'quantity',
            title: 'Quantity',
            width: '8%'
        },
        {
            data: 'price',
            title: 'Unit Price',
            width: '10%'
        },
        {
            data: null,
            title: 'Total Amount',
            width: '10%',
            render: (data, type, row) => {
                const totalAmount = row.quantity * row.price;
                return totalAmount.toFixed(2);
            },
        },
        {
            data: 'expiry',
            title: 'Expiration Date',
            width: '8%'
        },
    ];

    if (hasAnyRole(['Developer'])) {
        columns.push({
            data: null,
            title: 'Action',
            width: '6%',
            render: '#action',
        });
    }

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

    const submitReturn = () => submitForm(route('return.iar.particular'), returnParticular);
</script>

<template>
    <Head title="PPMP" />
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
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('show.iar.transactions')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                All Transactions
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                IAR No# {{ transaction.iarNo }} with P.O. No# {{ transaction.poNo }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-full mb-8">
            <div class="overflow-x-auto">
                <div class="shadow sm:rounded-lg bg-zinc-300 mb-5">
                    <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                        <h3 class="font-bold text-lg leading-6 text-zinc-300">
                            Inspection and Acceptance Report Information
                        </h3>
                        <p class="text-sm text-zinc-300">
                            IAR No# [{{ transaction.iarNo }}] 
                            <span :class="{
                                'bg-amber-200 text-amber-700 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-amber-700': transaction.status === 'Pending',
                                'bg-emerald-200 text-emerald-700 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-emerald-700': transaction.status === 'Completed',
                                }">
                                {{ transaction.status }}
                            </span>
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:gap-2">
                        <dl class="font-semibold text-base">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    IAR No#
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                   {{ transaction.sdiIarId }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    PO No#
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.poNo }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    Supplier
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.supplier }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    IAR Date
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                   {{ transaction.iarDate }}
                                </dd>
                            </div>
                        </dl>
                        <dl class="font-semibold text-base">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    Number of Particulars
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.particularCount }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    Updated By
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.updater }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    Date Updated
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.dateUpdated }}
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-gray-600">
                                    Remarks
                                </dt>
                                <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                    {{ transaction.remark ?? 'N/a'}}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="mx-4 lg:mx-0">
                    <div class="col-span-3 bg-zinc-300 p-4 rounded-md shadow mt-5 lg:mt-0">
                        <div class="relative overflow-x-auto lg:overflow-hidden">
                            <DataTable
                                class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                                :columns="columns"
                                :data="props.particulars"
                                :options="{ 
                                    paging: true,
                                    searching: true,
                                    ordering: false
                                }"
                            >
                                <template #action="props">
                                    <Return @click="openReturnModal(props.cellData.pId)" tooltip="Return"/>
                                </template>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isReturnModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitReturn">
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-zinc-700 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m6 6 12 12m3-6a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>

                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Return Particular!</h3>
                        <p class="text-zinc-700 my-2">Confirming this action will return the selected Product to the pending list. <br> This action can't be undone.</p>
                        <p class="text-zinc-600"> Please confirm if you wish to proceed.  </p>
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

    :deep(table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(7)) {
        text-align: right !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(8)) {
        text-align: right !important;
    }
</style>