<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import AddButton from '@/Components/Buttons/AddButton.vue';
import Swal from 'sweetalert2';
import useAuthPermission from '@/Composables/useAuthPermission';
import InputError from '@/Components/InputError.vue';

const {hasAnyRole, hasPermission} = useAuthPermission();
const page = usePage();
const isLoading = ref(false);
const activeYear = ref('');

const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

const props = defineProps({
    generalFund: Object,
    availableYears: Object,
    accountClassification: Object,
});

const newBudgetInformation = ref([]);
const isFetchClick = ref(false);

const fetchNewBudgetInfo = async (year) => {
    if (year) {
        isLoading.value = true;
        activeYear.value = year;
        try {
            const response = await axios.get('general-servies-fund/show-fund-by-year', { 
                params: { year: year},
            });
            newBudgetInformation.value = response.data;
            isFetchClick.value = true;
            isLoading.value = false;
            return newBudgetInformation;
        } catch (error) {
            isFetchClick.value = false;
            isLoading.value = false;
            console.error('Error fetching product data:', JSON.stringify(error));
        }
    }
};

const years = generateYears();
function generateYears() {
    const currentYear = new Date().getFullYear() + 2;
    return Array.from({ length: 3 }, (_, i) => currentYear - i);
}

const modalState = ref(null);
const showModal = (modalType) => { modalState.value = modalType; }
const closeModal = () => { modalState.value = null; }
const isAddModalOpen = computed(() => modalState.value === 'add');

const storeFund = useForm({
    fundId: '',
    year: '',
    amount: '',
});

const submit = () => {
    isLoading.value = true;

    storeFund.post(route('general.fund.store.amount'), {
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            if (errMessage.value) {
                Swal.fire({
                    title: 'Failed',
                    text: errMessage.value,
                    icon: 'error',
                });
            } else {
                storeFund.reset();
                Swal.fire({
                    title: 'Success',
                    text: message.value,
                    icon: 'success',
                }).then(() => closeModal());
            }
        },
        onError: (errors) => {
            isLoading.value = false;
        },
    });
};


onMounted(() => {
    if (message.value) {
        Swal.fire({
            title: 'Success',
            text: message.value,
            icon: 'success',
            confirmButtonText: 'OK',
        });
    }

    if (errMessage.value) {
        Swal.fire({
            title: 'Failed',
            text: errMessage.value,
            icon: 'error',
            confirmButtonText: 'OK',
        });
    }
});

const formatDecimal = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value);
};
</script>

<template>
    <Head title="Proposed Budget" />
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
                            Components
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('general.fund.display')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Proposed Budget
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="hasAnyRole(['Developer']) || hasPermission('create-new-budget')">
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">New Budget Allocation</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="grid grid-cols-1 gap-0 lg:grid-cols-4 lg:gap-4">
                    <section class="py-6 bg-white rounded-md m-3 lg:m-0">
                        <div class="px-4 mx-auto sm:px-6 lg:px-8">
                            <div class="max-w-2xl mx-auto text-center">
                                <h2 class="text-3xl font-bold leading-tight text-black">
                                    Available Years
                                </h2>
                            </div>
                            <div v-for="(year, index) in availableYears" :key="index" class="max-w-3xl mx-auto mt-2 space-y-4 md:mt-4">
                                <div 
                                    class="transition-all duration-200 bg-white border-2 border-indigo-400 shadow-lg cursor-pointer hover:bg-indigo-400"
                                    :class="{'border-indigo-600 bg-indigo-400': activeYear === year}"
                                >
                                    <button @click="fetchNewBudgetInfo(year)" type="button" class="flex items-center justify-between w-full px-4 py-3">
                                        <span class="flex text-lg font-semibold text-black">{{ year }}</span>
                                        <svg class="w-6 h-6 text-black" stroke="currentColor" viewBox="0 0 208 456" xmlns="http://www.w3.org/2000/svg">
                                            <path fill="currentColor" d="M9 388q8 4 15 4q11 0 17-6l162-186L41 14Q26-1 11 12Q-4 29 9 42l137 156L9 354q-13 19 0 34z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-center text-gray-600 textbase mt-9">
                                Select the desired year to display the designated budget and its breakdown!
                            </p>
                        </div>
                    </section>
                    <ul v-if="!isFetchClick" class="col-span-3 bg-white rounded-md">
                        <li v-for="(yearData, year) in generalFund" :key="year" class="bg-white rounded-md pb-2 mb-2">
                            <div class="flex-1 flex items-start justify-between rounded-lg bg-indigo-600 p-2">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-lg font-semibold text-gray-50">{{ year }} | Summary Overview</h2>
                                </div>
                                <div v-if="hasAnyRole(['Developer']) || hasPermission('edit-proposed-budget')" class="flex items-center">
                                    <a :href="route('general.fund.editFundAllocation', { budgetDetails: yearData, year: year})" class="flex items-center rounded-md transition text-gray-100 px-1 hover:text-indigo-800 hover:bg-gray-100">
                                        <span class="mr-1">Edit</span>
                                        <svg class="w-7 h-7" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                                            <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>                           
                            <div v-for="account in yearData.funds" :key="account.id" class="flow-root py-3 m-4 border-2 border-indigo-400 ">
                                <dl class="-my-3 divide-y divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-indigo-100 sm:grid-cols-3 sm:gap-4 border-b-2 border-indigo-400">
                                        <dt class="font-medium text-indigo-900 sm:col-span-2">{{ account.accountClass }} </dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(account.amount) }}</dd>
                                    </div>
                                    <div v-for="allocation in account.allocations" :key="allocation.id" class="grid grid-cols-1 gap-1 pl-10 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-medium text-gray-900 sm:col-span-2">{{ allocation.semester }} - {{ allocation.description }}</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(allocation.amount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="flow-root py-3 m-4">
                                <dl class="-my-3 divide-y divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-indigo-900 sm:grid-cols-3 sm:gap-4 rounded-lg shadow-sm text-gray-100">
                                        <dt class="font-medium">Total Amount</dt>
                                        <dd class="sm:col-span-2 text-right">{{ formatDecimal(yearData.totalAmount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </li>
                    </ul>

                    <ul v-if="isFetchClick" class="col-span-3 bg-white rounded-md">
                        <li v-for="(yearData, year) in newBudgetInformation.data" :key="year" class="bg-white rounded-md pb-2 mb-2">
                            <div class="flex-1 flex items-start justify-between rounded-lg bg-indigo-600 p-2">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-lg font-semibold text-gray-50">{{ year }} | Summary Overview</h2>
                                </div>
                                <div class="flex items-center">
                                    <a :href="route('general.fund.editFundAllocation', { budgetDetails: yearData, year: year})" class="flex items-center rounded-full transition" title="Edit">
                                        <span class="sr-only">Open options</span>
                                        <svg class="w-8 h-8 text-white hover:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                                            <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>                       
                            <div v-for="account in yearData.funds" :key="account.id" class="flow-root py-3 m-4 border-2 border-indigo-400 ">
                                <dl class="-my-3 divide-y divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-indigo-100 sm:grid-cols-3 sm:gap-4 border-b-2 border-indigo-400">
                                        <dt class="font-bold text-indigo-900 sm:col-span-2">{{ account.accountClass }} </dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(account.amount) }}</dd>
                                    </div>
                                    <div v-for="allocation in account.allocations" :key="allocation.id" class="grid grid-cols-1 gap-1 pl-10 p-3 even:bg-gray-200/90 sm:grid-cols-3 sm:gap-4">
                                        <dt class="font-bold text-gray-900 sm:col-span-2">{{ allocation.semester }} - {{ allocation.description }}</dt>
                                        <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(allocation.amount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="flow-root py-3 m-4">
                                <dl class="-my-3 divide-y divide-gray-100 text-base">
                                    <div class="grid grid-cols-1 gap-1 p-3 bg-indigo-900 sm:grid-cols-3 sm:gap-4 rounded-lg shadow-sm text-gray-100">
                                        <dt class="font-bold">Total Amount</dt>
                                        <dd class="sm:col-span-2 text-right">{{ formatDecimal(yearData.totalAmount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isAddModalOpen" @close="closeModal"> 
        <form @submit.prevent="submit">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Designate Budget</h3>
                        <p class="text-sm text-gray-500"> Enter the details for the new Budget you wish to add.</p>
                        <div class="mt-10">
                            <div class="relative z-0 w-full my-3 group">
                                <select v-model="storeFund.year" name="year" id="year" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Select year</option>
                                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                </select>
                                <label for="year" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                                <InputError class="mt-2" :message="storeFund.errors.year" />
                            </div>
                            <div class="relative z-0 w-full group my-2">
                                <select v-model="storeFund.fundId" name="accountId" id="accountId" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected>Please choose the Account Classification applicable</option>
                                    <option v-for="account in accountClassification" :key="account.id" :value="account.id">
                                        {{ account.account }}
                                    </option>
                                </select>
                                <label for="accountId" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Account Classification</label>
                                <InputError class="mt-2" :message="storeFund.errors.fundId" />
                            </div>
                            <div class="relative z-0 w-full group my-3">
                                <input v-model="storeFund.amount" type="text" name="amount" id="amount" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="amount" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Designated Amount</label>
                                <InputError class="mt-2" :message="storeFund.errors.amount" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
        </form>
    </Modal>
</template>
 