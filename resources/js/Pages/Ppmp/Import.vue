<script setup>
    import { Head, router } from '@inertiajs/vue3';
    import { reactive, ref, computed } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';

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

        router.post('ppmp/create', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
    };

    const submitForm = (url, data) => {
        Inertia.post(url, data, {
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };

    const submitDropPpmp = () => submitForm('ppmp/drop', edit);
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Project Procurement Management Plan</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Import</a></li>
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
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submit" class="space-y-5">
                                <p class="mb-1 block text-base font-medium text-[#86591e]">PPMP Information</p>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="create.ppmpType" name="ppmpType" id="ppmpType" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select PPMP Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="contingency">Contingency</option>
                                    </select>
                                    <label for="ppmpType" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="create.ppmpYear" name="ppmpYear" id="ppmpYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
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
                                        <option v-for="office in props.offices" :key="office.id" :value="office.id">{{ office.name }}</option>
                                    </select>
                                    <label for="office" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office</label>
                                </div>
                                <div class="pt-4">
                                    <p class="mb-5 block text-base font-medium text-[#86591e]">Upload File</p>

                                    <div class="mb-8 border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 hover:bg-gray-200" @dragover.prevent @drop="onDrop">
                                        <input type="file" ref="fileInput" @change="onFileChange" multiple name="files[]" id="file" class="sr-only" accept=".xls,.xlsx"/>
                                        <label for="file" class="relative flex min-h-[200px] items-center justify-center rounded-md border border-dashed border-[#e0e0e0] p-12 text-center cursor-pointer">
                                            <div class="cursor-pointer">
                                                <span class="mb-2 block text-base font-semibold text-[#545557]">
                                                    Drop an Excel file here
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
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Create PPMP
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-5/12">Office Code</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">PPMP No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">PPMP Type</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(ppmp, index) in officePpmps" :key="ppmp.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ ppmp.officeCode }}</td>
                                                <td class="px-6 py-3">{{ ppmp.ppmpCode }}</td>
                                                <td class="px-6 py-3">{{ ppmp.ppmpType }}</td>
                                                <td class="px-6 py-3">{{ ppmp.basedPrice }}</td>
                                                <td class="px-6 py-3">
                                                    <ViewButton :href="route('indiv.ppmp.show', { ppmpTransaction: ppmp.id })" tooltip="View"/>
                                                    <RemoveButton @click="openDropPpmpModal(ppmp)" tooltip="Trash"/>
                                                </td>
                                            </tr>
                                        </tbody>
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
    </AuthenticatedLayout>
    </div>
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
</style>