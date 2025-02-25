<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted } from 'vue';
    
    const page = usePage();

    const props = defineProps({
        toPr: Object,
        pendingPr: Object
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
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li class="after:content-['/'] after:ml-2 text-[#86591e]" aria-current="page">Transactions</li> 
                    <li class="text-[#86591e]" aria-current="page">Pending Approval</li> 
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
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Pr No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">PPMP No.</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Supplier</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Created At</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Created / Update By</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Status</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(transaction, index) in pendingPr" :key="transaction.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ transaction.pr_no }}</td>
                                                <td class="px-6 py-3">{{ transaction.ppmp_controller.ppmp_code }}</td>
                                                <td class="px-6 py-3">{{ transaction.semester }} - {{ transaction.qty_adjustment }}%</td>
                                                <td class="px-6 py-3">{{ transaction.pr_desc }}</td>
                                                <td class="px-6 py-3">{{ transaction.formatted_created_at }}</td>
                                                <td class="px-6 py-3">{{ transaction.updater.name }}</td>
                                                <td class="px-6 py-3">
                                                    <span :class="{
                                                        'bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-yellow-300': transaction.pr_status === 'Draft',
                                                        }">
                                                        {{ transaction.pr_status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-3">
                                                    <ViewButton :href="route('pr.show.particular', { prTransaction: transaction.id})" tooltip="View"></ViewButton>
                                                    <Print :href="route('generatePdf.PurchaseRequestDraft', { pr: transaction.id})" tooltip="Print"></Print>
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
<style scoped>
    .badge-pending {
    background-color: yellow;
    color: black;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-approved {
    background-color: green;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }

    .badge-rejected {
    background-color: red;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    }
</style>