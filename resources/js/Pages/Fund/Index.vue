<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import RecycleIcon from '@/Components/Buttons/RecycleIcon.vue';
import Swal from 'sweetalert2';
import TrashedButton from '@/Components/Buttons/TrashedButton.vue';
import useAuthPermission from '@/Composables/useAuthPermission';
import InputError from '@/Components/InputError.vue';
import AddButton from '@/Components/Buttons/AddButton.vue';

const {hasAnyRole, hasPermission} = useAuthPermission();
const modalState = ref(null);
const page = usePage();
const isLoading = ref(false);

const isAddModalOpen = computed(() => modalState.value === 'add');
const isEditModalOpen = computed(() => modalState.value === 'edit');
const isDropModalOpen = computed(() => modalState.value === 'drop');
const isConfirmModalOpen = computed(() => modalState.value === 'confirm');
const isAddCartegoryModalOpen = computed(() => modalState.value === 'addCategory');

const showModal = (modalType) => {
    modalState.value = modalType;
}

const closeModal = () => {
    modalState.value = null;
}

const props = defineProps({
    activeFund: Array,
    authUserId: Number,
});

const trashedFund = ref([]);
const isTrashedActive = ref(false);
const fetchTrashedFund = async () => {
    isLoading.value = true;
    isTrashedActive.value = true;
    try {
        const response = await axios.get('funds/trashed-funds');
        trashedFund.value = response.data;
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
        console.error('Error fetching trashed items:', error);
    }
};

const form = useForm({
    fundName: '',
    fundDesc: '',
    createdBy: props.authUserId || '',
});

const editForm = useForm({
    fundId: '',
    updatedBy: props.authUserId || '',
    fundName: '',
    fundDesc: ''
});

const dropForm = useForm({
    fundId: '',
    updatedBy: props.authUserId || '',
});

const confirmForm = useForm({
    fundId: '',
    updatedBy: props.authUserId || '',
});

const addCategoryForm = useForm({
    fundId: '',
    fundName: '',
    catName: '',
    createdBy: props.authUserId || '',
});


const submitForm = (method, url, formData) => {
    isLoading.value = true;

    formData[method](url, {
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

const submit = () => submitForm('post', route('fund.store'), form);
const editFormSubmit = () => submitForm('put', route('fund.update'), editForm);
const dropFormSubmit = () => submitForm('put', route('fund.deactivate'), dropForm);
const submitNewCategory = () => submitForm('post', route('category.store'), addCategoryForm);
const confirmFormSubmit = () => {
    if (confirmForm.fundId) {
        submitForm('put', route('fund.restore', { id: confirmForm.fundId }), confirmForm);
    } else {
        Swal.fire({
            title: 'Error',
            text: 'Fund ID is missing',
            icon: 'error',
        });
    }
};

const openEditModal = (fund) => {
    editForm.fundId = fund.id;
    editForm.updatedBy = props.authUserId || '';
    editForm.fundName = fund.fund_name;
    editForm.fundDesc = fund.description || '';
    modalState.value = 'edit';
}

const openDropModal = (fund) => {
    dropForm.fundId = fund.id;
    dropForm.updatedBy = props.authUserId || '';
    modalState.value = 'drop';
}

const openConfirmModal = (fundId) => {
    confirmForm.fundId = fundId;
    confirmForm.updatedBy = props.authUserId || '';
    modalState.value = 'confirm';
}

const openAddCategoryModal = (fund) => {
    addCategoryForm.fundId = fund.id,
    addCategoryForm.fundName = fund.fund_name,
    modalState.value = 'addCategory';
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
        }).then(() => {
            Inertia.get(window.location.href);
        });
    }

    if (errMessage.value) {
        Swal.fire({
            title: 'Failed',
            text: errMessage.value,
            icon: 'error',
            confirmButtonText: 'OK',
        }).then(() => {
            Inertia.get(window.location.href);
        });
    }
});

const columns = [
        {
        data: null,
        title: 'Action',
        width: '10%',
        render: '#action',
    },
    {
        data: 'name',
        title: 'Fund Name',
        width: '20%',
    },
    {
        data: 'status',
        title: 'Current Status',
        width: '15%',
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
        data: 'desc',
        title: 'Description',
        width: '15%'
    },
    {
        data: 'nameOfCreator',
        title: 'Removed By',
        width: '20%'
    },
    {
        data: 'updatedAt',
        title: 'Date Removed',
        width: '20%'
    },
];
</script>

<template>
    <Head title="Account Classification" />
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
                            Components
                        </a>
                    </li>
                    <li :aria-current="!isTrashedActive ? 'page' : undefined">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('fund.display.all')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Account Classification
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
                <ol class="flex flex-col lg:flex-row">
                    <li v-if="hasAnyRole(['Developer']) || hasPermission('create-account-class')" >
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Add New Account Classification</span>
                        </AddButton>
                    </li>
                    <li v-if="hasAnyRole(['Developer']) || hasPermission('view-trashed-account-class')">
                        <TrashedButton @click="fetchTrashedFund" class="mx-1 my-1 lg:my-0" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                            <span class="mr-2">Trashed</span>
                        </TrashedButton>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 w-screen-2xl rounded-md mb-8">
            <div class="overflow-hidden sm:rounded-lg">
                <ul v-if="!isTrashedActive" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <li v-for="fund in activeFund" :key="fund.id" class="h-auto shadow-md rounded-lg bg-zinc-300 transition-transform transform">
                        <div class="flex-1 flex items-start justify-between p-4 rounded-lg">
                            <div class="flex flex-col gap-1">
                                <h4 class="text-xl font-semibold text-indigo-900 hover:underline">
                                    <a v-if="hasPermission('view-category') || hasAnyRole(['Developer'])" :href="route('category.display.active', { fund: fund.id })">{{ fund.fund_name }}</a>
                                </h4>
                                <p class="text-sm text-zinc-700 font-semibold">
                                    <span>Description: </span> {{ fund.description || 'No Description' }}
                                </p>
                                <p class="text-sm text-zinc-700 font-semibold">
                                    <span>Status: </span> {{ fund.fund_status }}
                                </p>
                                <p class="text-sm text-zinc-700 font-semibold">
                                    <span>Added By: </span> {{ fund.nameOfCreator }}
                                </p>
                            </div>
                            <div v-if="hasAnyRole(['Developer']) || hasPermission('edit-account-class') || hasPermission('delete-account-class')" class="flex items-center">
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
                                        <a :href="route('category.display.active', { fund: fund.id })" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                            View
                                        </a>
                                        <button @click="openEditModal(fund)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                            Edit 
                                        </button>
                                        <button @click="openDropModal(fund)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                            Remove
                                        </button>
                                        <button @click="openAddCategoryModal(fund)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                            New Category 
                                        </button>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                        <div class="p-4 rounded-lg">
                            <h4 class="text-base font-semibold text-zinc-700">Categories</h4>
                            <ul v-if="fund.categories.length > 0">
                                <li v-for="category in fund.categories" :key="category.id" class="my-1 w-full grid grid-cols-12 gap-2">
                                    <p class="flex justify-center items-center text-xs font-bold p-1 text-zinc-700 rounded-md bg-zinc-100 col-span-1">{{ category.cat_code.padStart(2, '0') }}</p>
                                    <p class="col-span-11 font-medium text-zinc-600 hover:underline">{{ category.cat_name }}</p>
                                </li>
                            </ul>
                            <ul v-else>
                                <li class="font-medium text-zinc-600 hover:underline">No category available.</li>
                            </ul>
                        </div>
                    </li>
                </ul>
                <div v-if="isTrashedActive" class="relative overflow-x-auto bg-zinc-300 p-4">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="trashedFund.data"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <RecycleIcon @click="openConfirmModal(props.cellData.id)"/>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submit">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd"/>
                                <path fill-rule="evenodd" d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> New Fund Cluster</h3>
                            <p class="text-sm text-zinc-700 mb-4"> Enter the details for the New Cluster you wish to add.</p>

                            <div class="mt-2">
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="form.fundName" type="text" name="fundName" id="fundName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="fundName" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Name</label>
                                    <InputError class="mt-2" :message="form.errors.fundName" />
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="form.fundDesc" type="text" name="fundDesc" id="fundDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=""/>
                                    <label for="fundDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Description (<i>Opptional</i>)</label>
                                    <InputError class="mt-2" :message="form.errors.fundDesc" />
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
    </AuthenticatedLayout>
    <Modal :show="isEditModalOpen" @close="closeModal"> 
        <form @submit.prevent="editFormSubmit">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd"/>
                            <path fill-rule="evenodd" d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Edit Fund Cluster</h3>
                        <p class="text-sm text-zinc-700"> Enter the details for the New Cluster you wish to update.</p>
                        <div class="mt-8">
                            <input type="hidden" v-model="editForm.fundId" id="edit_fundId">
                            <input type="hidden" v-model="editForm.updatedBy" id="edit_updatedBy">
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="editForm.fundName" type="text" name="editFundName" id="editFundName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required />
                                <label for="editFundName" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Name</label>
                                <InputError class="mt-2" :message="editForm.errors.fundName" />
                            </div>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="editForm.fundDesc" type="text" name="editFundDesc" id="editFundDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" />
                                <label for="editFundDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Description (<i>Opptional</i>)</label>
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
        <form @submit.prevent="dropFormSubmit">
            <input type="hidden" v-model="dropForm.fundId">
            <input type="hidden" v-model="dropForm.updatedBy">
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>

                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                        <p class="text-gray-600 my-2">Confirming this action will remove the selected Fund Cluster into the list.</p>
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
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-indigo-700 w-16 h-16 mx-auto my-6" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24" width="512" height="512" fill="currentColor">
                        <path fill-rule="evenodd" d="M18.5,3h-5.53c-.08,0-.16-.02-.22-.05l-3.16-1.58c-.48-.24-1.02-.37-1.56-.37h-2.53C2.47,1,0,3.47,0,6.5v11c0,3.03,2.47,5.5,5.5,5.5h13c3.03,0,5.5-2.47,5.5-5.5V8.5c0-3.03-2.47-5.5-5.5-5.5Zm2.5,14.5c0,1.38-1.12,2.5-2.5,2.5H5.5c-1.38,0-2.5-1.12-2.5-2.5V8H20.95c.03,.16,.05,.33,.05,.5v9Zm-3.13-3.71c.39,.39,.39,1.02,0,1.41l-3.16,3.16c-.63,.63-1.71,.18-1.71-.71v-1.66H7.5c-.83,0-1.5-.67-1.5-1.5s.67-1.5,1.5-1.5h5.5v-1.66c0-.89,1.08-1.34,1.71-.71l3.16,3.16Z"/>
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Confirm as Approved!</h3>
                        <p class="text-zinc-700 my-2">Confirming this action will remark the selected PPMP as Final/Approved. This action can't be undone.</p>
                        <p class="text-zinc-900"> Please confirm if you wish to proceed.  </p>
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
    <Modal :show="isAddCartegoryModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitNewCategory">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd"/>
                            <path fill-rule="evenodd" d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> New Category</h3>
                        <p class="text-sm text-zinc-700"> Enter the details for the New Category you wish to add.</p>
                        <div class="mt-8">
                            <input type="hidden" v-model="addCategoryForm.fundId" id="addfundId">
                            <input type="hidden" v-model="addCategoryForm.createdBy" id="createdBy">
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="addCategoryForm.fundName" type="text" name="addFundName" id="addFundName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required readonly />
                                <label for="editFundName" class="font-semibold absolute text-sm text-zinc-700 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Name</label>
                                <InputError class="mt-2" :message="addCategoryForm.errors.fundName" />
                            </div>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="addCategoryForm.catName" type="text" name="categoryName" id="categoryName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                <label for="categoryName" class="font-semibold absolute text-sm text-zinc-700 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category Name</label>
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
        background-color: #fafafa;
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