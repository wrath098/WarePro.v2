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
    generalFund: Object,
});

const formatDecimal = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

const modalState = ref(null);
const showModal = (modalType) => { modalState.value = modalType; }
const closeModal = () => { modalState.value = null; }
const isEditPPModalOpen = computed(() => modalState.value === 'edit');

const editParticular = reactive({
    budgetId: '',
    firstSemContingency: '',
    secondSemContingency: '',
    user: props.user,
});

const openEditPpmpModal = (particular) => {
    editParticular.partId = particular.pId;
    editParticular.prodCode = particular.prodCode;
    editParticular.prodDesc = particular.prodName;
    editParticular.firstQty = numberWithoutCommas(particular.qtyFirst);
    editParticular.secondQty = numberWithoutCommas(particular.qtySecond);
    modalState.value = 'edit';
}

</script>

<template>
    <Head title="General Fund" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">Annual Budget</li> 
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
                    <div class="p-4">
                        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4 mb-4">
                            <li v-for="(yearData, year) in generalFund" :key="year" class="">
                                <div class="flex-1 flex items-start justify-between rounded-lg bg-indigo-600 p-2">
                                    <div class="flex flex-col gap-1">
                                        <h2 class="text-lg font-semibold text-gray-50">Annual Budget for CY {{ year }}</h2>
                                    </div>
                                    <div class="flex items-center">
                                        <a :href="route('general.fund.editFundAllocation', { budgetDetails: yearData, year: year})" class="flex items-center rounded-full transition">
                                            <span class="sr-only">Open options</span>
                                            <svg class="w-8 h-8 text-indigo-100 hover:text-yellow-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                                                <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">General Fund</dt>
                                            <dd class="text-gray-700 sm:col-span-2 text-right">{{ formatDecimal(yearData.totalAmount) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <h6 class="py-3 px-2 text-gray-400">Allocation</h6>
                                <div v-for="account in yearData.funds" :key="account.id" class="flow-root border border-gray-100 py-3 shadow-sm mb-3">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900 sm:col-span-2">{{ account.accountClass }} </dt>
                                            <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(account.amount) }}</dd>
                                        </div>
                                        <div v-for="allocation in account.allocations" :key="allocation.id" class="grid grid-cols-1 gap-1 pl-10 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900 sm:col-span-2">{{ allocation.semester }} - {{ allocation.description }}</dt>
                                            <dd class="text-gray-700 sm:col-span-1 text-right">{{ formatDecimal(allocation.amount) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 