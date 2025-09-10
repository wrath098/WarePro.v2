<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';
    import IconButton from '@/Components/Buttons/IconButton.vue';
    import Checkbox from '@/Components/Checkbox.vue';
    import Dropdown from '@/Components/Dropdown.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
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

    const props = defineProps({
        ppmp: Object,
        transactions: Object,
        individualList: Object,
        accountClass: Object,
    });

    const edit = useForm({
        ppmpId: '',
        ppmpDesc: '',
        user: props.user,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isConsolidateModalOpen = computed(() => modalState.value === 'copy');
    const isEditPpmpModalOpen = computed(() => modalState.value === 'edit');
    const isDropPpmpModalOpen = computed(() => modalState.value === 'drop');

    const filteredYears = ref([]);
    const filteredVersion = ref([]);
    const generateConsolidated = useForm({
        selectedType: '',
        selectedYear: '',
        selectedAccounts: [],
        priceAdjust:100,
    });

    const openDropPpmpModal = (ppmp) => {
        edit.ppmpId = ppmp.id;
        modalState.value = 'drop';
    };

    const openEditModal = (ppmp) => {
        edit.ppmpId = ppmp.id;
        modalState.value = 'edit';
    };

    const onTypeChange = (context) => {
        const type = props.individualList.find(typ => typ.ppmp_type === context.selectedType);
        filteredYears.value = type ? type.years : [];
        generateConsolidated.selectedYear = '';
    };

    const onYearChange = (context) => {
        const type = props.individualList.find(typ => typ.ppmp_type === context.selectedType);
        const year = type.years.find(yer => yer.ppmp_year === context.selectedYear);
        filteredVersion.value = year ? Object.entries(year.versions).map(([key, value]) => ({ id: key, version: value })) : [];
        generateConsolidated.selectedVersion = '';
    };

    const submitForm = (method, url, formData) => {
        isLoading.value = true;

        formData[method](url, {
            preserveScroll: true,
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    }).then(() => {
                        isLoading.value = false;
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
                console.error('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submitConsolidated = () => submitForm('post', route('consolidated.ppmp.store'), generateConsolidated);
    const submitEditPpmp = () => submitForm('post', route('make.copy.ppmp'), edit);
    const submitDropPpmp = () => submitForm('delete', route('indiv.ppmp.destroy'), edit);

    const columns = [
        {
            data: 'code',
            title: 'Transaction No#',
            width: '10%'
        },
        {
            data: 'ppmpYear',
            title: 'PPMP for Year',
            width: '10%'
        },
        {
            data: 'description',
            title: 'Description',
            width: '15%'
        },
        {
            data: 'details',
            title: 'Other Details',
            width: '30%'
        },
        {
            data: 'updatedBy',
            title: 'Updated By',
            width: '10%'
        },
        {
            data: 'createdAt',
            title: 'Created At',
            width: '10%'
        },
        {
            data: null,
            title: 'Action',
            width: '15%',
            render: '#action',
        },
    ];
</script>

<template>
    <Head title="PPMP" />
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
                            Project Procurement Management Plan
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Consolidated PPMP List
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                {{ props.ppmp.status }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="props.ppmp.status == 'Draft'">
                    <li v-if="hasPermission('consolidate-office-ppmp') ||  hasAnyRole(['Developer'])" class="flex flex-col lg:flex-row justify-center items-center">
                        <button @click="showModal('copy')" class="flex items-center rounded-md text-white bg-gradient-to-r from-indigo-500 via-indigo-600 to-indigo-700 hover:bg-gradient-to-br hover:text-gray-200 p-1 group">
                            <svg class="w-5 h-5 text-gray-100 group-hover:text-gray-200" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                    <path d="M3 7V5c0-1.1.9-2 2-2h2m10 0h2c1.1 0 2 .9 2 2v2m0 10v2c0 1.1-.9 2-2 2h-2M7 21H5c-1.1 0-2-.9-2-2v-2"/>
                                    <rect width="7" height="5" x="7" y="7" rx="1"/>
                                    <rect width="7" height="5" x="10" y="12" rx="1"/>
                                </g>
                            </svg>
                            <span class="mx-1 text-gray-100 text-sm group-hover:text-gray-200">Consolidate</span>
                        </button>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4">
                <div class="relative">
                    <DataTable
                        class="display responsive table-hover table-striped bg-zinc-100"
                        :columns="columns"
                        :data="transactions"
                        :options="{
                            paging: true,
                            searching: true,
                            ordering: false,
                        }">
                            <template #action="props">
                                <div v-if="ppmp.status == 'Draft'">
                                    <ViewButton v-if="hasPermission('view-app') ||  hasAnyRole(['Developer'])" :href="route('conso.ppmp.show', { ppmpTransaction: props.cellData.id })" tooltip="View"/>
                                    <IconButton v-if="hasPermission('edit-consolidation') ||  hasAnyRole(['Developer'])" @click="openEditModal(props.cellData)" class="w-full inline-flex justify-center text-base sm:ml-3 sm:w-auto sm:text-sm" tooltip="Create a Copy">
                                        <svg class="w-7 h-7 text-indigo-800 hover:text-indigo-600" fill="currentColor" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M6.569 2.25h6.362c.604 0 1.102 0 1.508.033c.421.035.809.109 1.173.294a3 3 0 0 1 1.311 1.311c.186.364.26.752.294 1.173c.033.406.033.904.033 1.508v.181h.4c.503 0 .918 0 1.257.023c.352.024.678.076.991.205a3 3 0 0 1 1.624 1.624c.13.313.18.639.205.99c.023.34.023.755.023 1.257v.052c0 .502 0 .917-.023 1.256a3.2 3.2 0 0 1-.189.95a12.43 12.43 0 0 1-8.236 8.37l-.189.059a3.1 3.1 0 0 1-.956.19c-.34.024-.754.024-1.256.024h-.052c-.502 0-.917 0-1.256-.023c-.352-.024-.678-.076-.991-.205a3 3 0 0 1-1.624-1.624c-.13-.313-.18-.639-.205-.99c-.023-.34-.023-.755-.023-1.257v-.401h-.18c-.604 0-1.103 0-1.509-.033c-.421-.035-.809-.108-1.173-.294a3 3 0 0 1-1.311-1.311c-.185-.364-.26-.752-.294-1.173c-.033-.406-.033-.904-.033-1.508V6.569c0-.604 0-1.102.033-1.508c.035-.421.109-.809.294-1.173a3 3 0 0 1 1.311-1.311c.364-.185.752-.26 1.173-.294c.406-.033.904-.033 1.508-.033M8.25 17.625c0 .534 0 .898.02 1.18c.018.276.053.419.094.519a1.5 1.5 0 0 0 .812.812c.1.041.243.076.519.094c.282.02.646.02 1.18.02s.898 0 1.18-.02c.276-.018.419-.053.519-.094a1.5 1.5 0 0 0 .812-.812c.041-.1.075-.243.094-.519c.02-.282.02-.646.02-1.18v-.026c0-.502 0-.917.023-1.256c.024-.352.076-.678.205-.991a3 3 0 0 1 1.624-1.624c.313-.13.639-.18.99-.205c.34-.023.755-.023 1.257-.023h.026c.534 0 .898 0 1.18-.02c.276-.019.419-.053.519-.094a1.5 1.5 0 0 0 .812-.812c.041-.1.076-.243.094-.519c.02-.282.02-.646.02-1.18s0-.898-.02-1.18c-.018-.276-.053-.419-.094-.519a1.5 1.5 0 0 0-.812-.812c-.1-.041-.243-.076-.519-.094c-.282-.02-.646-.02-1.18-.02H11.1c-.642 0-1.08 0-1.417.028c-.329.027-.497.076-.614.135a1.5 1.5 0 0 0-.656.656c-.06.117-.108.285-.135.614c-.027.338-.028.775-.028 1.417z"/>
                                        </svg>
                                    </IconButton>
                                    <RemoveButton v-if="hasPermission('delete-app') ||  hasAnyRole(['Developer'])" @click="openDropPpmpModal(props.cellData)" tooltip="Trash"/>
                                </div>
                                <div v-if="ppmp.status == 'Approved'" class="flex justify-center">
                                    <Dropdown width="56">
                                        <template #trigger>
                                            <button class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600 flex items-center gap-2">
                                                <span class="sr-only">Open options</span>
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"viewBox="0 0 16 16">
                                                    <g fill="currentColor">
                                                        <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                                                        <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1a.5.5 0 0 0 0 1"/>
                                                    </g>
                                                </svg>
                                                Print
                                                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <a 
                                                :href="route('generatePdf.ApprovedConsolidatedPpmp', { ppmp: props.cellData.id, version: 'raw'})"
                                                target="_blank" 
                                                class="flex w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2v6a2 2 0 0 0 2 2h6v10a2 2 0 0 1-2 2h-7.103a2.7 2.7 0 0 0 .092-.5H11a2 2 0 0 0 2-2V16a2.5 2.5 0 0 0-2.007-2.451A2.75 2.75 0 0 0 8.25 11h-3.5q-.392.001-.75.104V4a2 2 0 0 1 2-2zm1.5.5V8a.5.5 0 0 0 .5.5h5.5zM3 13.75c0-.966.784-1.75 1.75-1.75h3.5c.966 0 1.75.784 1.75 1.75v.75h.5A1.5 1.5 0 0 1 12 16v3.5a1 1 0 0 1-1 1h-1v.75A1.75 1.75 0 0 1 8.25 23h-3.5A1.75 1.75 0 0 1 3 21.25v-.75H2a1 1 0 0 1-1-1V16a1.5 1.5 0 0 1 1.5-1.5H3zm5.5 0a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25v.75h4zm-4 5.5v2c0 .138.112.25.25.25h3.5a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25"/>
                                                </svg>
                                                <span class="ml-2 text-sm">Raw File Format</span>   
                                            </a>
                                            <a 
                                                :href="route('generatePdf.ApprovedConsolidatedPpmp', { ppmp: props.cellData.id, version: 'contingency'})"
                                                target="_blank" 
                                                class="flex w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M12 2v6a2 2 0 0 0 2 2h6v10a2 2 0 0 1-2 2h-7.103a2.7 2.7 0 0 0 .092-.5H11a2 2 0 0 0 2-2V16a2.5 2.5 0 0 0-2.007-2.451A2.75 2.75 0 0 0 8.25 11h-3.5q-.392.001-.75.104V4a2 2 0 0 1 2-2zm1.5.5V8a.5.5 0 0 0 .5.5h5.5zM3 13.75c0-.966.784-1.75 1.75-1.75h3.5c.966 0 1.75.784 1.75 1.75v.75h.5A1.5 1.5 0 0 1 12 16v3.5a1 1 0 0 1-1 1h-1v.75A1.75 1.75 0 0 1 8.25 23h-3.5A1.75 1.75 0 0 1 3 21.25v-.75H2a1 1 0 0 1-1-1V16a1.5 1.5 0 0 1 1.5-1.5H3zm5.5 0a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25v.75h4zm-4 5.5v2c0 .138.112.25.25.25h3.5a.25.25 0 0 0 .25-.25v-2a.25.25 0 0 0-.25-.25h-3.5a.25.25 0 0 0-.25.25"/>
                                                </svg>
                                                <span class="ml-2 text-sm">Contingency Format</span>   
                                            </a>
                                        </template>
                                    </Dropdown>
                                </div>
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isConsolidateModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitConsolidated">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Consolidate PPMP</h3>
                        <p class="text-sm text-zinc-700"> Enter the details to generate a consolidated PPMP you wish to consolidate.</p>
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]">PPMP Information: </p>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="generateConsolidated.selectedType" @change="onTypeChange(generateConsolidated)" name="selectedType" id="selectedType" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Type</option>
                                    <option v-for="type in props.individualList" :key="type.ppmp_type" :value="type.ppmp_type">{{ type.ppmp_type == 'individual' ? 'Office' : type.ppmp_type }}</option>
                                </select>
                                <label for="selectedType" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                            </div>
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="generateConsolidated.selectedYear" @change="onYearChange(generateConsolidated)" name="selectedYear" id="selectedYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select Year</option>
                                    <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">{{ year.ppmp_year }}</option>
                                </select>
                                <label for="selectedYear" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Year</label>
                            </div>
                            <div class="w-full my-4">
                                <label class="block mb-2 text-sm font-semibold text-[#1a0037]">
                                    Check Account Class to Consolidate:
                                </label>

                                <div v-for="(account, index) in accountClass" :key="index" class="flex items-center space-x-2 mb-2">
                                    <Checkbox v-model:checked="generateConsolidated.selectedAccounts" :value="index"/>
                                    <span class="text-sm text-gray-800">{{ account }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]"> Price Adjustment: <span class="text-sm font-medium text-[#8f9091]">Increase the prices of a product item by a percentage.</span></p>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="generateConsolidated.priceAdjust" type="number" min="100" max="120" name="priceAdjust" id="priceAdjust" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="priceAdjust" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Value must be within 100 to 120</label>
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
    <Modal :show="isEditPpmpModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitEditPpmp">
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Create Duplicate of Consolidated PPMP</h3>
                        <p class="text-sm text-zinc-700">Provide a description for the copied consolidated PPMP.</p>
                        
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-[#1a0037]">PPMP Description</p>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="edit.ppmpDesc" type="text" name="ppmpDesc" id="ppmpDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="ppmpDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter description</label>
                                <InputError class="mt-2" :message="edit.errors.ppmpId" />
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
    <Modal :show="isDropPpmpModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitDropPpmp">
            <input type="hidden" v-model="edit.ppmpId">
            <div class="bg-zinc-300 h-auto">
                <div class="p-6  md:mx-auto">
                    <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Drop PPMP?</h3>
                        <p class="text-gray-600 my-2">Confirming this action will permanently remove the selected PPMP including its particulars into the list. This action cannot be redo.</p>
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
        background-color: #fafafa;
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

    :deep(table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: left !important;
    }
</style>
