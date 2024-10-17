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
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Project Procurement and Manangement Plan</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label for="ppmpType" class="mb-1 block text-base font-medium text-[#07074D]">
                                        PPMP Type:
                                    </label>
                                    <select v-model="create.ppmpType" id="ppmpType" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option value="" selected>Please choose PPMP Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="contingency">Contingency</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        PPMP for CY:
                                    </label>
                                    <select v-model="create.ppmpYear" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" selected>Please choose year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Office
                                    </label>
                                    <select v-model="create.office" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" selected>Please choose the office</option>
                                        <option v-for="office in props.offices" :key="office.id" :value="office.id">{{ office.name }}</option>
                                    </select>
                                </div>

                                <div class="pt-4">
                                    <label class="mb-5 block text-xl font-semibold text-[#07074D]">
                                        Upload File
                                    </label>

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
                                        <h4 class="text-lg font-semibold">Selected Files:</h4>
                                        <ul class="mt-2">
                                            <li class="text-[#07074D]">
                                                {{ file.name }}
                                            </li>
                                        </ul>
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
                                                <th scope="col" class="px-6 py-3 w-1/6">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Office Code</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP Type</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Price Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Action/s</th>
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