<script setup>
    import { Head} from '@inertiajs/vue3';
    import { reactive, ref, watch, computed } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';

    const props = defineProps({
        transaction: Object,
        particulars: Object,
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
                    <li><a class="after:content-[''] after:ml-2 text-[#86591e]">Transaction No.</a></li>
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
                        <div class="mx-2 w-full md:w-3/12 bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="flex-1 flex items-start justify-between rounded-lg">
                                <div class="flex flex-col gap-1 ">
                                    <h2 class="text-lg font-semibold text-[#07074D] mb-4">Purchase Request List</h2>
                                </div>
                            </div>
                            <div v-for="pr in transaction" :key="pr.id" class="mb-4">
                                <p class="text-lg text-gray-800 font-semibold">{{ pr.pr_no }}</p>
                            </div>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-3/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Jan (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">May (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">PR (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Available (Qty)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in particulars" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3">{{ particular.prodName }}</td>
                                                <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                <td class="px-6 py-3">{{ particular.firstQty }}</td>
                                                <td class="px-6 py-3">{{ particular.secondQty }}</td>
                                                <td class="px-6 py-3">{{ particular.onPrQty }}</td>
                                                <td class="px-6 py-3">{{ particular.availableQty }}</td>
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
 