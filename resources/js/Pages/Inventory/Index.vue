<script setup>
    import { computed, onMounted, reactive, ref } from 'vue';
    import { Head, usePage } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
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
    });

    const addParticular = reactive({
        pid: '',
        stockNo: '',
        desc: '',
        qty: '',
        remarks: '',
        prodId: '',
        type: 'adjustment'
    });

    const modalState = ref(null);
    const closeModal = () => { modalState.value = null; }
    const isAddModalOpen = computed(() => modalState.value === 'add');

    const openAddModal = (item) => {
        addParticular.pid = item.id;
        addParticular.stockNo = item.stockNo;
        addParticular.desc = item.prodDesc;
        addParticular.prodId = item.prodId;
        modalState.value = 'add';
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
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Product Inventory</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Current Stock</a></li>
                </ol>
            </nav>
        </template>
        <div class="py-8">
            <div class="grid grid-cols-1 gap-3 px-4 sm:grid-cols-3 sm:px-8">
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
                        <p class="text-3xl"></p>
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
            <div class="mt-8 sm:px-6 lg:px-8">
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="w-full text-gray-900 display">
                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                            <tr>
                                <th class="w-1/12">Action</th>
                                <th class="w-1/12">Stock No.</th>
                                <th class="w-4/12">Description</th>
                                <th class="w-1/12">Unit of Measure</th>
                                <th class="w-1/12">Beginning Balance</th>
                                <th class="w-1/12">Stock Available</th>
                                <th class="w-1/12">Purchases</th>
                                <th class="w-1/12">Issuances</th>
                                <th class="w-1/12">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in inventory" :key="item.id">
                                <td class="text-center">
                                    <button @click="openAddModal(item)">
                                        <svg class="w-6 h-6 text-blue-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4.243a1 1 0 1 0-2 0V11H7.757a1 1 0 1 0 0 2H11v3.243a1 1 0 1 0 2 0V13h3.243a1 1 0 1 0 0-2H13V7.757Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </td>
                                <td>{{ item.stockNo }}</td>
                                <td>{{ item.prodDesc }}</td>
                                <td>{{ item.prodUnit }}</td>
                                <td class="text-center">{{ item.beginningBalance }}</td>
                                <td class="text-center">{{ item.stockAvailable }}</td>
                                <td class="text-center">{{ item.purchases }}</td>
                                <td class="text-center">{{ item.issuances }}</td>
                                <td>
                                    <span :class="{
                                        'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': item.status === 'Available',
                                        'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-red-300': item.status == 'Out of Stock'
                                        }">
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
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
    </AuthenticatedLayout>
    </div>
</template>
<style scoped>
:deep(table.dataTable) {
    border: 2px solid #555555;
}

:deep(table.dataTable thead > tr > th) {
    background-color: #555555;
    text-align: center;
    color: aliceblue;
}
</style>