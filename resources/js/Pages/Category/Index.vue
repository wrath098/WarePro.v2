<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { ref, reactive, computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import Modal from '@/Components/Modal.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import Swal from 'sweetalert2';
    import RecycleIcon from '@/Components/Buttons/RecycleIcon.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import TrashedButton from '@/Components/Buttons/TrashedButton.vue';
    import useAuthPermission from '@/Composables/useAuthPermission';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const modalState = ref(null);
    const page = usePage();
    const isLoading = ref(false);

    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isDropModalOpen = computed(() => modalState.value === 'deactivate');
    const isConfirmModalOpen = computed(() => modalState.value === 'confirm');

    const showModal = (modalType) => {
        modalState.value = modalType;
    }

    const closeModal = () => {
        modalState.value = null;
    }

    const props = defineProps({
        activeCategories: Object,
        funds: Array,
        authUserId: Number,
    });

    const trashedCategories = ref([]);
    const isTrashedActive = ref(false);
    const fetchTrashedCategories = async () => {
        isLoading.value = true;
        isTrashedActive.value = true;
        try {
            const response = await axios.get('categories/trashed-categories');
            trashedCategories.value = response.data;
            isLoading.value = false;
        } catch (error) {
            isLoading.value = false;
            console.error('Error fetching trashed items:', error);
        }
    };

    const form = reactive({
        catName: '',
        catCode: '',
        fundId: '',
        createdBy: props.authUserId || '',
    });

    const editForm = reactive({
        catId: '',
        catName: '',
        catCode: '',
        fundId: '',
        updater: props.authUserId || '',
    });

    const dropForm = reactive({
        catId: '',
        updater: props.authUserId || '',
    });

    const confirmForm = reactive({
        catId: '',
        updater: props.authUserId || '',
    });

    const openEditModal = (category) => {
        editForm.catId = category.id;
        editForm.catName = category.name;
        editForm.catCode = category.code;
        editForm.fundId = category.fundId;
        modalState.value = 'edit';
    };

    const openDropModal = (category) => {
        dropForm.catId = category.id;
        modalState.value = 'deactivate';
    };

    const openConfirmModal = (category) => {
        confirmForm.catId = category;
        modalState.value = 'confirm';
    };

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            }
        });
    };

    const submit = () => submitForm('categories/save', form);
    const submitEdit = () => submitForm('categories/update', editForm);
    const submitDrop = () => submitForm('categories/deactivate', dropForm);
    const confirmFormSubmit = () => submitForm(`categories/restore/${confirmForm.catId}`, null);

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
            title: 'Code #',
            width: '10%',
            render: '#code',
        },
        {
            data: 'name',
            title: 'Name',
            width: '35%'
        },
        {
            data: 'status',
            title: 'Current Status',
            width: '10%',
            render: (data, type, row) => {
                return `
                <span class="${data === 'Deactivated' 
                    ? 'bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-red-300' 
                    : ''}">
                    ${data}
                </span>
                `;
            },
        },
        {
            data: 'fundName',
            title: 'Account Classification',
            width: '15%'
        },
        {
            data: 'updatedBy',
            title: 'Removed By',
            width: '10%'
        },
        {
            data: 'updatedAt',
            title: 'Date Removed',
            width: '10%'
        },
        {
            data: null,
            title: 'Action',
            width: '10%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="Category" />
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
                            Components
                        </a>
                    </li>
                    <li :aria-current="!isTrashedActive ? 'page' : undefined">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('category.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Categories
                            </a>
                        </div>
                    </li>
                    <li v-if="isTrashedActive" aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Trashed
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="hasAnyRole(['Developer']) || hasPermission('create-category') || hasPermission('view-trashed-category')">
                    <li class="flex flex-col lg:flex-row">
                        <AddButton v-if="hasAnyRole(['Developer']) || hasPermission('create-category')" @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">New Item Class</span>
                        </AddButton>
                        <TrashedButton v-if="hasAnyRole(['Developer']) || hasPermission('view-trashed-category')" @click="fetchTrashedCategories" class="mx-1 my-1 lg:my-0" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                            <span class="mr-2">Trashed</span>
                        </TrashedButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-white shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <ul v-if="!isTrashedActive" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                        <li v-for="category in activeCategories" :key="category.id" class="flex items-center gap-4 p-4 justify-center h-auto rounded-lg  bg-gray-100 shadow-md transition-transform transform">
                            <div class="text-2xl font-bold text-indigo-900">
                                {{ category.code.padStart(2, '0') }}
                            </div>

                            <div class="flex-1 flex items-start justify-between bg-gray-50 p-4 rounded-lg">
                                <div class="flex flex-col gap-1">
                                    <p class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ category.name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Fund Cluster: </span> {{ category.fundName}}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium ">Added By: </span> {{ category.creatorName }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Status: </span> {{ category.status }}
                                    </p>
                                </div>
                                <div v-if="hasAnyRole(['Developer']) || hasPermission('edit-category') || hasPermission('delete-category')" class="flex items-center">
                                    <Dropdown>
                                        <template #trigger>
                                            <button class="flex items-center bg-gray-700 text-indigo-100 p-2 rounded-full hover:text-gray-900  hover:bg-indigo-50 transition">
                                                <span class="sr-only">Open options</span>
                                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 3">
                                                    <path d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <button v-if="hasAnyRole(['Developer']) || hasPermission('edit-category')" @click="openEditModal(category)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                Edit 
                                            </button>
                                            <button v-if="hasAnyRole(['Developer']) || hasPermission('delete-category')"  @click="openDropModal(category)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                Remove
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <DataTable
                        v-if="isTrashedActive"
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="trashedCategories.data"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }">
                            <template #code="props">
                                <span>{{ props.cellData.code.padStart(2, '0') }}</span>
                            </template>
                            <template #action="props">
                                <RecycleIcon @click="openConfirmModal(props.cellData.id)"/>
                            </template>
                    </DataTable>
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> New Category</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the New Category you wish to add.</p>
                            <div class="mt-2">
                                <input type="hidden" v-model="form.createdBy">
                                <div class="relative z-0 w-full group mt-8">
                                    <select v-model="form.fundId" name="fundId" id="fundId" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Please choose the Account Classification applicable</option>
                                        <option v-for="fund in funds" :key="fund.id" :value="fund.id">
                                            {{ fund.name }}
                                        </option>
                                    </select>
                                    <label for="fundId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Account Classification</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="form.catName" type="text" name="category" id="category" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="category" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category Name</label>
                                </div>
                                <!-- <div class="relative z-0 w-full group my-2">
                                    <input v-model="form.catCode" type="number" name="categoryCode" id="categoryCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="categoryCode" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category Code</label>
                                </div> -->
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Edit Category</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the new category you wish to add.</p>
                            <div class="mt-10">
                                <input type="hidden" v-model="editForm.updater">
                                <input type="hidden" v-model="editForm.catId">
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editForm.catName" type="text" name="editCatName" id="editCatName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="editCatName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category Name</label>
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="editForm.catCode" type="text" name="editCatCode" id="editCatCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" disabled/>
                                    <label for="editCatCode" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category Code</label>
                                </div>
                                <div class="relative z-0 w-full group my2">
                                    <select v-model="editForm.fundId" name="fundId" id="fundId" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required disabled>
                                        <option value="" disabled selected>Please choose the Account Classification applicable</option>
                                        <option v-for="fund in funds" :key="fund.id" :value="fund.id">
                                            {{ fund.name }}
                                        </option>
                                    </select>
                                    <label for="fundId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Account Classification</label>
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
                        Save Changes 
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
        <Modal :show="isDropModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDrop">
                <input type="hidden" v-model="dropForm.catId">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Category into the list.</p>
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

    :deep([data-v-de7a9c2b] table.dataTable tbody > tr > td:nth-child(2)) {
            text-align: left !important;
    }

    :deep([data-v-de7a9c2b] table.dataTable tbody > tr > td:nth-child(4)) {
            text-align: left !important;
    }
</style>