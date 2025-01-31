<script setup>
    import { computed, reactive, ref } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import { DataTable } from 'datatables.net-vue3';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import { Inertia } from '@inertiajs/inertia';
    import axios from 'axios';

    const props = defineProps({
        productList: Object,
    });

    const searchProductInfo = reactive({
        product: '',
        startDate: '',
        endDate: '',
    });

    const isLoading = ref(false);
    const productTransactions = ref([]);

    const fetchproductTransactions = async () => {
        isLoading.value = true;

        if (searchProductInfo.product && searchProductInfo.startDate && searchProductInfo.endDate) {
            try {
                const response = await axios.get('../api/product-inventory-log', { params: searchProductInfo });
                productTransactions.value = response.data;

                return productTransactions;
            } catch (error) {
                console.error('Error fetching product data:', error);
            }
        }

        isLoading.value = false;
    };
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
            {{ productTransactions }}
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="w-full bg-white h-32 mb-2 rounded-md">
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
                            <div class="relative z-0 w-full group">
                                <select v-model="searchProductInfo.product" name="product" id="product" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                    <option value="" disabled selected class="pl-5">Select Product</option>
                                    <option v-for="product in productList" :key="product.id" class="ml-5" :value="product">{{ product.prodStockNo  }} - {{ product.prodDesc  }}</option>
                                </select>
                                <label for="product" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Select Product</label>
                            </div>

                            <div class="relative z-0 w-full mb-5 group">
                                <input v-model="searchProductInfo.startDate" type="date" name="from" id="from" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="from" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">From (Start Date)</label>
                            </div>

                            <div class="relative z-0 w-full mb-5 group">
                                <input v-model="searchProductInfo.endDate" type="date" name="to" id="to" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="to" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">To (End Date)</label>
                            </div>

                            <div class="relative z-0 w-full mb-5 group text-center">
                                <button type="submit" class="inline-block w-auto text-center min-w-[200px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px" :disabled="isLoading">
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="w-full text-gray-900 display">
                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                            <tr>
                                <th class="w-1/12">Action</th>
                                <th class="w-1/12">Stock No.</th>
                                <th class="w-4/12">Description</th>
                                <th class="w-1/12">Unit of Measure</th>
                                <th class="w-1/12">Beginning Balance</th>
                                <th class="w-1/12">Stock Available</th>
                                <th class="w-1/12">Purchases</th>
                                <th class="w-1/12">Issuances</th>
                                <th class="w-1/12">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>