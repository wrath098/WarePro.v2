<script setup>
import { Head } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';

const modalState = ref(null);

const isAddModalOpen = computed(() => modalState.value === 'add');
const isEditModalOpen = computed(() => modalState.value === 'edit');
const isDropModalOpen = computed(() => modalState.value === 'drop');

const showModal = (modalType) => {
    modalState.value = modalType;
}

const closeModal = () => {
    modalState.value = null;
}

const props = defineProps({
    fundCluster: Array,
    authUserId: Number,
});

const form = reactive({
    fundName: '',
    fundDesc: '',
    createdBy: props.authUserId || '',
});

const editForm = reactive({
    fundId: '',
    updatedBy: props.authUserId || '',
    fundName: '',
    fundDesc: ''
});

const dropForm = reactive({
    fundId: '',
    updatedBy: props.authUserId || '',
});


const submit = () => {
    Inertia.post('funds/save', form, {
        onSuccess: () => {
            closeModal();
        }
    });
};

const editFormSubmit = () => {
    Inertia.post('funds/update', editForm, {
        onSuccess: () => {
            closeModal();
        },
    });
}

const dropFormSubmit = () => {
    Inertia.post('funds/deactivate', dropForm, {
        onSuccess: () => {
            closeModal();
        },
    });
}

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

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word.charAt(0).toUpperCase())
        .join('');
};
</script>

<template>
    <Head title="Fund" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">Account Classification</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-indigo-400 my-2 italic">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-gray-400 my-2 italic">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                            <li v-for="fund in fundCluster" :key="fund.id" class="flex items-center gap-4 p-4 justify-center h-auto rounded-lg  bg-gray-100 shadow-md transition-transform transform">
                                <div class="text-2xl font-bold text-indigo-900">
                                    {{ getInitials(fund.fund_name) }}
                                </div>

                                <div class="flex-1 flex items-start justify-between bg-gray-50 p-4 rounded-lg">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ fund.fund_name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium">Description: </span> {{ fund.description || 'No Description' }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium">Status: </span> {{ fund.fund_status }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-medium">Added By: </span> {{ fund.nameOfCreator }}
                                        </p>
                                    </div>
                                    <div class="flex items-center">
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
                                                <button @click="openEditModal(fund)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                    Edit 
                                                </button>
                                                <button @click="openDropModal(fund)" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                    Remove
                                                </button>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </div>
                            </li>
                            <button @click="showModal('add')" class="flex items-center justify-center h-48 rounded-lg bg-gray-100 shadow-md transition-transform transform hover:scale-105">
                                <span class="text-xl font-semibold text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-6 h-6 text-indigo-900 transition duration-75 ml-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18M3 12h18"/>
                                    </svg>
                                    New Fund Cluster
                                </span>
                            </button>
                            <Modal :show="isAddModalOpen" @close="closeModal"> 
                                <form @submit.prevent="submit">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start">
                                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                                <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd"/>
                                                    <path fill-rule="evenodd" d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> New Fund Cluster</h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-gray-500 mb-4"> Enter the details for the New Cluster you wish to add.</p>
                                                    <div class="relative z-0 w-full group my-2">
                                                        <input v-model="form.fundName" type="text" name="fundName" id="fundName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                                        <label for="fundName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Name</label>
                                                    </div>
                                                    <div class="relative z-0 w-full group my-2">
                                                        <input v-model="form.fundDesc" type="text" name="fundDesc" id="fundDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=""/>
                                                        <label for="fundDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Description (<i>Opptional</i>)</label>
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
                                <form @submit.prevent="editFormSubmit">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start">
                                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                                <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M12 14a3 3 0 0 1 3-3h4a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-4a3 3 0 0 1-3-3Zm3-1a1 1 0 1 0 0 2h4v-2h-4Z" clip-rule="evenodd"/>
                                                    <path fill-rule="evenodd" d="M12.293 3.293a1 1 0 0 1 1.414 0L16.414 6h-2.828l-1.293-1.293a1 1 0 0 1 0-1.414ZM12.414 6 9.707 3.293a1 1 0 0 0-1.414 0L5.586 6h6.828ZM4.586 7l-.056.055A2 2 0 0 0 3 9v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2h-4a5 5 0 0 1 0-10h4a2 2 0 0 0-1.53-1.945L17.414 7H4.586Z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Edit Fund Cluster</h3>
                                                <p class="text-sm text-gray-500"> Enter the details for the New Cluster you wish to update.</p>
                                                <div class="mt-8">
                                                    <input type="hidden" v-model="editForm.fundId" id="edit_fundId">
                                                    <input type="hidden" v-model="editForm.updatedBy" id="edit_updatedBy">
                                                    <div class="relative z-0 w-full group my-2">
                                                        <input v-model="editForm.fundName" type="text" name="editFundName" id="editFundName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                                        <label for="editFundName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Name</label>
                                                    </div>
                                                    <div class="relative z-0 w-full group my-3">
                                                        <input v-model="editForm.fundDesc" type="text" name="editFundDesc" id="editFundDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" />
                                                        <label for="editFundDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Fund Description (<i>Opptional</i>)</label>
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
                                    <div class="bg-gray-100 h-auto">
                                        <div class="bg-white p-6  md:mx-auto">
                                            <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                                            </svg>

                                            <div class="text-center">
                                                <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                                                <p class="text-gray-600 my-2">Confirming this action will remove the selected Fund Cluster into the list.</p>
                                                <p> Please confirm if you wish to proceed.  </p>
                                                <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
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
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </Modal>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 