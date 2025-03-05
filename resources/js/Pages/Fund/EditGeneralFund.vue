<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
import Swal from 'sweetalert2';

const page = usePage();
const isLoading = ref(false);

const props = defineProps({
    budgetDetails: Object,
    year: String,
})

const updateBudget = reactive({...props.budgetDetails});

const modalState = ref(false);
const closeModal = () => { modalState.value = false; }
const showModal = () => { modalState.value = true; }

const submitForm = () => {
    showModal();
};

const confirmSubmit = () => {
    isLoading.value = true;
    const data = { year: props.year, ...updateBudget};
    Inertia.put('update-fund-allocations', data, {
        onSuccess: () => {
            closeModal();
            isLoading.value = false;
        },
    });
};

const message = computed(() => page.props.flash.message);
const errMessage = computed(() => page.props.flash.error);

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

const getTotalContingency = (account) => {
    const accountClass = props.budgetDetails.funds.find(fund => fund.id === account.id);
    const calculateUnContingency = accountClass.allocations
        .filter(fund => fund.description !== 'Contingency')
        .reduce((total, fund) => total + Number(fund.amount), 0);
    const totalContingency =  accountClass.amount - calculateUnContingency;
    return totalContingency;
};

const balanceAmount = (accountClass, account) => {
    const contingency = getTotalContingency(accountClass);
    const fund = updateBudget.funds.find(fund => fund.id === accountClass.id);
    
    if (fund) {
        const fundAllocations = fund.allocations.filter(allocation => allocation.description === account.description && allocation.id !== account.id);
        const calculateContingencyLeft = contingency - account.amount;

        if (calculateContingencyLeft >= 0 && fundAllocations.length > 0) {
            fundAllocations[0].amount = calculateContingencyLeft;
        } else {
            Swal.fire({
                title: 'Warning!',
                text: 'Insufficient contingency or no matching allocations. Available Contingency: ' + contingency,
                icon: 'warning',
                confirmButtonText: 'OK',
            });
        }
    } else {
        Swal.fire({
            title: 'Failed',
            text: 'Fund not found',
            icon: 'error',
            confirmButtonText: 'OK',
        });
    }
};
</script>
<template>
    <Head title="General Fund" />
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
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('general.fund.display')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Proposed Budget
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Edit
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-white shadow rounded-md mb-8">
                <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 rounded-md">
                    <div class="flex flex-wrap">
                        <p class="text-base text-gray-100">
                            <strong class="font-semibold">Proposed Budget for {{ year }}</strong>
                            <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                            <span class="text-sm">Please enter the required data in the input field.</span>
                        </p>
                    </div>
                </div>
                <div>
                <form @submit.prevent="submitForm">
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2" for="card_number">
                                Total Amount
                            </label>
                            <input
                                v-model="updateBudget.totalAmount"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                id="grid-first-name" type="number" placeholder="Total Amount" readonly>
                        </div>
                        <div class="relative isolate flex overflow-hidden bg-gray-600 px-6 py-2.5 rounded-md">
                            <div class="flex flex-wrap">
                                <p class="text-base text-gray-100">
                                    <strong class="font-semibold">Budget Allocations</strong>
                                </p>
                            </div>
                        </div>
                        <div v-for="account in updateBudget.funds" :key="account.id" class="my-4 mx-4 bg-blue-100/60 shadow-md rounded-md p-2">
                            <label class="block text-gray-700 font-bold mb-2" :for="'account-' + account.id">
                                {{ account.accountClass }}
                            </label>
                            <input
                                v-model="account.amount"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                :id="'account-' + account.id" 
                                type="number" 
                                :disabled="account.allocations != null" 
                                placeholder="" 
                                required
                            >
                            <div class="grid lg:grid-cols-2 md:grid-cols-2 gap-4 my-5">
                                <div v-for="allocation in account.allocations" :key="allocation.id" class="my-2 mx-5">
                                    <label class="block text-gray-700 font-bold mb-2" :for="'allocation-' + allocation.id">
                                        {{ allocation.semester }} - {{ allocation.description }}
                                    </label>
                                    <input
                                        v-model="allocation.amount"
                                        class="w-full shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline disabled:bg-gray-300"
                                        :id="'allocation-' + allocation.id" 
                                        type="number" 
                                        placeholder="" 
                                        :disabled="allocation.description !== 'Contingency'"
                                        @keyup="balanceAmount(account, allocation)"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="w-full flex justify-center">
                            <button type="submit" class="inline-flex items-center rounded cursor-pointer bg-[#273ef1] px-6 py-3 font-semibold text-white transition [box-shadow:rgb(171,_196,245)-8px_8px] hover:[box-shadow:rgb(171,_196,_245)0px_0px]">
                                <span class="mr-2">Submit</span>
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 15 16">
                                    <path fill="currentColor" d="M12.49 7.14L3.44 2.27c-.76-.41-1.64.3-1.4 1.13l1.24 4.34c.05.18.05.36 0 .54l-1.24 4.34c-.24.83.64 1.54 1.4 1.13l9.05-4.87a.98.98 0 0 0 0-1.72Z"/>
                                </svg>
                            </button>
                        </div>
                    </div> 
                </form>
            </div>
        </div>
        <Modal :show="modalState" @close="closeModal">
            <div class="bg-gray-100 h-auto">
                <div class="bg-white p-6  md:mx-auto">
                    <svg class="text-indigo-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2c-.791 0-1.55.314-2.11.874l-.893.893a.985.985 0 0 1-.696.288H7.04A2.984 2.984 0 0 0 4.055 7.04v1.262a.986.986 0 0 1-.288.696l-.893.893a2.984 2.984 0 0 0 0 4.22l.893.893a.985.985 0 0 1 .288.696v1.262a2.984 2.984 0 0 0 2.984 2.984h1.262c.261 0 .512.104.696.288l.893.893a2.984 2.984 0 0 0 4.22 0l.893-.893a.985.985 0 0 1 .696-.288h1.262a2.984 2.984 0 0 0 2.984-2.984V15.7c0-.261.104-.512.288-.696l.893-.893a2.984 2.984 0 0 0 0-4.22l-.893-.893a.985.985 0 0 1-.288-.696V7.04a2.984 2.984 0 0 0-2.984-2.984h-1.262a.985.985 0 0 1-.696-.288l-.893-.893A2.984 2.984 0 0 0 12 2Zm3.683 7.73a1 1 0 1 0-1.414-1.413l-4.253 4.253-1.277-1.277a1 1 0 0 0-1.415 1.414l1.985 1.984a1 1 0 0 0 1.414 0l4.96-4.96Z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Confirm Update!</h3>
                        <p class="text-gray-600 my-2">Please confirm if you wish to proceed.</p>
                        <p> This action can't be undone upon confirmation.</p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                            <SuccessButton @click="confirmSubmit" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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
        </Modal>
    </AuthenticatedLayout>
</template>
 