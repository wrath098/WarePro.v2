<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    
    const props = defineProps({
        products: Object,
    });

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
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
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
                        <PrintButton :href="route('generatePdf.PriceActiveList')" class="mx-1 my-1 lg:my-0" target="_blank">
                            <span class="mr-2">Print List</span>
                        </PrintButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl bg-white shadow rounded-md mb-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="relative overflow-x-auto">
                    <DataTable class="display table-hover table-striped shadow-lg rounded-lg" :options="tableOptions">
                        <thead>
                            <tr>
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
                            <tr v-for="product in products" :key="product.id">
                                <td scope="row" class="py-2">
                                    {{  product.newNo }}
                                </td>
                                <td scope="row" class="py-2">
                                    {{  product.oldNo }}
                                </td>
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