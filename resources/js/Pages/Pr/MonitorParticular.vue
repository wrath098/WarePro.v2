<script setup>
    import { Head, usePage} from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted } from 'vue';
    
    const page = usePage();

    const props = defineProps({
        ppmp: Object,
        transaction: Object,
        particulars: Object,
    });

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success!',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed!',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Procurement Basis</a></li>
                    <li><a class="after:content-[''] after:ml-2 text-[#86591e]">Transaction No. : {{ ppmp.ppmpCode }}</a></li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="w-full md:w-3/12 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="flex-1 flex items-start justify-between bg-indigo-300 p-2 rounded-t-xl md:mb-5">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-lg justify-center font-semibold text-[#161555] mb-4">Purchase Request Information</h2>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">PPMP Transaction No.</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.ppmpCode }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Calendar Year</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.ppmpYear}}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Cumulative Total Amount</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.totalAmount }}</dd>
                                        </div>
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">PR Count : {{ ppmp.prCount }}</dt>
                                            <dd class="text-gray-700 sm:col-span-2">
                                                <div v-for="pr in transaction" :key="pr.id">
                                                    <p class="text-gray-800">{{ pr.pr_no }}</p>
                                                </div>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-indigo-300 p-2 rounded-t-xl">
                                <h2 class="text-lg font-semibold text-[#171858] mb-4">Particulars</h2>
                            </div>
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
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
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
 