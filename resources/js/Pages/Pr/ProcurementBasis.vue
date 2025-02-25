<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Swal from 'sweetalert2';
    import { computed, onMounted } from 'vue';
    
    const page = usePage();
    const props = defineProps({
        ppmpList: Object
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
                                                <th scope="col" class="px-6 py-3 w-1/12">Transaction No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">PPMP Year</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Version</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Quantity Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Purchase Request</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Date Created/Updated</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Created/Updated By</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(transaction, index) in ppmpList" :key="transaction.code">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ transaction.code }}</td>
                                                <td class="px-6 py-3">{{ transaction.ppmpYear }}</td>
                                                <td class="px-6 py-3">v.{{ transaction.version }}</td>
                                                <td class="px-6 py-3">{{ transaction.priceAdjust }}%</td>
                                                <td class="px-6 py-3">{{ transaction.qtyAdjust }}%</td>
                                                <td class="px-6 py-3">
                                                    <span :class="{
                                                        'bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-gray-300': transaction.pr <= 0,
                                                        'bg-indigo-100 text-indigo-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-indigo-300': transaction.pr > 0,
                                                        }">
                                                        {{ transaction.pr }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-3">{{ transaction.createdAt }}</td>
                                                <td class="px-6 py-3">{{ transaction.updatedBy }}</td>
                                                <td v-if="transaction.pr" class="px-6 py-3">
                                                    <ViewButton :href="route('pr.display.availableToPurchase', { ppmpTransaction: transaction.id })" tooltip="View"/>
                                                </td>
                                                <td v-else class="px-6 py-3"></td>
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