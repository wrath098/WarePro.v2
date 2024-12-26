<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    
    const props = defineProps({
        products: Object,
    });
</script>

<template>
    <Head title="Products" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Products</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Price List</a></li>
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-end">
                            <div class="flex mb:flex-col">
                                <PrintButton :href="route('generatePdf.PriceActiveList')" target="_blank">
                                    <span class="mr-2">Print List</span>
                                </PrintButton>
                            </div>
                        </div>
                        <div class="px-5">
                            <DataTable class="w-full text-left rtl:text-right text-gray-900 ">
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
                                        <th v-for="(price, index) in products[0]?.price" :key="index" class="px-6 py-3 w-1/12">
                                            <span>Price </span> <br>( {{ index+1 }} )
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in products" :key="product.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
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
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 