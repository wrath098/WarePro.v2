<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, reactive, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import RecycleIcon from '@/Components/Buttons/RecycleIcon.vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        activeItemClass: Object,
        deactivatedItemClass: Object,
        categories: Array,
        authUserId: Number,
    });

    const form = reactive({
        catId: '',
        itemName: '',
        createdBy: props.authUserId || '',
    });

    const editForm = reactive({
        itemId: '',
        editName: '',
        updatedBy: props.authUserId || '',
    });

    const modalState = ref(null);
    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isDeactivateModalOpen = computed(() => modalState.value === 'deactivate');
    const isConfirmModalOpen = computed(() => modalState.value === 'confirm');

    const showModal = (modalType) => {
        modalState.value = modalType;
    }

    const openEditModal = (item) => {
        editForm.itemId = item.id;
        editForm.editName = item.name;
        modalState.value = 'edit';
    };

    const openDeactivateModal = (item) => {
        editForm.itemId = item.id;
        modalState.value = 'deactivate';
    };

    const openConfirmModal = (item) => {
        editForm.itemId = item;
        modalState.value = 'confirm';
    };

    const closeModal = () => {
        modalState.value = null;
    }

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
        });
    };

    const submit = () => submitForm('items/save', form);
    const submitEdit = () => submitForm('items/update', editForm);
    const submitDeactivate = () => submitForm('items/deactivate', editForm);
    const confirmFormSubmit = () => submitForm(`items/restore/${editForm.itemId}`, null);

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
    <Head title="Item Class" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Item Classes</a></li>
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')">
                            <span class="mr-2">New Item Class</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <DataTable class="w-full text-left rtl:text-right text-gray-900 ">
                            <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Code
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Class Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Category
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Current Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Created/Updated By
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Date Created
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Action/s
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in activeItemClass" :key="item.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  ++index }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  item.code }}
                                    </td>
                                    <td class="py-2">
                                        {{ item.name }}
                                    </td>
                                    <td class="py-2">
                                        {{ item.category }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <span :class="{
                                            'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': item.status === 'Active',
                                            }">
                                            {{ item.status }}
                                        </span>
                                        
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ item.creator }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ item.createdAt }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <EditButton @click="openEditModal(item)" tooltip="Edit"/>
                                        <RemoveButton @click="openDeactivateModal(item)" tooltip="Remove"/>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>
            <div v-if="deactivatedItemClass.length > 0" class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2 mt-10">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <DataTable class="w-full text-left rtl:text-right text-gray-900 ">
                            <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Code
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Class Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Category
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Current Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Created/Updated By
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Date Updated
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Action/s
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in deactivatedItemClass" :key="item.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  ++index }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  item.code }}
                                    </td>
                                    <td class="py-2">
                                        {{ item.name }}
                                    </td>
                                    <td class="py-2">
                                        {{ item.category }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <span :class="{
                                            'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-red-300': item.status === 'Deactivated',
                                            }">
                                            {{ item.status }}
                                        </span>
                                        
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ item.creator }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ item.updatedAt }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <RecycleIcon @click="openConfirmModal(item.id)"/>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> New Item Class</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the new Item Class you wish to add.</p>

                            <div class="mt-10">
                                <div class="relative z-0 w-full mb-5 group">
                                    <select v-model="form.catId" name="catId" id="catId" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected class="pl-5">Please choose the category applicable</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <label for="catId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category</label>
                                </div>
                                <!-- <input type="number" v-model="form.itemCode" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Code : Ex. 1-99" required> -->
                                <div class="relative z-0 w-full group my-1">
                                    <input v-model="form.itemName" type="text" name="itemName" id="itemName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="itemName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Name</label>
                                </div>
                                <input type="hidden" v-model="form.createdBy">
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Edit Item Class</h3>
                            <p class="text-sm text-gray-500"> Edit the detail below for the Item Class you wish to update.</p>
                            <div class="mt-10">
                                <input type="hidden" v-model="editForm.itemId">
                                <input type="hidden" v-model="editForm.updatedBy">
                                <div class="relative z-0 w-full group my-1">
                                    <input v-model="editForm.editName" type="text" name="editItemName" id="editItemName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editItemName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Name</label>
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
        <Modal :show="isDeactivateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDeactivate">
                <input type="hidden" v-model="editForm.itemId">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Item Class into the list. you can't redo this</p>
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
        <Modal :show="isConfirmModalOpen" @close="closeModal"> 
            <form @submit.prevent="confirmFormSubmit">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-indigo-700 w-16 h-16 mx-auto my-6" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24" width="512" height="512" fill="currentColor">
                            <path fill-rule="evenodd" d="M18.5,3h-5.53c-.08,0-.16-.02-.22-.05l-3.16-1.58c-.48-.24-1.02-.37-1.56-.37h-2.53C2.47,1,0,3.47,0,6.5v11c0,3.03,2.47,5.5,5.5,5.5h13c3.03,0,5.5-2.47,5.5-5.5V8.5c0-3.03-2.47-5.5-5.5-5.5Zm2.5,14.5c0,1.38-1.12,2.5-2.5,2.5H5.5c-1.38,0-2.5-1.12-2.5-2.5V8H20.95c.03,.16,.05,.33,.05,.5v9Zm-3.13-3.71c.39,.39,.39,1.02,0,1.41l-3.16,3.16c-.63,.63-1.71,.18-1.71-.71v-1.66H7.5c-.83,0-1.5-.67-1.5-1.5s.67-1.5,1.5-1.5h5.5v-1.66c0-.89,1.08-1.34,1.71-.71l3.16,3.16Z"/>
                        </svg>
                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Confirm as Approved!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remark the selected PPMP as Final/Approved. This action can't be undone.</p>
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
 