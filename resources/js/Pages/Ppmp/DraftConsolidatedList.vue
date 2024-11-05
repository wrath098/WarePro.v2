<script setup>
    import { Head } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { reactive, ref, computed } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Generate from '@/Components/Buttons/Generate.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';



    const props = defineProps({
        ppmp: Object,
        transactions: Object,
        individualList: Object,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isConsolidateModalOpen = computed(() => modalState.value === 'copy');

    const filteredYears = ref([]);
    const filteredVersion = ref([]);
    const generateConsolidated = reactive({
        selectedType: '',
        selectedYear: '',
        selectedVersion: '',
        priceAdjust:''
    });

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

    const submitForm = (url, data) => {
        Inertia.post(url, data, {
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };
    const submitConsolidated = () => submitForm('create-consolidated', generateConsolidated);
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-gray-600 hover:text-green-700">Project Procurement and Manangement Plan</a></li>
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.type }}</li> 
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.status }}</li> 
                    <li v-if="ppmp.status == 'Draft'"><Generate @click="showModal('copy')" class="" tooltip="Generate"/></li>
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
                        <div class="mx-2 w-full bg-white rounded-md shadow">
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <div class="p-6 text-gray-900 text-center">
                                        <DataTable class="w-full text-gray-900 display">
                                            <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                                <tr class="text-center">
                                                    <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Transaction No.</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">PPMP Year</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Version</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Price Adjustment</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Quantity Adjustment</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Created/Updated By</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(transaction, index) in transactions" :key="transaction.code">
                                                    <td class="px-6 py-3">{{ index + 1 }}</td>
                                                    <td class="px-6 py-3">{{ transaction.code }}</td>
                                                    <td class="px-6 py-3">{{ transaction.ppmpYear }}</td>
                                                    <td class="px-6 py-3">v.{{ transaction.version }}</td>
                                                    <td class="px-6 py-3">{{ transaction.priceAdjust }}</td>
                                                    <td class="px-6 py-3">{{ transaction.qtyAdjust }}</td>
                                                    <td class="px-6 py-3">{{ transaction.updatedBy }}</td>
                                                    <td class="px-6 py-3" v-if="props.ppmp.status == 'Draft'">
                                                        <ViewButton :href="route('conso.ppmp.show', { ppmpTransaction: transaction.id })" tooltip="View"/>
                                                        <RemoveButton @click="''" tooltip="Trash"/>
                                                    </td>
                                                    <td class="px-6 py-3" v-if="props.ppmp.status == 'Approved'">
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
        </div>
    </AuthenticatedLayout>
    <Modal :show="isConsolidateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitConsolidated">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">Consolidate PPMP</h3>
                            <p class="text-sm text-gray-500"> Enter the details to generate a consolidated PPMP you wish to consolidate.</p>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Select PPMP Type: </p>
                                <select v-model="generateConsolidated.selectedType" @change="onTypeChange(generateConsolidated)" class="p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    <option value="" disabled>Please choose the PPMP Type</option>
                                    <option v-for="type in props.individualList" :key="type.ppmp_type" :value="type.ppmp_type">
                                        {{ type.ppmp_type }}
                                    </option>
                                </select>

                                <select v-model="generateConsolidated.selectedYear" v-if="filteredYears.length" @change="onYearChange(generateConsolidated)" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    <option value="" disabled>Please choose the PPMP Year</option>
                                    <option v-for="year in filteredYears" :key="year.ppmp_year" :value="year.ppmp_year">
                                        {{ year.ppmp_year }}
                                    </option>
                                </select>

                                <select v-model="generateConsolidated.selectedVersion" v-if="filteredVersion.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                    <option value="" disabled>Please choose the PPMP Version</option>
                                    <option v-for="version in filteredVersion" :key="version.version" :value="version.version">
                                        {{ version.version }}
                                    </option>
                                </select>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-gray-500"> Price Adjustment: <span class="text-sm text-[#8f9091]">Value: 100% - 120%</span></p>
                                <input v-model="generateConsolidated.priceAdjust"  
                                    type="number"
                                    id="priceAdjust"
                                    min="100"
                                    max="120"
                                    placeholder="Enter the percentage from 100 to 120"
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
