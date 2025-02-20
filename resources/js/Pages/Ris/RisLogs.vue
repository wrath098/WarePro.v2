<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import { computed, onMounted } from 'vue';
    import Swal from 'sweetalert2';

    const page = usePage();

    const props = defineProps({
        transactions: Object,
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
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Procurement Basis</a></li>
                </ol>
            </nav>
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
                                                <th scope="col" class="px-6 py-3 w-1/12">Office (Requestee)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit of Measure</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Requested (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued To</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued By</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Date Released</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Attachment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(transaction, index) in transactions" :key="transaction.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ transaction.risNo }}</td>
                                                <td class="px-6 py-3">{{ transaction.officeRequestee }}</td>
                                                <td class="px-6 py-3">{{ transaction.stockNo }}</td>
                                                <td class="px-6 py-3">{{ transaction.prodDesc }}</td>
                                                <td class="px-6 py-3">{{ transaction.unit }}</td>
                                                <td class="px-6 py-3">{{ transaction.qty }}</td>
                                                <td class="px-6 py-3">{{ transaction.issuedTo }}</td>
                                                <td class="px-6 py-3">{{ transaction.releasedBy }}</td>
                                                <td class="px-6 py-3">{{ transaction.dateReleased }}</td>
                                                <td v-if="transaction.attachment" class="px-6 py-3">
                                                    <ViewButton :href="route('ris.show.attachment', {transactionId : transaction.id})" tooltip="View" target="_blank"/>
                                                </td>
                                                <td v-else class="italic text-gray-400">No Attachment</td>
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
