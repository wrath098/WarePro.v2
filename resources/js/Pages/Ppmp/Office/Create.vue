<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, watch } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const isSearchLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        user: Object,
    });

    const create = useForm({
        ppmpType: 'Office',
        ppmpYear: '',
        officeName: props.user.office.office_name,
        officeId: props.user.office.id,
        user: props.user,
    });

    const productCatalog = ref([]);
    const filterOfficeNoPpmp = async () => {
        if (!create.ppmpYear) return;

        productCatalog.value = [];

        isSearchLoading.value = true;
        try {
            const response = await axios.get('validate-office', {
                params: create
            });
            productCatalog.value = response.data?.data;
        } catch (error) {
            console.error('Error fetching office data:', error);
        } finally {
            isSearchLoading.value = false;
        }
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }
</script>

<template>
    <Head title="PPMP - Import" />
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
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('import.ppmp.index')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="w-full my-4 bg-zinc-300 shadow rounded-md">
            <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                <h3 class="font-bold text-lg leading-6 text-zinc-300">
                    Project Procurement Management Plan (PPMP)
                </h3>
                <p class="text-sm text-zinc-300">
                    The PPMP is prepared to identify and outline all materials, services, and equipment needed for the project, including their estimated costs and procurement schedules. It ensures that all required resources are acquired on time and in compliance with project guidelines and budgeting requirements, supporting the smooth and timely implementation of the project.
                </p>
            </div>
            <div class="p-4 shadow-md sm:rounded-lg">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-3 lg:gap-4">
                        <div class="relative z-0 w-full my-3 group">
                            <select v-model="create.ppmpYear" @change="filterOfficeNoPpmp" name="ppmpYear" id="ppmpYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                <option value="" disabled selected>Choose the PPMP year you want to view or create.</option>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <label for="ppmpYear" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                        </div>
                        <div v-if="create.ppmpYear" class="relative z-0 w-full my-3 group">
                            <input v-model="create.officeName" type="text" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" readonly/>
                            <label for="prodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Name</label>
                        </div>
                        <div v-if="create.ppmpYear" class="relative z-0 w-full my-3 group">
                            <input v-model="create.ppmpType" type="text" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" readonly/>
                            <label for="prodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isSearchLoading" class="flex justify-center items-center">
            <div class="text-center p-6">
                <div class="w-24 h-24 border-4 border-dashed rounded-full animate-spin border-[#380252] mx-auto"></div>
                <h2 class="text-zinc-900 mt-4">Loading...</h2>
                <p class="text-zinc-600 ">Waiting is a virtue.</p>
            </div>
        </div>
        <div v-if="productCatalog.length > 0" class="my-4 w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="sm:mx-4 lg:mx-0">
                    <div class="flex flex-col sm:flex-row md:gap-4 lg:gap-4">
                        <div class="rounded-md w-full sm:w-3/12">
                            <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                                <h3 class="font-bold text-lg leading-6 text-zinc-300">
                                    Select a Product
                                </h3>
                                <p class="text-sm text-zinc-300">
                                    Choose an item from the list below.
                                </p>
                            </div>
                            <div class="h-[calc(60vh-8rem)] overflow-y-auto bg-zinc-300 b-zinc-400">
                                <div v-for="product in productCatalog" :key="product.id" class="flex flex-col max-w-lg m-2">
                                    <button @click="select(product)" class="flex flex-col sm:flex-row border-b-2 border-r-2 border-zinc-400 py-1 px-1 w-full text-center sm:text-left rounded-lg bg-zinc-200 hover:bg-zinc-100 transition">
                                        <img :src="product.image_path" :alt="product.itemName" class="flex-shrink-0 m-2 w-16 h-16 rounded-md bg-zinc-100 self-center">
                                        <div class="flex flex-col py-2 pr-2">
                                            <h4 class="text-sm font-semibold">{{ product.itemName }} <span class="text-xs font-hairline">#{{ product.newNo }}</span></h4>
                                            <p class="text-xs font-hairline">{{ product.desc }}</p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                            
                        <div class="w-full sm:w-9/12 p-2 rounded-md mt-5 lg:mt-0">
                            <div class="p-2 overflow-hidden">
                                <div class="relative overflow-x-auto">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
 
<style scoped>
    .upload-area {
        border: 2px dashed #007BFF;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }
    .upload-area:hover {
        border-color: #08396d;
    }

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

    :deep(table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: left !important;
    }
</style>