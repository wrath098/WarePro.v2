<script setup>
    import { computed, reactive, ref } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import { DataTable } from 'datatables.net-vue3';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import { Inertia } from '@inertiajs/inertia';


    const props = defineProps({
        inventory: Object,
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
        Inertia.post('inventory/store', addParticular, {
            onSuccess: () => {
                closeModal();
            },
        });
    }

</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-[''] after:ml-2 text-green-700">Product Inventory</a></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
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
                                        'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-red-300': item.status == 'Reorder'
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