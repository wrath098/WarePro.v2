<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        offices: Object,
        authUserId: Number,
    });

    const create = useForm({
        offCode: '',
        offName: '',
        offHead: '',
        posHead: '',
        createdBy: props.authUserId || '',
    });

    const edit = useForm({
        offId: '',
        offCode: '',
        offName: '',
        offHead: '',
        posHead: '',
        updatedBy: props.authUserId || '',
    });

    const modalState = ref(null);

    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isDeactivateModalOpen = computed(() => modalState.value === 'deactivate');

    const showModal = (modalType) => {
        modalState.value = modalType;
    }

    const closeModal = () => {
        modalState.value = null;
    }

    const openEditModal = (office) => {
        edit.offId = office.id;
        edit.offCode = office.code;
        edit.offName = office.name;
        edit.offHead = office.head;
        edit.posHead = office.position;
        modalState.value = 'edit';
    };

    const openDeactivateModal = (office) => {
        edit.offId = office.id;
        modalState.value = 'deactivate';
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
                    }).then(() => closeModal());
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.log('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submit = () => submitForm('post', route('office.store'), create);
    const submitEdit = () => submitForm('put', route('office.update'), edit);
    const submitDeactivate = () => submitForm('put', route('office.deactivate'), edit);

    const columns = [
        {
            data: 'code',
            title: 'Code #',
            width: '7%'
        },
        {
            data: 'name',
            title: 'Name',
            width: '20%'
        },
        {
            data: 'head',
            title: 'Department Head',
            width: '18%'
        },
        {
            data: 'position',
            title: 'Position',
            width: '15%'
        },
        {
            data: 'updatedBy',
            title: 'Updated By',
            width: '12%'
        },
        {
            data: 'updatedAt',
            title: 'Updated At',
            width: '15%'
        },
        {
            data: null,
            title: 'Action',
            width: '13%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="Office" />
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
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('office.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Offices
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="hasAnyRole(['Developer']) || hasPermission('create-office')">
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">New Office</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns"
                        :data="props.offices"
                        :options="{  paging: true,
                            searching: true,
                            ordering: false
                        }">
                            <template #action="props">
                                <EditButton v-if="hasAnyRole(['Developer']) || hasPermission('edit-office')" @click="openEditModal(props.cellData)" tooltip="Edit"/>
                                <RemoveButton v-if="hasAnyRole(['Developer']) || hasPermission('delete-office')" @click="openDeactivateModal(props.cellData)" tooltip="Remove"/>
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
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> New Office</h3>
                            <p class="text-sm text-zinc-700"> Enter the details for the new Office you wish to add.</p>
                            <div class="mt-10">
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="create.offCode" type="text" name="officeCode" id="officeCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="officeCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Code/Abbreviation</label>
                                    <InputError class="mt-2" :message="create.errors.offCode" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="create.offName" type="text" name="officeName" id="officeName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="officeName" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Name</label>
                                    <InputError class="mt-2" :message="create.errors.offName" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="create.offHead" type="text" name="officeHead" id="officeHead" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="officeHead" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Head of Office</label>
                                    <InputError class="mt-2" :message="create.errors.offHead" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="create.posHead" type="text" name="positionHead" id="positionHead" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="positionHead" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Position of the Head of Office</label>
                                    <InputError class="mt-2" :message="create.errors.posHead" />
                                </div>
                                <input type="hidden" v-model="create.createdBy">
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
        <Modal :show="isEditModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Edit Office</h3>
                            <p class="text-sm text-zinc-700"> Edit the detail below for the Office you wish to update.</p>
                            <div class="mt-10">
                                <input type="hidden" v-model="edit.offId">
                                <input type="hidden" v-model="edit.updatedBy">
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="edit.offCode" type="text" name="editOfficeCode" id="editOfficeCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editOfficeCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Code/Abbreviation</label>
                                    <InputError class="mt-2" :message="edit.errors.offCode" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="edit.offName" type="text" name="editOfficeName" id="editOfficeName" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editOfficeName" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Name</label>
                                    <InputError class="mt-2" :message="edit.errors.offName" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="edit.offHead" type="text" name="editOfficeHead" id="editOfficeHead" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editOfficeHead" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Head of Office</label>
                                    <InputError class="mt-2" :message="edit.errors.offHead" />
                                </div>
                                <div class="relative z-0 w-full group my-3">
                                    <input v-model="edit.posHead" type="text" name="editPositionHead" id="editPositionHead" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editPositionHead" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Position of the Head of Office</label>
                                    <InputError class="mt-2" :message="edit.errors.posHead" />
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
        <Modal :show="isDeactivateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDeactivate">
                <input type="hidden" v-model="edit.offId">
                <input type="hidden" v-model="edit.updatedBy">
                <div class="bg-zinc-400 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Move to Trash!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will remove the selected Office into the list. This action can't be undone.</p>
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
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
            text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(3)) {
            text-align: left !important;
    }
</style>