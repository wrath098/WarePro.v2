<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const props = defineProps({
        transaction: Object,
        particulars: Object,
    });

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Failed',
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
            <nav aria-label="breadcrumb" class="font-semibold text-lg md:leading-6"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Inspection and Acceptance</a></li>
                    <li class="after:content-[':'] after:ml-2 text-[#86591e]" aria-current="page">Transactions</li>
                    <li class="after:content-[''] after:ml-2 text-[#86591e]" aria-current="page">IAR No. {{ transaction.sdiIarId }} - P.O. No. {{ transaction.poNo }}</li>
                </ol>
            </nav>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="flex-1 flex items-start justify-between rounded-lg bg-indigo-600 p-2">
                                <div class="flex flex-col gap-1">
                                    <h2 class="text-lg font-semibold text-gray-50">Inspection and Acceptance Report Information</h2>
                                </div>
                            </div>
                            <div class="p-2">
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">IAR No.</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.sdiIarId }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">PO No.</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.poNo }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Supplier </dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.supplier }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">IAR Date</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.iarDate }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Status</dt>
                                            <dd class="text-gray-700 sm:col-span-2">
                                                <span :class="{
                                                    'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': transaction.status === 'Completed',
                                                    }">
                                                    {{ transaction.status }}
                                                </span>
                                            </dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Number of Particulars</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.particularCount }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Updated By</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.updater }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Date Updated</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.dateUpdated }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Remarks</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ transaction.remark }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Item No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-4/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit of Measure</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Quantity</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit Price</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Total Amount</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Expiry Date</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in particulars" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.itemNo }}</td>
                                                <td class="px-6 py-3">{{ particular.stockNo }}</td>
                                                <td class="px-6 py-3">{{ particular.specs }}</td>
                                                <td class="px-6 py-3">{{ particular.unit }}</td>
                                                <td class="px-6 py-3">{{ particular.quantity }}</td>
                                                <td class="px-6 py-3">{{ particular.price }}</td>
                                                <td class="px-6 py-3">{{ particular.quantity * particular.price }}</td>
                                                <td class="px-6 py-3">{{ particular.expiry }}</td>
                                                <td class="px-6 py-3">
                                                    <span :class="{
                                                    'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': particular.status === 'Completed',
                                                    'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-300': particular.status === 'Failed',
                                                        }">
                                                        {{ particular.status }}
                                                    </span>
                                                </td>
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