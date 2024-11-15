<script setup>
    import { reactive, ref } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Sidebar from '@/Components/Sidebar.vue';

    const props = defineProps({
        toPr: Object,
    });

    const filteredYear = ref([]);
    const filteredPpmpList = ref([]);
    const generatePr = reactive({
        selectedType: '',
        selectedYear: '',
        selectedppmpCode: '',
        semester: '',
        prDesc:'',
        qtyAdjust: 70,
    });

    const onTypeChange = (context) => {
        const type = props.toPr.find(typ => typ.ppmp_type === context.selectedType);
        filteredYear.value = type ? type.years : [];
        generatePr.selectedYear = '';
    };

    const onYearChange = (context) => {
        const type = props.toPr.find(typ => typ.ppmp_type === context.selectedType);
        const year = type ? type.years.find(yer => yer.ppmp_year === context.selectedYear) : null;
        filteredPpmpList.value = year ? year.ppmpNo : [];
        generatePr.selectedppmpCode = '';
    };

    const nextStep = () => {
        Inertia.get('step-2', generatePr);
    };
</script>
<template>
    <Head title="PR" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Purchase Request</li> 
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

                        <div class="mx-2 w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0 flex justify-center">
                            <div class="bg-white w-1/3 p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <form @submit.prevent="nextStep" class="space-y-5 mx-2">
                                        <h3 class="my-2 text-[#07074D]">
                                            Step 1: Project Procurement Management Plan Information
                                        </h3>
                                        <div>
                                            <select v-model="generatePr.selectedType" @change="onTypeChange(generatePr)" id="ppmpType" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                                <option value="" selected>Please choose PPMP Type</option>
                                                <option v-for="type in props.toPr" :key="type.ppmp_type" :value="type.ppmp_type">
                                                    {{ type.ppmp_type }}
                                                </option>
                                            </select>

                                            <select v-model="generatePr.selectedYear" @change="onYearChange(generatePr)" v-if="filteredYear.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                                <option value="" disabled>Please choose the PPMP Year</option>
                                                <option v-for="year in filteredYear" :key="year.ppmp_year" :value="year.ppmp_year">
                                                    {{ year.ppmp_year }}
                                                </option>
                                            </select>
                                            
                                            <select v-model="generatePr.selectedppmpCode" v-if="filteredPpmpList.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                                <option value="" disabled>Please choose the Consolidated PPMP</option>
                                                <option v-for="code in filteredPpmpList" :key="code" :value="code">
                                                    {{ code }}
                                                </option>
                                            </select>
                                        </div>

                                        <div v-if="generatePr.selectedType == 'consolidated'" class="mt-5" >
                                            <select v-model="generatePr.semester" class="mb-1 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                                <option value="" disabled>Select Semester</option>
                                                <option value="qty_first">1st</option>
                                                <option value="qty_second">2nd</option>
                                            </select> 

                                            <select v-model="generatePr.prDesc" class="mb-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                                <option value="" disabled>Purchase Request Description</option>
                                                <option value="nc">Non-Contract</option>
                                                <option value="dc">Direct Contract</option>
                                                <option value="psdbm">PS-DBM</option>
                                            </select>  

                                            <p class="text-sm text-gray-500"> Quantity Adjustment: <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span></p>
                                            <input 
                                                v-model="generatePr.qtyAdjust"
                                                type="number"
                                                id="qtyAdjust"
                                                min="50"
                                                max="100"
                                                placeholder="Enter from 50 to 100"
                                                class="w-full rounded-md border border-[#e0e0e0] bg-white py-2 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md"
                                                readonly
                                                />
                                        </div>

                                        <div>
                                            <button
                                                class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                                Next
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>