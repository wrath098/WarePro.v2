<script setup>
    import { Head, router } from '@inertiajs/vue3';
    import { ref, computed, reactive } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Copy from '@/Components/Buttons/Copy.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    
    const props = defineProps({
        ppmpTransaction: Object,
        ppmp: Object,
        types: Object,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isCopyModalOpen = computed(() => modalState.value === 'copy');

    const filteredYears = ref([]);
    const makeCopy = reactive({
        selectedType: '',
        selectedYear: '',
        qtyAdjust: '',
    });

    const onTypeChange = (context) => {
        const type = props.types.find(typ => typ.ppmp_type === context.selectedType);
        filteredYears.value = type ? type.years : [];
        makeCopy.selectedYear = '';
    };

    const submitForm = (url, data) => {
        router.post(url, data, {
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };
    const submitCopy = () => submitForm('copy-ppmp', makeCopy);
</script>

<template>
    <Head title="Office" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3 flex justify-between"> 
                <ol class="flex space-x-2 leading-none">
                    <li><a class="after:content-['/'] after:ml-2 text-gray-600 hover:text-green-700">Project Procurement and Manangement Plan</a></li>
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.type }}</li> 
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.status }}</li> 
                    <li v-if="ppmp.status == 'Draft'"><Copy @click="showModal('copy')" class="mr-10" tooltip="Make a Copy"/></li>
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto text-center">
                        <DataTable class="w-full text-gray-900 display">
                            <thead class="text-sm text-gray-100 uppercase bg-indigo-600 text-left">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12 text-left">No#</th>
                                    <th scope="col" class="px-6 py-3 w-2/12 text-left">Transaction No.</th>
                                    <th scope="col" class="px-6 py-3 w-1/12 text-left">Office Code</th>
                                    <th scope="col" class="px-6 py-3 w-3/12 text-left">Office Name</th>
                                    <th scope="col" class="px-6 py-3 w-1/12 text-left">Calendar Year</th>
                                    <th scope="col" class="px-6 py-3 w-1/12 text-left">Version</th>
                                    <th scope="col" class="px-6 py-3 w-2/12 text-left">Created/Updated By</th>
                                    <th scope="col" class="px-6 py-3 w-1/12 text-left">Action/s</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transaction, index) in ppmpTransaction" :key="transaction.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td class="px-6 py-3">{{ index + 1 }}</td>
                                    <td class="px-6 py-3">{{ transaction.ppmp_code }}</td>
                                    <td class="px-6 py-3 text-left">{{ transaction.requestee.office_code }}</td>
                                    <td class="px-6 py-3 text-left">{{ transaction.requestee.office_name }}</td>
                                    <td class="px-6 py-3">{{ transaction.ppmp_year }}</td>
                                    <td class="px-6 py-3">v.{{ transaction.ppmp_version }}</td>
                                    <td class="px-6 py-3">{{ transaction.updater.name }}</td>
                                    <td class="px-6 py-3">
                                        <Print :href="route('generatePdf.IndividualPpmp', { ppmp: transaction.id})" tooltip="Print"></Print>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
        <Modal :show="isCopyModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitCopy">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Make a Copy of PPMP</h3>
                            <p class="text-sm text-gray-500"> Enter the details for creating a copy of PPMP you wish to copy and modify.</p>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Select PPMP Type: </p>
                                <select v-model="makeCopy.selectedType" @change="onTypeChange(makeCopy)" class="p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    <option value="" disabled>Please choose the PPMP Type</option>
                                    <option v-for="type in props.types" :key="type.ppmp_type" :value="type.ppmp_type">
                                        {{ type.ppmp_type }}
                                    </option>
                                </select>

                                <select v-model="makeCopy.selectedYear" v-if="filteredYears.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    <option value="" disabled>Please choose the PPMP Year</option>
                                    <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">
                                        {{ year.ppmp_year }}
                                    </option>
                                </select>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Quantity Adjustment: <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span></p>
                                <input v-model="makeCopy.qtyAdjust"  
                                    type="number"
                                    id="qtyAdjust"
                                    min="50"
                                    max="100"
                                    placeholder="Enter the percentage from 50 to 100"
                                    class="w-full rounded-md border border-[#e0e0e0] bg-white py-2 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md"
                                    required
                                />
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
    </div>
</template>
 