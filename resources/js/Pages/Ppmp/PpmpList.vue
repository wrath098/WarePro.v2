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

            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2 leading-none">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Project Procurement Management Plan</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Individual</a></li>
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">{{ props.ppmp.status }}</li>
                    <li v-if="ppmp.status == 'Draft'"><Copy @click="showModal('copy')" class="mr-10" tooltip="Quantity Adjustment"/></li>
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
                                    <td class="px-6 py-3 text-center">
                                        <Print :href="route('generatePdf.IndividualPpmp', { ppmp: transaction.id})" tooltip="Initial"></Print>
                                        <Print v-if="transaction.tresh_adjustment > 0 && transaction.tresh_adjustment < 1" :href="route('generatePdf.IndividualPpmp.withAdjustment', { ppmp: transaction.id})" tooltip="Adjusted"></Print>
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Quantity Adjustment</h3>
                            <p class="text-sm text-gray-500"> Enter the details to create a copy of the Individual PPMP with Quantity Adjustment. This will serve as the threshold for releasing items per office.</p>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]">PPMP Information</p>
                                <div class="relative z-0 w-full my-3 group">
                                    <select v-model="makeCopy.selectedType" @change="onTypeChange(makeCopy)" name="selectedType" id="selectedType" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select the PPMP Type</option>
                                        <option v-for="type in props.types" :key="type.ppmp_type" :value="type.ppmp_type">{{ type.ppmp_type }}</option>
                                    </select>
                                    <label for="selectedType" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                                </div>
                                <div class="relative z-0 w-full my-3 group" v-if="filteredYears.length">
                                    <select v-model="makeCopy.selectedYear" name="selectedYear" id="selectedYear" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                        <option value="" disabled selected>Select the PPMP Year</option>
                                        <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">{{ year.ppmp_year }}</option>
                                    </select>
                                    <label for="selectedYear" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Year</label>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]"> Quantity Adjustment: <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span></p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="makeCopy.qtyAdjust" type="number" min="50" max="100" name="qtyAdjust" id="qtyAdjust" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="qtyAdjust" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Qty Adjustment</label>
                                </div>
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
 