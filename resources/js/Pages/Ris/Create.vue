<script setup>
    import { ref, watch } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import { DataTable } from 'datatables.net-vue3';
import AddButton from '@/Components/Buttons/AddButton.vue';

    const props = defineProps({
        products: Object
    });

    const risNo = ref('');
    const stockData = ref(null);
    const requestData = ref([]);

    const fetchData = () => {
        if (risNo.value.length > 0) {
            const foundStock = props.products.find(item => item.prod_newNo === risNo.value);
            if (foundStock) {
            stockData.value = {
                ...foundStock,
                qty: foundStock.quantity || 0
            };
            } else {
            stockData.value = null;
            }
        } else {
            stockData.value = null;
        }
    };

    watch(() => stockData.value?.qty, (newQty) => {});

    const addToRequestData = () => {
        if (stockData.value && stockData.value.qty > 0 ) {
            requestData.value.push({
                ...stockData.value,
                qty: stockData.value.qty
            });
            stockData.value = null;
            console.log("Updated requestData:", requestData.value);
        } else {
            alert("Please enter a valid quantity.");
        }
    };

    const removeItem = (index) => {
        requestData.value.splice(index, 1);
        console.log("Updated requestData:", requestData.value);
    };

    const columns = [
        
        { title: 'Stock No.', data: 'prod_newNo' },
        { title: 'Description', data: 'prod_desc' },
        { title: 'Unit', data: 'prod_unit' },
        { title: 'Requested Quantity', data: 'qty' },
    ];
</script>

<template>
    <Head title="Dashboard" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-gray-400">Requisition and Issuance</a></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <section class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="py-2">
                        <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 ">
                            <div class="flex flex-wrap">
                                <p class="text-base text-gray-100">
                                    <strong class="font-semibold">Requested Product Information</strong>
                                    <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                    Please enter the required data in the input field.
                                </p>
                            </div>
                        </div>
                        <div class="px-4">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                    <span class="text-gray-600 text-base font-semibold">Stock No. : </span>
                                </div>
                                <input 
                                    type="text" 
                                    id="risNo" 
                                    v-model="risNo"
                                    @input="fetchData"
                                    class="mt-2 pl-20 p-2.5 border border-gray-300 rounded-md w-1/3 focus:outline-none focus:ring focus:border-indigo-500" 
                                    placeholder="00-00-000" 
                                    required
                                >
                            </div>

                            <div v-if="stockData" class="mt-2 px-4">
                                <h3 class="text-base text-gray-700 font-semibold">Product Details:</h3>
                                <div class="px-5 mb-2">
                                    <p class="text-base text-gray-500">
                                        <strong class="font-semibold">Stock No:</strong> 
                                        {{ stockData.prod_newNo }}
                                    </p>
                                    <p class="text-base text-gray-500">
                                        <strong class="font-semibold">Description:</strong> 
                                        {{ stockData.prod_desc }}
                                    </p>
                                    <p class="text-base text-gray-500">
                                        <strong class="font-semibold">Unit of Measure:</strong> 
                                        {{ stockData.prod_unit }}
                                    </p>
                            

                                    <label for="quantity"><strong class="text-base text-gray-500 font-semibold">Quantity: </strong></label>
                                    <input 
                                        type="number" 
                                        id="quantity" 
                                        v-model="stockData.qty" 
                                        min="0"
                                        class="border-0 border-b-2 text-center"
                                    />
                                </div>
                                <AddButton @click="addToRequestData">Add Product</AddButton>
                            </div>

                            <div v-else-if="risNo.length > 0">
                                <p>No matching stock found!</p>
                            </div>

                        </div>

                            
                        <div v-if="requestData.length > 0" class="mt-4">
                            <DataTable :data="requestData" :columns="columns">
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
