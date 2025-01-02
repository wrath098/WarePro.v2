<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';

    const props = defineProps({
        transactions: Object
    });
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Procurement Basis</a></li>
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
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">RIS No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-3/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit of Measure</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Requested (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Requestee</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued To</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(transaction, index) in transactions" :key="transaction.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ transaction.ris_no }}</td>
                                                <td class="px-6 py-3">{{ transaction.prod_id }}</td>
                                                <td class="px-6 py-3">{{ transaction.prod_id }}</td>
                                                <td class="px-6 py-3">{{ transaction.unit }}</td>
                                                <td class="px-6 py-3">{{ transaction.qty }}</td>
                                                <td class="px-6 py-3">{{ transaction.office_id }}</td>
                                                <td class="px-6 py-3">{{ transaction.issued_to }}</td>
                                                <td class="px-6 py-3">{{ transaction.created_by }}</td>
                                            </tr>
                                        </tbody>
                                    </DataTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
