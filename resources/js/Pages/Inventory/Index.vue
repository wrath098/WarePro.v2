<script setup>
    import { computed, onMounted, reactive, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { DataTable } from 'datatables.net-vue3';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const isLoading = ref(false);
    const props = defineProps({
        inventory: Object,
        countOutOfStock: Number,
        countAvailable: Number,
        countReorder: Number,
    });

    const reformatInventory = Object.values(props.inventory);

    const addParticular = reactive({
        pid: '',
        stockNo: '',
        desc: '',
        qty: '',
        remarks: '',
        prodId: '',
        type: 'adjustment'
    });

    const editParticular = reactive({
        pid: '',
        stockNo: '',
        desc: '',
        reorder: '',
    });

    const modalState = ref(null);
    const closeModal = () => { modalState.value = null; }
    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');

    const openAddModal = (item) => {
        addParticular.pid = item.id;
        addParticular.stockNo = item.stockNo;
        addParticular.desc = item.prodDesc;
        addParticular.prodId = item.prodId;
        modalState.value = 'add';
    }

    const openEditModal = (item) => {
        editParticular.pid = item.id;
        editParticular.reorder = item.reorderLevel;
        editParticular.stockNo = item.stockNo,
        editParticular.desc = item.prodDesc,
        modalState.value = 'edit';
    }

    const submitAdd = () => {
        isLoading.value = true;
        Inertia.post('inventory/store', addParticular, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
        });
    }

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
            data: null,
            title: 'Action/s',
            width: '10%',
            render: '#action',
        },
        {
            data: 'stockNo',
            title: 'Stock No.',
            width: '10%',
        },
        {
            data: 'prodDesc',
            title: 'Description',
            width: '20%',
        },
        {
            data: 'prodUnit',
            title: 'Unit of Measure',
            width: '5%',
        },
        {
            data: 'beginningBalance',
            title: 'Beginning Balance',
            width: '10%',
        },
        {
            data: 'stockAvailable',
            title: 'Stock Available',
            width: '10%',
        },
        {
            data: 'purchases',
            title: 'Purchases',
            width: '10%',
        },
        {
            data: 'issuances',
            title: 'Issuances',
            width: '10%',
        },
        {
            data: 'status',
            title: 'Status',
            width: '15%',
            render: (data, type, row) => {
                return `
                 <span class="${data === 'Available' 
                    ? 'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-1 rounded border border-indigo-300' 
                    : data === 'Out of Stock'
                    ? 'bg-rose-100 text-rose-800 text-xs font-medium me-2 px-2.5 py-1 rounded border border-rose-300'
                    : data === 'Re-order'
                    ? 'bg-orange-100 text-orange-800 text-xs font-medium me-2 px-2.5 py-1 rounded border border-orange-300'
                    : ''}">
                    ${data}
                </span>
                `;
            },
        },
    ];
</script>

<template>
    <Head title="Product Inventory" />
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
                            Product Inventory
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('inventory.index')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Warehouse Stock
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="py-4 w-full px-4 lg:px-0">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-indigo-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 12 12" stroke="currentColor">
                            <path fill="currentColor" d="M6 12A6 6 0 1 0 6 0a6 6 0 0 0 0 12Zm2.53-6.72L5.78 8.03a.75.75 0 0 1-1.06 0l-1-1a.75.75 0 0 1 1.06-1.06l.47.47l2.22-2.22a.75.75 0 0 1 1.06 1.06Z"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Total Items Available</h3>
                        <p class="text-3xl">{{ countAvailable }}</p>
                    </div>
                </div>
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-yellow-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                <path d="M3 16a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1zm7 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zm7 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zM5 11V8a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v3"/>
                                <path d="M16.5 8.5L19 11l2.5-2.5"/>
                            </g>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Total Items for Re-Order</h3>
                        <p class="text-3xl">{{ countReorder }}</p>
                    </div>
                </div>
                
                <div class="flex items-center bg-white border rounded-sm overflow-hidden shadow">
                    <div class="p-4 bg-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 22c-.818 0-1.6-.33-3.163-.988C4.946 19.373 3 18.554 3 17.175V7.542M12 22c.818 0 1.6-.33 3.163-.988C19.054 19.373 21 18.554 21 17.175V7.542M12 22v-9.97m9-4.488c0 .613-.802 1-2.405 1.773l-2.92 1.41c-1.804.87-2.705 1.304-3.675 1.304m9-4.487c0-.612-.802-.999-2.405-1.772L17 5M3 7.542c0 .613.802 1 2.405 1.773l2.92 1.41c1.804.87 2.705 1.304 3.675 1.304M3 7.542c0-.612.802-.999 2.405-1.772L7 5m-1 8.026l2 .997M10 2l2 2m0 0l2 2m-2-2l-2 2m2-2l2-2" color="currentColor"/>
                        </svg>
                    </div>
                    <div class="px-4 text-gray-700">
                        <h3 class="text-sm tracking-wider">Total Items Out of Stock</h3>
                        <p class="text-3xl">{{ countOutOfStock }}</p>
                    </div>
                </div>
            </div>

            <div class="my-4">
                <div class="bg-white relative p-4 overflow-x-auto md:overflow-hidden shadow-md rounded-md">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="reformatInventory"
                        :options="{  
                            paging: true,
                            searching: true,
                            ordering: true
                        }">
                            <template #action="props">
                                <button @click="openEditModal(props.cellData)" class="m-1" title="Edit">
                                    <svg class="w-6 h-6 text-orange-700 hover:text-indigo-900" aria-hidden="true" fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 576 512">
                                        <path fill="currentColor" d="m402.6 83.2l90.2 90.2c3.8 3.8 3.8 10 0 13.8L274.4 405.6l-92.8 10.3c-12.4 1.4-22.9-9.1-21.5-21.5l10.3-92.8L388.8 83.2c3.8-3.8 10-3.8 13.8 0zm162-22.9l-48.8-48.8c-15.2-15.2-39.9-15.2-55.2 0l-35.4 35.4c-3.8 3.8-3.8 10 0 13.8l90.2 90.2c3.8 3.8 10 3.8 13.8 0l35.4-35.4c15.2-15.3 15.2-40 0-55.2zM384 346.2V448H64V128h229.8c3.2 0 6.2-1.3 8.5-3.5l40-40c7.6-7.6 2.2-20.5-8.5-20.5H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V306.2c0-10.7-12.9-16-20.5-8.5l-40 40c-2.2 2.3-3.5 5.3-3.5 8.5z"/>
                                    </svg>
                                </button>
                                <button @click="openAddModal(props.cellData)" class="m-1" title="Add">
                                    <svg class="w-6 h-6 text-blue-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="200" height="200" ill="currentColor" viewBox="0 0 48 48">
                                        <mask id="ipTAdd0">
                                            <g fill="none" stroke="#fff" stroke-linejoin="round" stroke-width="4">
                                                <rect width="36" height="36" x="6" y="6" fill="#555" rx="3"/>
                                                <path stroke-linecap="round" d="M24 16v16m-8-8h16"/>
                                            </g>
                                        </mask>
                                        <path fill="currentColor" d="M0 0h48v48H0z" mask="url(#ipTAdd0)"/>
                                    </svg>
                                </button>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAdd">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Product Inventory</h3>
                            <p class="text-sm text-gray-500">Enter the proper stock no. of the product.</p>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500"> Product Information: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Stock No: </span>
                                    </div>
                                    <input v-model="addParticular.stockNo" type="text" id="stockNo" class="mt-2 pl-16 p-2.5 bg-gray-200 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-10" readonly>
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Description: </span>
                                    </div>
                                    <input v-model="addParticular.desc" type="text" id="stockNo" class="mt-2 pl-20 p-2.5 bg-gray-200 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-10" readonly>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Product Quantity: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Quantity: </span>
                                    </div>
                                    <input v-model="addParticular.qty" type="number" id="stockNo" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 1" required>
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Remarks: </span>
                                    </div>
                                    <input v-model="addParticular.remarks" type="text" id="stockNo" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. Reconciled Quantity" required>
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
        <Modal :show="isEditModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">Update Product Inventory Reorder Level</h3>
                            <p class="text-sm text-gray-500">This will adjust the number of the reorder level of a specific product.</p>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500"> Product Information: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Stock No: </span>
                                    </div>
                                    <input v-model="editParticular.stockNo" type="text" id="stockNo" class="mt-2 pl-16 p-2.5 bg-gray-200 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-10" readonly>
                                </div>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Description: </span>
                                    </div>
                                    <input v-model="editParticular.desc" type="text" id="stockNo" class="mt-2 pl-20 p-2.5 bg-gray-200 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-10" readonly>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Product Reorder Level: </p>
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                        <span class="text-gray-600 text-sm font-semibold">Quantity: </span>
                                    </div>
                                    <input v-model="editParticular.reorder" type="text" id="stockNo" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. Reconciled Quantity" required>
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

    :deep([data-v-285881b3] table.dataTable tbody > tr > td:nth-child(3)) {
            text-align: left !important;
    }
</style>