<script setup>
    import { ref, computed, onMounted } from 'vue';
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Swal from 'sweetalert2';
    
    const page = usePage();

    const props = defineProps({
        toPr: Object,
    });

    const filteredYear = ref([]);
    const filteredPpmpList = ref([]);
    const generatePr = useForm({
        selectedType: '',
        selectedYear: '',
        selectedppmpCode: '',
        semester: '',
        prDesc:'',
        qtyAdjust: 100,
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
        generatePr.get(route('pr.form.step2'));
    };

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
</script>
<template>
    <Head title="Purchase Request" />
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
                            Purchase Request
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('pr.form.step1')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Step 1
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="flex items-center justify-center mt-5 rounded-md bg-white shadow-md">
            <div class="mx-auto w-full max-w-[550px] bg-white rounded-md border-2 border-indigo-900 shadow-lg my-4">
                <form @submit.prevent="nextStep">
                    <div class="px-6 py-4 bg-indigo-900 text-white rounded-t">
                        <h1 class="text-lg font-bold">Step 1: Procurement Requests Information</h1>
                    </div>
                    <div class="p-4 rounded-md">
                        <div class="mb-5">
                            <label for="ppmpType" class="mb-3 block text-base font-medium text-[#07074D]">
                                Items for Procurement
                            </label>
                            <select v-model="generatePr.selectedType" @change="onTypeChange(generatePr)" id="ppmpType" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                <option value="" selected disabled>Select Type</option>
                                <option v-for="type in props.toPr" :key="type.ppmp_type" :value="type.ppmp_type">
                                    {{ type.ppmp_type }}
                                </option>
                            </select>
                        </div>
                        <div v-if="filteredYear.length" class="mb-5">
                            <label for="ppmpYear" class="mb-3 block text-base font-medium text-[#07074D]">
                                Year of the Selected Type
                            </label>
                            <select v-model="generatePr.selectedYear" @change="onYearChange(generatePr)" id="ppmpYear" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                <option value="" disabled>Select Year</option>
                                <option v-for="year in filteredYear" :key="year.ppmp_year" :value="year.ppmp_year">
                                    {{ year.ppmp_year }}
                                </option>
                            </select>
                        </div>
                        <div v-if="filteredPpmpList.length" class="mb-5">
                            <label for="ppmpCode" class="mb-3 block text-base font-medium text-[#07074D]">
                                Transaction No#
                            </label>
                            <select v-model="generatePr.selectedppmpCode"  id="ppmpCode" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                <option value="" disabled>Select Transaction</option>
                                <option v-for="code in filteredPpmpList" :key="code" :value="code">
                                    {{ code }}
                                </option>
                            </select>
                        </div>
                        <div v-if="generatePr.selectedType == 'Consolidated'" class="-mx-3 flex flex-wrap">
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="mb-5">
                                    <label for="semester" class="mb-3 block text-base font-medium text-[#07074D]">
                                        Semester-Based Procurement
                                    </label>
                                    <select v-model="generatePr.semester" id="semester" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                        <option value="" disabled>Select to Procure</option>
                                        <option value="qty_first">January (1st Semester)</option>
                                        <option value="qty_second">May (2nd Semester)</option>
                                    </select> 
                                </div>
                            </div>
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="mb-5">
                                    <label for="prDescription" class="mb-3 block text-base font-medium text-[#07074D]">
                                        Mode of Procurement
                                    </label>
                                    <select v-model="generatePr.prDesc" id="prDescription" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                        <option value="" disabled>Select Description</option>
                                        <option value="nc">For Bidding</option>
                                        <option value="dc">Direct Contract</option>
                                        <option value="psdbm">PS-DBM</option>
                                    </select> 
                                </div>
                            </div>
                            <div class="w-full px-3">
                                <div class="mb-5">
                                    <label for="qtyAdjust" class="mb-3 block text-base font-medium text-[#07074D]">
                                        Quantity to Procure:
                                        <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span>
                                    </label>
                                    <input 
                                        v-model="generatePr.qtyAdjust"
                                        type="number"
                                        id="qtyAdjust"
                                        min="50"
                                        max="100"
                                        placeholder="Enter from 50 to 100"
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-medium text-[#6B7280] outline-none focus:border-[#6A64F1] focus:shadow-md"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <button
                                class="hover:shadow-form w-full rounded-md bg-[#6A64F1] py-3 px-8 text-center text-base font-semibold text-white outline-none">
                                Next
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>