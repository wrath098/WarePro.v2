<script setup>
    import { ref, computed, onMounted, watch } from 'vue';
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Swal from 'sweetalert2';
    import Checkbox from '@/Components/Checkbox.vue';
    
    const page = usePage();
    const loading = ref(false);

    const props = defineProps({
        toPr: Object,
    });

    const generatePr = useForm({
        selectedType: '',
        selectedYear: '',
        selectedppmpCode: '',
        semester: '',
        prDesc:'',
        selectedAccounts: [],
        qtyAdjust: ''
    });

    const fetchPpmpTransaction = ref([]);
    const onTypeChange = async () => {
        if (generatePr.selectedType && generatePr.selectedYear) {
            loading.value = true;
            try {
                const response = await axios.get('../../api/ppmp-type', {
                    params: { 
                    type: generatePr.selectedType,
                    year: generatePr.selectedYear,
                    },
                });

                fetchPpmpTransaction.value = response.data;
            } catch (error) {
                console.error('Error fetching office data:', error);
                fetchPpmpTransaction.value = [];
            } finally {
                loading.value = false;
            }
        } else {
            fetchPpmpTransaction.value = [];
        }
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() + 1;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const searchTransaction = ref('');
    const debouncedQuery = ref('');
    const showSuggestions = ref(false);
    const selectionMade = ref(false);
    const adjustment = ref('');
    let timer = null;

    watch(searchTransaction, (newVal) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (selectionMade.value) {
                selectionMade.value = false;
                return;
            }
            debouncedQuery.value = newVal.trim();
            showSuggestions.value = !!debouncedQuery.value;
        }, 500);
    });

    const filterSuggestions = computed(() => {
        if (!Array.isArray(fetchPpmpTransaction.value)) return [];

        if (!debouncedQuery.value) return [];

        return fetchPpmpTransaction.value
            .map(item => item.ppmp_code)
            .filter(code => code.toLowerCase().includes(debouncedQuery.value.toLowerCase()));
        }
    );

    const selectSuggestion = (code) => {
        searchTransaction.value = code;
        generatePr.selectedppmpCode = code;
        showSuggestions.value = false;
        selectionMade.value = true;
        const selected = fetchPpmpTransaction.value.find(item => item.ppmp_code === code);
        adjustment.value = selected.adjustment;
        if (selected?.account_class) {
            generatePr.selectedAccounts = Object.keys(selected.account_class).map(String);
        }
    };

    const selectedTransaction = computed(() => {
        return fetchPpmpTransaction.value.find(
            (item) => item.ppmp_code === searchTransaction.value
        ) ?? null;
    });

    const accountClassList = computed(() => {
        if (!selectedTransaction.value || !selectedTransaction.value.account_class) {
            return [];
        }
        return Object.entries(selectedTransaction.value.account_class).map(([id, name]) => ({
            id,
            name,
        }));
    });

    const nextStep = () => {
        generatePr.get(route('pr.form.step2'), {}, {
            preserveState: true,
        });
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
        <div class="flex items-center justify-center mt-5 rounded-md bg-zinc-300 shadow-sm mb-8">
            <div class="mx-auto w-full max-w-[550px] bg-zinc-100 rounded-md shadow-lg my-4">
                <form @submit.prevent="nextStep">
                    <div class="bg-zinc-600 px-6 py-4 rounded-t-lg">
                        <h3 class="font-bold text-lg leading-6 text-zinc-300">
                            Step 1: Procurement Requests Information
                        </h3>
                        <p class="text-sm text-zinc-300">
                            Fill in the following input fields.
                        </p>
                    </div>
                    <div class="p-4 rounded-md">
                        <div class="mb-5">
                            <label for="ppmpType" class="mb-3 block text-base font-semibold text-[#1a0037]">
                                Items for Procurement
                            </label>
                            <select v-model="generatePr.selectedType" @change="onTypeChange" id="ppmpType" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-semibold text-zinc-700 outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                <option value="" selected disabled>Select Type</option>
                                <option v-for="type in props.toPr" :key="type.ppmpType" :value="type.ppmpType">
                                    {{ type.ppmpType }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-7">
                            <label for="ppmpYear" class="mb-2 block text-base font-semibold text-[#1a0037]">
                                Year of the Selected Type
                            </label>
                            <select v-model="generatePr.selectedYear" @change="onTypeChange" id="ppmpYear" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-semibold text-zinc-700 outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                <option value="" disabled>Select Year</option>
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        <div v-if="loading" class="mb-5">
                            <p class="block italic text-sm font-medium text-[#074d2a]">
                                Loading Transaction...
                            </p>
                        </div>
                        <div v-else-if="fetchPpmpTransaction.length > 0" class="mb-5">
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="searchTransaction" name="transactionCode" type="text" id="transactionCode" autocomplete="off" class="block py-2.5 px-6 w-full text-sm font-semibold text-zinc-700 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="transactionCode" class="font-semibold text-zinc-700 ml-2 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Transaction Code</label>
                            </div>
                            <ul v-if="filterSuggestions.length && showSuggestions" class="border border-gray-300 rounded max-h-48 overflow-auto bg-white shadow-md">
                                <li v-for="code in filterSuggestions" :key="code" @click="selectSuggestion(code)" class="px-4 py-2 hover:bg-blue-100 cursor-pointer">
                                    {{ code }}
                                </li>
                            </ul>
                            <div v-if="filterSuggestions.length == 0 && showSuggestions" class="border border-gray-300 rounded max-h-48 overflow-auto bg-white shadow-md">
                                <p class="px-4 py-2 italic text-rose-600">No available/approved transaction found!</p>
                            </div>
                        </div>
                        <div v-else class="mb-5 italic text-rose-600">
                            <p>{{ fetchPpmpTransaction.message }}</p>
                        </div>
                        
                        <div v-if="generatePr.selectedType == 'Consolidated' && fetchPpmpTransaction.length > 0" class="-mx-3 flex flex-wrap">
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="mb-5">
                                    <label for="semester" class="mb-3 block text-base font-semibold text-[#1a0037]">
                                        Milestone
                                    </label>
                                    <select v-model="generatePr.semester" id="semester" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-semibold text-zinc-700 outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                        <option value="" disabled>Select to Semester</option>
                                        <option value="qty_first">January (1st Semester)</option>
                                        <option value="qty_second">May (2nd Semester)</option>
                                    </select> 
                                </div>
                            </div>
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="mb-5">
                                    <label for="prDescription" class="mb-3 block text-base font-semibold text-[#1a0037]">
                                        Mode of Procurement
                                    </label>
                                    <select v-model="generatePr.prDesc" id="prDescription" class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-6 text-base font-semibold text-zinc-700 outline-none focus:border-[#6A64F1] focus:shadow-md" required>
                                        <option value="" disabled>Select Description</option>
                                        <option value="dc">Direct Contract</option>
                                        <option value="emergency">Emergency</option>
                                        <option value="nc">For Bidding</option>
                                        <option value="psdbm">PS-DBM</option>
                                    </select> 
                                </div>
                            </div>
                            <div v-if="accountClassList.length">
                                <div class="w-full px-3">
                                    <div class="mb-5">
                                        <label for="semester" class="mb-3 block text-base font-semibold text-[#1a0037]">
                                            Check Account Classification
                                        </label>
                                        <div v-for="account in accountClassList" :key="account.id" class="flex space-x-2 mb-2">
                                            <Checkbox v-model:checked="generatePr.selectedAccounts" :value="account.id" />
                                            <span class="text-sm text-gray-800">{{ account.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full p-3 mb-5 bg-amber-300 rounded-lg">
                                    <div class="flex items-start space-x-3">
                                        <Checkbox 
                                            v-model="generatePr.qtyAdjust" 
                                            :value="true" 
                                            aria-describedby="threshold-description"
                                        />
                                        <p id="threshold-description" class="text-sm text-gray-800 text-justify">
                                            If checked, the values will be based on the set final quantity adjustment on consolidated ppmp with <span class="font-semibold">({{ adjustment }}%)</span>. 
                                            If unchecked, the system will use the adjusted quantity based on the proposed budget.
                                        </p>
                                    </div>
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