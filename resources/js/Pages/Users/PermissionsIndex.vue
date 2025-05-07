<script setup>
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import Modal from '@/Components/Modal.vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { Inertia } from '@inertiajs/inertia';
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { computed, onMounted, reactive, ref } from 'vue';
    import Swal from 'sweetalert2';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import InputError from '@/Components/InputError.vue';

    const page = usePage();
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        permissions: Object,
    });

    const isLoading = ref(false);
    const modalState = ref(null);
    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isDropModalOpen = computed(() => modalState.value === 'drop');
    const showModal = (modalType) => { modalState.value = modalType;}
    const closeModal = () => {modalState.value = null;}

    const create = useForm({
        permissionName: '',
    });

    const drop = useForm({
        id: '',
    });

    const openDropModal = (id) => {
        drop.id = id;
        modalState.value = 'drop';
    };

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
                    }).then(() => {
                        closeModal();
                        isLoading.value = false;
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.log('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submit = () => submitForm('post', route('user.permissions.store'), create);
    const submitDrop = () => submitForm('delete', route('user.permissions.destroy', { permission: drop.id}), drop);
    
    const columns = [
        {
            data: 'name',
            title: 'Permissions',
            width: '40%',
        },
        {
            data: 'guardName',
            title: 'Guard Name',
            width: '20%',
        },
        {
            data: 'createdAt',
            title: 'Date Created',
            width: '20%',
        },
        {
            data: null,
            title: 'Action',
            width: '20%',
            render: '#action',
        },
    ];

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
    <Head title="Account Setting" />
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
                            Account Settings
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('user.permissions')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Permissions
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">New Permission</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 max-w-screen-2xl bg-white shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="props.permissions"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }">
                        <template #action="props">
                            <RemoveButton @click="openDropModal(props.cellData.id)" tooltip="Remove"/>
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
                            <svg class="h-8 w-8 text-indigo-600" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12 22q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22m-2.5-8.5q1.45 0 2.475-1.025T13 10t-1.025-2.475T9.5 6.5T7.025 7.525T6 10t1.025 2.475T9.5 13.5m7 1q1.05 0 1.775-.725T19 12t-.725-1.775T16.5 9.5t-1.775.725T14 12t.725 1.775t1.775.725M12 20q2.125 0 3.875-1t2.825-2.65q-.525-.15-1.075-.25T16.5 16q-1.325 0-3.2.775t-3 3.05q.425.1.85.138T12 20m-3.175-.65q.875-1.8 1.988-2.675T12.5 15.5q-.725-.225-1.463-.362T9.5 15q-1.125 0-2.225.275t-2.125.775q.65 1.075 1.588 1.938t2.087 1.362"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Create New User Permission</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the new User Permission you wish to add.</p>
                            <div class="mt-10">
                                <p class="text-sm text-[#86591e] mb-2">User Permission</p>
                                <div class="relative z-0 w-full group my-1">
                                    <input v-model="create.permissionName" type="text" name="roleName" id="roleName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required />
                                    <label for="roleName" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Permission Name</label>
                                    <InputError class="mt-2" :message="create.errors.permissionName" />
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
        <Modal :show="isDropModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDrop">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Delete Permission!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Permission into the list. This action can't be undone.</p>
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

    :deep([data-v-72e215fa] table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: left !important;
    }
</style>