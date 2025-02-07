<script setup>
    import { computed, onMounted, reactive, ref, watch } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import { DataTable } from 'datatables.net-vue3';
    import axios from 'axios';
    import { debounce } from 'lodash';

    const props = defineProps({
        availableProducts: Object,
    });

    const searchProductInfo = reactive({
        prodId: '',
        product: '',
        startDate: '',
        endDate: '',
    });

    const productList = ref([]);
    const productTransactions = ref([]);

    const fetchProduct = async () => {
        if (searchProductInfo.product) {
            try {
                const response = await axios.get('../api/search-product-item', {
                    params: { query: searchProductInfo.product },
                });
                productList.value = response.data;
                return productList;
            } catch (error) {
                console.error('Error fetching product data:', error);
            }
        } else {
            productList.value = [];
            return productList;
        }
    };

    const debouncedFetchProduct = debounce(fetchProduct, 300);

    const selectProduct = (product) => {
        searchProductInfo.prodId = product.prodId;
        searchProductInfo.product = product.prodDesc;
        productList.value = [];
    };

    const fetchproductTransactions = async () => {
        if (searchProductInfo.product && searchProductInfo.startDate && searchProductInfo.endDate) {
            try {
                const response = await axios.get('../api/product-inventory-log', { 
                    params: { query: searchProductInfo},
                });
                productTransactions.value = response.data;
                return productTransactions;
            } catch (error) {
                console.error('Error fetching product data:', error);
            }
        }
    };

    const columns = [
        {
            data: 'id',
            title: 'Transaction No.',
            width: '10%',
        },
        {
            data: 'created',
            title: 'Date of Issuance/Acceptance',
        },
        {
            data: 'unit',
            title: 'Unit of Measure',
        },
        {
            data: 'type',
            title: 'Kind',
            width: '20%',
        },
        {
            data: 'qty',
            title: 'Quantity',
        },
        {
            data: 'adjustedTotalStock',
            title: 'Current Stock',
        },
    ];
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-[''] after:ml-2 text-green-700">Product Inventory</a></li>
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
                <div class="w-full bg-white h-auto mb-2 rounded-md">
                    <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 rounded-md">
                        <div class="flex flex-wrap">
                            <p class="text-base text-gray-100">
                                <strong class="font-semibold">Product Information and Date of Duration</strong>
                                <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                <span class="text-sm">Please enter the required data in the input field.</span>
                            </p>
                        </div>
                    </div>
                    <form @submit.prevent="fetchproductTransactions">
                        <div class="grid lg:grid-cols-4 lg:gap-6 my-5 mx-3">
                            <div class="relative z-0 w-full group my-5">
                                <input v-model="searchProductInfo.product" @input="debouncedFetchProduct" type="text" name="fetchProduct" id="fetchProduct" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="fetchProduct" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Product</label>
                            </div>
                            <ul v-if="productList.data" class="absolute w-3/4 h-60 bg-white border border-gray-300 mt-16 z-10 overflow-y-scroll">
                                <li
                                    v-for="product in productList.data"
                                    :key="product.prodId"
                                    @click="selectProduct(product)"
                                    class="px-4 py-2 cursor-pointer hover:bg-gray-200"
                                >
                                {{ product.prodStockNo }} - {{ product.prodDesc }}
                                </li>
                            </ul>

                            <div class="relative z-0 w-full my-5 group">
                                <input v-model="searchProductInfo.startDate" type="date" name="from" id="from" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="from" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">From (Start Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group">
                                <input v-model="searchProductInfo.endDate" type="date" name="to" id="to" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="to" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">To (End Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group text-center">
                                <button type="submit" class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                    Filter
                                </button>
                                <a v-if="productTransactions.data && productTransactions.data.length > 0" :href="route('generatePdf.StockCard', { productDetails: searchProductInfo })" class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                    Print
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable
                        class="display table-hover table-striped shadow-lg rounded-lg"
                        :columns="columns"
                        :data="productTransactions.data"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
<style scoped>
    table.dataTable th {
    background-color: #4c51bf;
    color: white;
    }
</style>