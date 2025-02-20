<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { computed, onMounted } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
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
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Inspection and Acceptance</a></li>
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">All Transactions</li> 
                </ol>
            </nav>
        </template>
        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 lg:overflow-hidden shadow-sm sm:rounded-lg">
                    <DataTable class="w-full text-gray-900 display">
                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                            <tr>
                                <th class="px-6 py-3 w-1/12">No.</th>
                                <th class="px-6 py-3 w-1/12">IAR No.</th>
                                <th class="px-6 py-3 w-1/12">PO No.</th>
                                <th class="px-6 py-3 w-3/12">Supplier</th>
                                <th class="px-6 py-3 w-1/12">IAR Date</th>
                                <th class="px-6 py-3 w-1/12">Status</th>
                                <th class="px-6 py-3 w-1/12">Updated By</th>
                                <th class="px-6 py-3 w-1/12">Date Updated</th>
                                <th class="px-6 py-3 w-1/12">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(transaction, index) in props.transactions" :key="transaction.air_id">
                                <td>{{ ++index }}</td>
                                <td>{{ transaction.airId }}</td>
                                <td>{{ transaction.poId }}</td>
                                <td>{{ transaction.supplier }}</td>
                                <td>{{ transaction.date }}</td>
                                <td>
                                    <span :class="{
                                        'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-300': transaction.status === 'Pending',
                                        'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': transaction.status === 'Completed',
                                        }">
                                        {{ transaction.status }}
                                    </span>
                                </td>
                                <td>{{ transaction.updater }}</td>
                                <td>{{ transaction.dateUpdated }}</td>
                                <td v-if="transaction.status === 'Pending'">
                                    <ViewButton :href="route('iar.particular', { iar: transaction.id})" tooltip="View"/>
                                </td>
                                <td v-if="transaction.status === 'Completed'">
                                    <ViewButton :href="route('iar.particular.completed', { iar: transaction.id})" tooltip="View"/>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>