<script setup>
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Swal from 'sweetalert2';

const page = usePage();
const message = computed(() => page.props.flash.message);

const props = defineProps({
    user: Object,
    roles: Array,
    direct_permissions: Array,
    all_permissions: Array,
    roleList: Object
});

const isLoading = ref(false);
const modalState = ref(null);
const isAddModalOpen = computed(() => modalState.value === 'add');
const isUpdateModalOpen = computed(() => modalState.value === 'update');
const isRemoveRoleModalOpen = computed(() => modalState.value === 'remove');
const showModal = (modalType) => { modalState.value = modalType;}
const closeModal = () => {modalState.value = null;}

const newRole = useForm({
    roleName: '',
    userId: props.user.id,
});

const removeUserRole = useForm({});
const updateUser = useForm({
    id: props.user.id,
    name: props.user.name,
    email: props.user.email,
});

const removeRole = reactive({
    role: '',
    userId: props.user.id,
});

const openRemoveModal = (role) => {
    removeRole.role = role;
    modalState.value = 'remove';
};

const submitRole = () => {
    isLoading.value = true;

    newRole.post(route('user.assign.role'), {
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            newRole.reset();
            Swal.fire({
                title: 'Success',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            }).then(() => {
                isLoading.value = false;
                closeModal();
            });
        },
        onError: (errors) => {
            isLoading.value = false;
        },
    });
};

const submitRemoveRole = async () => {
    removeUserRole.delete(route('user.revoke.role', { param: removeRole }), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Success',
                text: message.value || 'User account deleted successfully.',
                icon: 'success',
                confirmButtonText: 'OK',
            }).then(() => {
                closeModal();
            });
        },
        onError: (errors) => {
            isLoading.value = false;w
        }
    });
};

const submitUpdatedInformation = async () => {
    updateUser.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Success',
                text: message.value || 'User account deleted successfully.',
                icon: 'success',
                confirmButtonText: 'OK',
            }).then(() => {
                closeModal();
            });
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join('\n') || 'An error occurred.';
            Swal.fire('Error!', errorMessage, 'error');
        }
    });
};
</script>

<template>
    <Head title="Users" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center" aria-current="page">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Account Setting
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('user')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Users
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                {{ user?.email }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-slate-50 shadow rounded-md">
            <section class="m-4 p-1 xs:p-8">
                <div class="w-full mx-auto border-2 border-[#7393dc] rounded-lg p-8">
                    <h2 class="sm:text-xl text-[12px] font-bold mb-6">Personal Information</h2>
                    <div class="space-y-6">
                        <div class="grid sm:grid-cols-3 grid-cols-1 gap-4">
                            <div>
                                <label for="to" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Complete Name</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.name" disabled>
                                </div>
                            </div>
                            <div>
                                <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Email Address</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.email" disabled>
                                </div>
                            </div>
                            <div>
                                <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Date Created</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.created_at" disabled>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Roles</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                <div v-for="role in roles" :key="role.id" class="flex items-center justify-between h-[50px] rounded-[5px] text-xs xs:text-sm bg-indigo-500  w-full px-2 pl-4 font-light mb-2">
                                    <p class="truncate text-gray-50">{{ role.name }}</p>
                                    <button
                                        v-if="role.name !== 'Developer'"
                                        @click="openRemoveModal(role)" 
                                        class="text-lg text-rose-600 px-4 py-1 rounded-lg hover:bg-gray-50 transition duration-300 shadow-sm"
                                    >
                                        X
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end text-xs">
                        <button @click="showModal('add')" class="border-2 border-blue-500 text-blue-600 px-4 py-1 rounded-lg hover:bg-blue-500 hover:text-white transition duration-300 shadow-sm mx-1">
                            Add Roles
                        </button>
                        <button @click="showModal('update')" class="border-2 border-teal-500 text-teal-600 px-4 py-1 rounded-lg hover:bg-teal-500 hover:text-white transition duration-300 shadow-sm mx-1">
                            Update Information
                        </button>
                        <button class="border-2 border-rose-500 text-rose-600 px-4 py-1 rounded-lg hover:bg-rose-500 hover:text-white transition duration-300 shadow-sm mx-1">
                            Change Password
                        </button>
                    </div>
                </div>
            </section>
            <section class="m-4 p-1 xs:p-8">
                <div class="w-full mx-auto border-2 border-[#7393dc] rounded-lg p-8">
                    <h2 class="sm:text-xl text-[12px] font-bold mb-6">Permission/s</h2>
                    <div class="space-y-6">
                        <div class="grid sm:grid-cols-3 grid-cols-1 gap-4">
                            <div>
                                <label for="to" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Complete Name</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.name">
                                </div>
                            </div>
                            <div>
                                <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Email Address</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.email">
                                </div>
                            </div>
                            <div>
                                <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Date Created</span></label>
                                <div class="relative max-w-xs">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="user?.created_at">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="from" class="text-xs xs:text-sm font-medium text-gray-700 mb-1"><span class="font-light">Roles</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                <div v-for="role in roles" :key="role.id">
                                    <input type="text" class="h-[50px] rounded-[5px] text-xs xs:text-sm border border-[#D1D5DB] w-full px-2 pl-4 font-light" placeholder="" :value="role?.name">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitRole">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12 22q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22m-2.5-8.5q1.45 0 2.475-1.025T13 10t-1.025-2.475T9.5 6.5T7.025 7.525T6 10t1.025 2.475T9.5 13.5m7 1q1.05 0 1.775-.725T19 12t-.725-1.775T16.5 9.5t-1.775.725T14 12t.725 1.775t1.775.725M12 20q2.125 0 3.875-1t2.825-2.65q-.525-.15-1.075-.25T16.5 16q-1.325 0-3.2.775t-3 3.05q.425.1.85.138T12 20m-3.175-.65q.875-1.8 1.988-2.675T12.5 15.5q-.725-.225-1.463-.362T9.5 15q-1.125 0-2.225.275t-2.125.775q.65 1.075 1.588 1.938t2.087 1.362"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Add New Role for the User</h3>
                            <p class="text-sm text-gray-500"> Select the roles that you wish to add to the User.</p>
                            <div class="relative z-0 w-full group my-7 lg:mb-0">
                                <select v-model="newRole.roleName" name="role" id="role" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected class="pl-5">Select Office/End-User</option>
                                    <option v-for="role in props.roleList" :key="role.id" :value="role.name" class="ml-5">{{ role.name }}</option>
                                </select>
                                <label for="role" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select a Role</label>
                                <InputError class="mt-2" :message="newRole.errors.roleName" />
                                <InputError class="mt-2" :message="newRole.errors.userId" />
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
        <Modal :show="isUpdateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitUpdatedInformation">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12 22q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22m-2.5-8.5q1.45 0 2.475-1.025T13 10t-1.025-2.475T9.5 6.5T7.025 7.525T6 10t1.025 2.475T9.5 13.5m7 1q1.05 0 1.775-.725T19 12t-.725-1.775T16.5 9.5t-1.775.725T14 12t.725 1.775t1.775.725M12 20q2.125 0 3.875-1t2.825-2.65q-.525-.15-1.075-.25T16.5 16q-1.325 0-3.2.775t-3 3.05q.425.1.85.138T12 20m-3.175-.65q.875-1.8 1.988-2.675T12.5 15.5q-.725-.225-1.463-.362T9.5 15q-1.125 0-2.225.275t-2.125.775q.65 1.075 1.588 1.938t2.087 1.362"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Update User Information</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the user you wish to update.</p>
                            <div class="mt-10">
                                <p class="text-sm text-[#86591e] mb-2">Name</p>
                                <div class="relative z-0 w-full group my-1">
                                    <input v-model="updateUser.name" type="text" name="name" id="name" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="name" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Full Name</label>
                                    <InputError class="mt-2" :message="updateUser.errors.name" />
                                    </div>
                            </div>
                            <div class="mt-10">
                                <p class="text-sm text-[#86591e] mb-2">Email Address</p>
                                <div class="relative z-0 w-full group my-1">
                                    <input v-model="updateUser.email" type="email" name="email" id="email" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="email" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Email Address</label>
                                    <InputError class="mt-2" :message="updateUser.errors.email" />
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
        <Modal :show="isRemoveRoleModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitRemoveRole">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Remove Role from the User!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Role into the user. This action can't be undone.</p>
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