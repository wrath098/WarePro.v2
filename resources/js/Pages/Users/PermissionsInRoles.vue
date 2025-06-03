<script setup>
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import Modal from '@/Components/Modal.vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { computed, onMounted, reactive, ref } from 'vue';
    import Swal from 'sweetalert2';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import InputError from '@/Components/InputError.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';

    const props = defineProps({
        role: Object,
        permissions: Object,
    });

    const isLoading = ref(false);
    const modalState = ref(null);
    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isDropModalOpen = computed(() => modalState.value === 'drop');
    const showModal = (modalType) => { modalState.value = modalType;}
    const closeModal = () => {modalState.value = null;}

    const create = useForm({
        roleName: '',
    });

    const drop = useForm({
        permission_id: '',
        role_id: '',
    });

    const openDropModal = (id) => {
        drop.permission_id = id;
        drop.role_id = props.role.id;
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

    const submit = () => submitForm('post', route('user.roles.store'), create);
    const submitDrop = () => submitForm('delete', route('role.permission.destroy'), drop);

    const columns = [
        {
            data: 'name',
            title: 'Roles',
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
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('user.roles')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Roles
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                {{ role.name }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Add New Permission</span>
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
                            <RemoveButton v-if="!['Developer', 'System Administrator'].includes(props.cellData?.name)" @click="openDropModal(props.cellData.id)" tooltip="Remove"/>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isDropModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitDrop">
            <div class="bg-gray-100 h-auto">
                <div class="bg-white p-6  md:mx-auto">
                    <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                    </svg>

                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Delete Role!</h3>
                        <p class="text-gray-600 my-2">Confirming this action will remove the selected Role into the list. This action can't be undone.</p>
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

    :deep([data-v-c1690676] table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: left !important;
    }
</style>