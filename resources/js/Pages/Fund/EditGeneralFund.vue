<script setup>
import { Head } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/Buttons/DangerButton.vue';
import SuccessButton from '@/Components/Buttons/SuccessButton.vue';

const props = defineProps({
    budgetDetails: Object,
    year: String,
})
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
                        <form action="">
                            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 flex flex-col my-2">
                                <h6 class="pb-2 text-gray-400">Budget</h6>
                                <div class="-mx-3 md:flex mb-6">
                                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-first-name">
                                            Total Amount
                                        </label>
                                        <input :value="budgetDetails.totalAmount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-red rounded py-3 px-4 mb-3" id="grid-first-name" type="number" placeholder="Total Amount">
                                    </div>
                                </div>
                                
                                <h6 class="py-2 text-gray-400">Allocation</h6>
                                <div v-for="account in budgetDetails.funds" :key="account.id" class="-mx-3 md:flex mb-6 flex-col">
                                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-first-name">
                                            {{ account.accountClass }}
                                        </label>
                                        <input :value="account.amount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-red rounded py-3 px-4 mb-3" id="grid-first-name" type="number" placeholder="">
                                    </div>
                                    <div class="md:w-1/2 px-5 grid lg:grid-cols-2 lg:gap-6">
                                        <div v-for="allocation in account.allocations" :key="allocation.id">
                                            <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="grid-city">
                                                {{ allocation.semester }} - {{ allocation.description }}
                                            </label>
                                            <input :value="allocation.amount" class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4 disabled:bg-gray-300" id="grid-city" type="number" placeholder="" :disabled="allocation.description !== 'Contingency'">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 