<script setup>
    import { Head, router} from '@inertiajs/vue3';
    import { ref, watch } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { debounce } from 'lodash';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    
    const props = defineProps({
        products: Object,
        filters: Object,
    });

    let search = ref(props.filters.search);

    watch(search, debounce(function (value) {
        router.get('product-price-list', { search: value }, {
            preserveState: true,
            preserveScroll:true,
            replace:true
        });
    }, 500));
</script>

<template>
    <Head title="Products" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a :href="route('product.display.active')" class="after:content-['/'] after:ml-2 text-gray-600 hover:text-green-700">Products</a></li>
                    <li class="text-green-700" aria-current="page">Price List</li> 
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                            <div class="flex mb:flex-col">
                                <PrintButton :href="route('generatePdf.PriceActiveList')" target="_blank">
                                    <span class="mr-2">Print</span>
                                </PrintButton>
                            </div>
                            
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-indigo-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="search" type="text" id="search" class="block p-2 ps-10 text-sm text-gray-900 border border-indigo-600 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 " placeholder="Search for Item Names">
                            </div>
                        </div>
                        <table class="w-full text-left rtl:text-right text-gray-900 ">
                            <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        No#
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        New Stock No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Old Stock No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Item Class
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-3/12">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Unit Of Measure
                                    </th>
                                    <th v-for="(price, index) in products.data[0]?.price" :key="index" class="px-6 py-3 w-1/12">
                                        <span>Price </span>( {{ index+1 }} )
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(product, index) in products.data" :key="product.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  index+1 }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  product.newNo }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  product.oldNo }}
                                    </td>
                                    <td class="py-2">
                                        {{ product.itemName }}
                                    </td>
                                    <td class="py-2">
                                        {{ product.desc }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ product.unit }}
                                    </td>
                                    <td v-for="price in product.price" :key="price" class="py-2 text-right">
                                        {{ price != 0 ? price : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-center p-5">
                        <Pagination :links="products.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 