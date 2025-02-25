<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Layouts/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
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
</script>

<template>
    <Head title="General Fund" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" >Annual Budget</li>
                    <li class="after:content-[''] after:ml-2 text-[#86591e]" aria-current="page">Edit</li>  
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 rounded-md">
                        <div class="flex flex-wrap">
                            <p class="text-base text-gray-100">
                                <strong class="font-semibold">Anual Budget for {{ year }}</strong>
                                <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                <span class="text-sm">Please enter the required data in the input field.</span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <form @submit.prevent="submitForm">
                        <!-- Total Amount Field -->
                            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 flex flex-col my-2">
                                <h6 class="pb-2 text-gray-400">Budget</h6>
                                <div class="-mx-3 md:flex mb-6">
                                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-first-name">
                                            Total Amount
                                        </label>
                                        <input v-model="updateBudget.totalAmount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-red rounded py-3 px-4 mb-3" id="grid-first-name" type="number" placeholder="Total Amount" readonly>
                                    </div>
                                </div>

                                <!-- Allocations -->
                                <h6 class="py-2 text-gray-400">Allocation</h6>
                                <div v-for="account in updateBudget.funds" :key="account.id" class="-mx-3 md:flex mb-6 flex-col">
                                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-first-name">
                                            {{ account.accountClass }}
                                        </label>
                                        <input v-model="account.amount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-red rounded py-3 px-4 mb-3" id="grid-first-name" type="number" :disabled="account.allocations != null" placeholder="" required>
                                    </div>

                                    <!-- Account Allocations -->
                                    <div class="md:w-1/2 px-5 grid lg:grid-cols-2 lg:gap-6">
                                        <div v-for="allocation in account.allocations" :key="allocation.id">
                                            <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-city">
                                                {{ allocation.semester }} - {{ allocation.description }}
                                            </label>
                                            <input v-model="allocation.amount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4 disabled:bg-gray-300" id="grid-city" type="number" placeholder="" :disabled="allocation.description !== 'Contingency'" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="md:w-1/2 bg-blue-500 text-white rounded py-2 px-4 mt-4">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
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
    </div>
</template>
 