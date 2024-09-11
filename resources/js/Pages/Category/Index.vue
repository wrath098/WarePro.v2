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

const showModal = (modalType) => {
    modalState.value = modalType;
}

const closeModal = () => {
    modalState.value = null;
}

const props = defineProps({
    categories: Array,
    funds: Array,
    authUserId: Number,
});

const form = reactive({
    catName: '',
    catCode: '',
    fundId: '',
    createdBy: props.authUserId || '',
});

const submit = () => {
    Inertia.post('categories/save', form, {
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error('Form submission failed', errors);
        },
    });
};
</script>

<template>
    <Head title="Category" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-3">Categories</h2>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                            <li v-for="category in categories" :key="category.id" class="flex items-center gap-4 p-4 justify-center h-auto rounded-lg  bg-gray-100 shadow-md transition-transform transform">
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
                                                <button class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                    Edit 
                                                </button>
                                                <button class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:bg-gray-100 transition duration-150 ease-in-out">
                                                    Remove
                                                </button>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </div>
                            </li>
                            <button @click="showModal('add')" class="flex items-center justify-center h-48 rounded-lg bg-gray-100 shadow-md transition-transform transform hover:scale-105">
                                <span class="text-xl text-gray-900">
                                    <svg class="w-6 h-6 text-indigo-900 transition duration-75" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18M3 12h18"/>
                                    </svg>
                                    Add
                                </span>
                            </button>
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
                                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> New Category</h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-gray-500"> Enter the details for the new category you wish to add.</p>
                                                    <input type="hidden" v-model="form.createdBy">
                                                    <input type="text" v-model="form.catName" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Name : Ex. Common Office Supplies" required>
                                                    <input type="number" v-model="form.catCode" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Code : Ex. 01-99" required>
                                                    <select v-model="form.fundId" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500">
                                                        <option value="" disabled selected>Please choose its fund cluster if applicable</option>
                                                        <option v-for="fund in funds" :key="fund.id" :value="fund.id">
                                                            {{ fund.name }}
                                                        </option>
                                                    </select>
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
                            
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 