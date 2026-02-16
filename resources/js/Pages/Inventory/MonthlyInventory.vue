<script setup>
import { reactive, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { DataTable } from 'datatables.net-vue3'
import axios from 'axios'
import Swal from 'sweetalert2'

const isLoading = ref(false)

const searchDate = reactive({
    date_from: '',
    date_to: '',
})

const getAsOfParams = () => {
    if (!searchDate.date_to) return null

    const asOfDate = new Date(searchDate.date_to)
    const year = asOfDate.getFullYear()

    return {
        date_from: `${year}-01-01`,
        date_to: searchDate.date_to,
    }
}

const productTransactions = ref([])

const fetchproductTransactions = async () => {
    isLoading.value = true

    if (searchDate.date_from && searchDate.date_to) {
        console.log('Fetching products with params:', {
            date_from: searchDate.date_from,
            date_to: searchDate.date_to,
        });
        try {
            const response = await axios.get('../api/monthly-product-inventory-report', {
                params: {
                    date_from: searchDate.date_from,
                    date_to: searchDate.date_to,
                },
            });


            productTransactions.value = response.data
            isLoading.value = false

            if (productTransactions.value.data.length == 0) {
                Swal.fire({
                    title: 'Warning',
                    text: 'No products found.',
                    icon: 'warning',
                    showConfirmButton: false,
                    timer: 1500,
                })
            }
        } catch (error) {
            isLoading.value = false
            console.error('Error fetching product data:', error)
        }
    }
}

const columns = [
    {
        data: 'newStockNo',
        title: 'New Stock No#',
        width: '10%',
    },
    // {
    //     data: 'oldStockNo',
    //     title: 'Old Code',
    //     width: '10%',
    // },
    {
        data: 'description',
        title: 'Description',
    },
    {
        data: 'unit',
        title: 'Unit of Measurement',
        width: '10%',
    },
    {
        data: 'currentInventory',
        title: 'Quantity',
        width: '10%',
    },
];
</script>

<template>

    <Head title="Product Inventory" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-4 h-4 w-4">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Product Inventory
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('show.stockCard')"
                                class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Monthly Inventory Report
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="w-full px-4 lg:px-0 my-4">
            <div class="w-full bg-bg-zinc-300 h-auto mb-2 rounded-md shadow-md">
                <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                    <h3 class="font-bold text-lg leading-6 text-zinc-300">
                        Product Inventory
                    </h3>
                    <p class="text-sm text-zinc-300">
                        Displays the current inventory of products based on the selected date.
                    </p>
                </div>

                <form @submit.prevent="fetchproductTransactions">
                    <div class="grid gap-2 grid-cols-1 lg:grid-cols-4 lg:gap-6 my-5 mx-3">
                        <div class="relative z-0 my-5 group">
                            <input v-model="searchDate.date_from" type="date" name="date_from" id="date_from"
                                class="block py-2.5 px-0 w-full text-medium text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer"
                                placeholder=" " required />
                            <label for="date_from"
                                class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                Date From
                            </label>
                        </div>

                        <div class="relative z-0 my-5 group">
                            <input v-model="searchDate.date_to" type="date" name="date_to" id="date_to"
                                class="block py-2.5 px-0 w-full text-medium text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer"
                                placeholder=" " required />
                            <label for="date_to"
                                class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">
                                Date To (As of)
                            </label>
                        </div>

                        <div class="lg:col-span-2 relative z-0 w-full my-5 lg:flex justify-around items-center group">
                            <button type="submit"
                                class="flex w-auto justify-center items-center mx-1 min-w-[150px] px-6 py-3 my-1 lg:my-0 text-white transition-all bg-gray-600 rounded-md sm:w-auto hover:bg-gray-900 hover:text-white hover:-tranneutral-y-px">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" width="200" height="200"
                                    viewBox="0 0 16 16">
                                    <path fill="currentColor" d="M1 2h14v2L9 9v7l-2-2V9L1 4V2zm0-2h14v1H1V0z" />
                                </svg>
                                Filter
                            </button>
                            <a :href="productTransactions.data && productTransactions.data.length > 0 ? route('generatePdf.MonthlyInventoryReport', {
                                date_from: searchDate.date_from,
                                date_to: searchDate.date_to
                            }) : '#'" target="_blank"
                                class="flex w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white hover:-tranneutral-y-px"
                                :class="{ 'opacity-25 cursor-not-allowed': !(productTransactions.data && productTransactions.data.length > 0) }"
                                @click="!(productTransactions.data && productTransactions.data.length > 0) && $event.preventDefault()">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" width="200" height="200"
                                    viewBox="0 0 17 16">
                                    <g fill="currentColor" fill-rule="evenodd">
                                        <path d="M6 12v3.98h5.993V12H6zM5 1h7.948v2.96H5z" />
                                        <path
                                            d="M1.041 5.016v9h3.91V11.01H13v3.006h4.041v-9h-16zm2.975 2.013H2.969V5.953h1.047v1.076zm2-.06H4.969v-1h1.047v1z" />
                                    </g>
                                </svg>
                                Print (Duration)
                            </a>

                            <a :href="productTransactions.data && productTransactions.data.length > 0 && getAsOfParams()
                                ? route('generatePdf.MonthlyInventoryReport', getAsOfParams())
                                : '#'" target="_blank" class="flex w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white hover:-tranneutral-y-px"
                                :class="{ 'opacity-25 cursor-not-allowed': !(productTransactions.data && productTransactions.data.length > 0 && searchDate.date_to) }"
                                @click="!(productTransactions.data && productTransactions.data.length > 0 && searchDate.date_to) && $event.preventDefault()">
                                Print (As of)
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-zinc-300 shadow-md sm:rounded-lg p-4">
                <div class="relative overflow-x-auto md:overflow-hidden">
                    <div v-if="isLoading"
                        class="absolute bg-white text-indigo-800 bg-opacity-60 z-10 h-full w-full flex items-center justify-center">
                        <div class="flex items-center">
                            <span class="text-3xl mr-4">Loading</span>
                            <svg class="animate-spin h-8 w-8 text-indigo-800" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <DataTable class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="columns" :data="productTransactions.data" :options="{
                            paging: true,
                            searching: true,
                            ordering: true
                        }" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
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
    text-align: center;
}

:deep(div.dt-container select.dt-input) {
    background-color: #fafafa;
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

:deep(table.dataTable tbody > tr > td:nth-child(3)) {
    text-align: left !important;
}

:deep(table.dataTable tbody > tr > td:nth-child(5)) {
    text-align: right !important;
}
</style>