<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { reactive, ref, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        officePpmps: Object,
        offices: Object,
        user: Number,
    });

    const edit = reactive({
        ppmpId: '',
        user: props.user,
    });

    const modalState = ref(null);
    const file = ref([]);
    const fileInput = ref(null);

    const isDropPpmpModalOpen = computed(() => modalState.value === 'drop');

    const closeModal = () => {
        modalState.value = null;
    }

    const openDropPpmpModal = (ppmp) => {
        edit.ppmpId = ppmp.id;
        modalState.value = 'drop';
    };

    const create = reactive({
        ppmpType: '',
        ppmpYear: '',
        ppmpSem: '',
        office: '',
        createdBy: props.user,
    });

    const onFileChange = (event) => {
        const selectedFile = event.target.files[0];
        if (selectedFile) {
            file.value = selectedFile;
        }
    };

    const onDrop = (event) => {
        event.preventDefault();
        const droppedFile = event.dataTransfer.files[0];
        if (droppedFile) {
            file.value = droppedFile;
        }
    };

    const officeList = ref([]);
    const filterOfficeNoPpmp = async () => {
        if (create.ppmpType && create.ppmpYear) {
            try {
                const response = await axios.get('ppmp/offices-with-no-ppmp', { params: create });
                officeList.value = response.data;
            } catch (error) {
                console.error('Error fetching office data:', error);
            }
        }
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const submit = () => {
        if (!file.value) {
            alert('Please select a file first!');
            return;
        }

        const formData = new FormData();
            formData.append('ppmpType', create.ppmpType);
            formData.append('ppmpYear', create.ppmpYear);
            formData.append('ppmpSem', create.ppmpSem);
            formData.append('office', create.office);
            formData.append('user', create.createdBy);
            formData.append('file', file.value);

        Inertia.post('ppmp/create', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
    };

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
                isLoading.value = false;
            },
        });
    };

    const submitDropPpmp = () => submitForm('ppmp/drop', edit);

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success!',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed!',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });

    const columns = [
        {
            data: 'officeCode',
            title: 'Office Code',
            width: '25%'
        },
        {
            data: 'ppmpCode',
            title: 'Transaction No#',
            width: '15%'
        },
        {
            data: 'ppmpType',
            title: 'Type',
            width: '15%',
        },
        {
            data: 'basedPrice',
            title: 'Price Adjustment',
            width: '10%'
        },
        {
            data: 'creator',
            title: 'Created By',
            width: '15%'
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

        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-3 lg:gap-4">
                        <div class="bg-white p-2 rounded-md shadow">
                            <form @submit.prevent="submit" class="space-y-5 px-2">
                                <p class="mb-1 block text-base font-medium text-[#86591e]">PPMP Information</p>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="create.ppmpType" @change="filterOfficeNoPpmp" name="ppmpType" id="ppmpType" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select PPMP Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="contingency">Contingency</option>
                                    </select>
                                    <label for="ppmpType" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="create.ppmpYear" @change="filterOfficeNoPpmp" name="ppmpYear" id="ppmpYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                    <label for="ppmpYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group" v-if="create.ppmpType == 'contingency'">
                                    <select v-model="create.ppmpSem" name="ppmpSem" id="ppmpSem" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select Semester</option>
                                        <option value="1">1st Semester</option>
                                        <option value="2">2nd Semester</option>
                                    </select>
                                    <label for="ppmpSem" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Semester</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="create.office" name="office" id="office" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select Office</option>
                                        <option v-for="office in officeList.data" :key="office.id" :value="office.id">{{ office.name }}</option>
                                    </select>
                                    <label for="office" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office</label>
                                </div>
                                <div class="pt-4">
                                    <p class="mb-5 block text-base font-medium text-[#86591e]">Upload File</p>

                                    <div class="mb-8 border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 hover:bg-gray-200" @dragover.prevent @drop="onDrop">
                                        <input type="file" ref="fileInput" @change="onFileChange" multiple name="files[]" id="file" class="sr-only" accept=".xls,.xlsx"/>
                                        <label for="file" class="relative flex min-h-[200px] items-center justify-center rounded-md border border-dashed border-[#e0e0e0] p-12 text-center cursor-pointer">
                                            <div class="cursor-pointer w-full mx-auto flex flex-col items-center justify-center">
                                                <svg class="w-10 h-10 text-emerald-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="currentColor">
                                                        <path d="m8 13l1.708 2.5m0 0l1.708 2.5m-1.708-2.5l1.708-2.5m-1.708 2.5L8 18m8.562 0h-.854c-.805 0-1.208 0-1.458-.244s-.25-.637-.25-1.423V13m6.728 0h-.949c-.38 0-.569 0-.719.063c-.502.214-.502.71-.502 1.169v.036c0 .458 0 .955.502 1.169c.15.063.34.063.719.063c.38 0 .569 0 .718.063c.503.214.503.71.503 1.169v.036c0 .458 0 .955-.503 1.169c-.15.063-.339.063-.718.063h-1.034"/>
                                                        <path d="M15 22h-4.273c-3.26 0-4.892 0-6.024-.798a4.1 4.1 0 0 1-.855-.805C3 19.331 3 17.797 3 14.727v-2.545c0-2.963 0-4.445.469-5.628c.754-1.903 2.348-3.403 4.37-4.113C9.095 2 10.668 2 13.818 2c1.798 0 2.698 0 3.416.252c1.155.406 2.066 1.263 2.497 2.35C20 5.278 20 6.125 20 7.818V10"/>
                                                        <path d="M3 12a3.333 3.333 0 0 1 3.333-3.333c.666 0 1.451.116 2.098-.057A1.67 1.67 0 0 0 9.61 7.43c.173-.647.057-1.432.057-2.098A3.333 3.333 0 0 1 13 2"/>
                                                    </g>
                                                </svg>

                                                <span class="mb-2 block text-base font-semibold text-[#545557]">
                                                    Drag and Drop an Excel file here
                                                </span>
                                                <span class="mb-2 block text-base font-medium text-[#6B7280]">
                                                    Or
                                                </span>
                                                <span class="inline-flex rounded border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 text-gray-400 hover:bg-gray-100 hover:text-[#1f2024] py-2 px-7 text-base font-medium">
                                                    Browse
                                                </span>
                                            </div>
                                        </label>
                                    </div>

                                    <div v-if="file">
                                        <h4 class="text-sm font-medium text-[#86591e]">Selected Files: <span class="text-base text-gray-700 italic">{{ file.name }}</span></h4>
                                    </div>
                                </div>

                                <div>
                                    <button
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none"
                                        :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                                        Create PPMP
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="col-span-2 p-2 bg-white rounded-md shadow mt-5 lg:mt-0">
                            <div class="bg-white p-2 overflow-hidden">
                                <div class="relative overflow-x-auto">
                                    <DataTable
                                        class="display table-hover table-striped shadow-lg rounded-lg"
                                        :columns="columns"
                                        :data="officePpmps"
                                        :options="{  paging: true,
                                            searching: true,
                                            ordering: true
                                        }">
                                            <template #action="props">
                                                <ViewButton :href="route('indiv.ppmp.show', { ppmpTransaction: props.cellData.id })" tooltip="View"/>
                                                <RemoveButton @click="openDropPpmpModal(props.cellData)" tooltip="Trash"/>
                                            </template>
                                    </DataTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isDropPpmpModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDropPpmp">
                <input type="hidden" v-model="edit.ppmpId">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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

    :deep([data-v-d08dbe78] table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: left !important;
    }
</style>