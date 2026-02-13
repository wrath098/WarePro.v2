<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import Modal from '@/Components/Modal.vue';
    import { computed, ref } from 'vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import Dropdown from '@/Components/Dropdown.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const props = defineProps({
        products: Object,
    });

    const isLoading = ref(false);
    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isPrintPriceOpen = computed(() => modalState.value === 'priceAdjustment');

    const priceForm = ref({
        adjustment: 100,
    });

    const openAdjustmentModal = () => {
        modalState.value = 'priceAdjustment';
    }

    const tableOptions = {
        ordering: false,
        paging: true,
        searching: true,
    };

</script>

<template>
    <Head title="Price List" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a :href="route('product.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Products
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('product.display.active.pricelist')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Price List
                            </a>
                        </div>
                    </li>
                </ol>
                <ol>
                    <li class="flex flex-col lg:flex-row">
                        <Dropdown class="w-32">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-medium leading-4 bg-rose-900 text-gray-50 transition duration-150 ease-in-out hover:bg-rose-800 focus:outline-none">
                                        Print Price
                                        <svg
                                            class="-me-0.5 ms-2 h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <template #content>
                                <button @click="openAdjustmentModal()" class="flex flex-row w-full px-4 py-2 text-start text-base leading-5 text-gray-900 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    <span class="mr-2">Adj. Price</span>
                                    <svg class="w-6 h-6" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
                                        <path fill="currentColor" d="M3.5 8H3V7h.5a.5.5 0 0 1 0 1M7 10V7h.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5z"/>
                                        <path fill="currentColor" fill-rule="evenodd" d="M1 1.5A1.5 1.5 0 0 1 2.5 0h8.207L14 3.293V13.5a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 1 13.5zM3.5 6H2v5h1V9h.5a1.5 1.5 0 1 0 0-3m4 0H6v5h1.5A1.5 1.5 0 0 0 9 9.5v-2A1.5 1.5 0 0 0 7.5 6m2.5 5V6h3v1h-2v1h1v1h-1v2z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                <a v-if="hasPermission('print-price-list') || hasAnyRole(['Developer'])" :href="route('generatePdf.PriceActiveList')" target="_blank" rel="noopener noreferrer" class="flex flex-row w-full px-4 py-2 text-start text-base leading-5 text-gray-900 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    <span class="mr-2">Timeline</span> 
                                    <svg class="w-6 h-6" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
                                        <path fill="currentColor" d="M3.5 8H3V7h.5a.5.5 0 0 1 0 1M7 10V7h.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5z"/>
                                        <path fill="currentColor" fill-rule="evenodd" d="M1 1.5A1.5 1.5 0 0 1 2.5 0h8.207L14 3.293V13.5a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 1 13.5zM3.5 6H2v5h1V9h.5a1.5 1.5 0 1 0 0-3m4 0H6v5h1.5A1.5 1.5 0 0 0 9 9.5v-2A1.5 1.5 0 0 0 7.5 6m2.5 5V6h3v1h-2v1h1v1h-1v2z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </template>
                        </Dropdown>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="relative overflow-x-auto">
                    <DataTable class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100" :options="tableOptions">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-3 w-1/12">
                                    Stock No.
                                </th>
                                <!-- <th scope="col" class="px-6 py-3 w-1/12">
                                    Old Stock No.
                                </th> -->
                                <th scope="col" class="px-6 py-3 w-1/12">
                                    Item Class
                                </th>
                                <th scope="col" class="px-6 py-3 w-4/12">
                                    Description
                                </th>
                                <th scope="col" class="px-6 py-3 w-1/12">
                                    Unit Of Measure
                                </th>
                                <th v-for="(price, index) in products[0]?.price" :key="index" class="px-6 py-3 w-1/12">
                                    <span v-if="index === 0">Latest</span>
                                    <span v-else-if="index === 1">Previous</span>
                                    <span v-else-if="index === 2">3rd Latest</span>
                                    <span v-else-if="index === 3">4th Latest</span>
                                    <span v-else>Oldest</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id">
                                <td scope="row" class="py-2">
                                    {{  product.newNo }}
                                </td>
                                <!-- <td scope="row" class="py-2">
                                    {{  product.oldNo }}
                                </td> -->
                                <td class="py-2">
                                    {{ product.itemName }}
                                </td>
                                <td class="py-2">
                                    {{ product.desc }}
                                </td>
                                <td class="py-2">
                                    {{ product.unit }}
                                </td>
                                <td v-for="price in product.price" :key="price" class="py-2">
                                    {{ price != 0 ? price : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal :show="isPrintPriceOpen" @close="closeModal"> 
        <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Print Price</h3>
                    <p class="text-sm text-zinc-700"> Set the price adjustment.</p>
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-[#1a0037]">Enter a value between 100% and 120% to adjust the price.</p>
                        <div class="relative z-0 w-full group my-2">
                            <input v-model="priceForm.adjustment" type="number" name="adjustment" id="adjustment" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                            <label for="adjustment" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Price Adjustment</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <a :href="route('generatePdf.priceAdjusted', { adjustment: priceForm.adjustment })" target="_blank" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Print 
            </a>

            <DangerButton @click="closeModal"> 
                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Cancel
            </DangerButton>
        </div>
    </Modal>
</template>
<style scoped>
:deep(table.dataTable) {
    border: 2px solid #7393dc;
}

:deep(table.dataTable thead > tr > th) {
    background-color: #d8d8f6;
    border: 2px solid #7393dc;
    text-align: center;
    color: #03244d;
}

:deep(table.dataTable tbody > tr > td) {
    border-right: 2px solid #7393dc;
    text-align: right;
}

:deep(div.dt-container select.dt-input) {
    border: 1px solid #03244d;
    margin-left: 1px;
    width: 75px;
}

:deep(div.dt-container .dt-search input) {
    background-color: #fafafa;
    border: 1px solid #03244d;
    margin-right: 1px;
    width: 250px;
}

:deep(div.dt-length > label) {
    display: none;
}

:deep([data-v-2055750e] table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: center !important;
}

:deep([data-v-2055750e] table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: center !important;
}

:deep([data-v-2055750e] table.dataTable tbody > tr > td:nth-child(5)) {
        text-align: center !important;
}

:deep([data-v-2055750e] table.dataTable tbody > tr > td:nth-child(3)) {
        text-align: left !important;
}

:deep([data-v-2055750e] table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: left !important;
}
</style>