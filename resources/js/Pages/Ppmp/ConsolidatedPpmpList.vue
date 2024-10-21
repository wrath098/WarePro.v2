<script setup>
    import { Head } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';

    const props = defineProps({
        transactions: Object,
        years: Object,
    });

    const consolidate = reactive({
        ppmpYear: '',
        basePrice: 100,
        qtyAdjust: 100,
        ppmpType: 'individual',
        ppmpStatus: 'draft',
    });

    const submitConsolidate = () => {
        Inertia.post('create-consolidated', consolidate, {
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.log(errors);
            },
        });
    };
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Project Procurement and Manangement Plan</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submitConsolidate" class="space-y-5">
                                <h4>Consolidate PPMP</h4>
                                <div>
                                    <label for="ppmpType" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Choose Year
                                    </label>
                                    <select v-model="consolidate.ppmpYear" @change="onCategoryChange(consolidate)" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option value="" disabled>Select Year</option>
                                        <option v-for="year in props.years" :key="year.ppmp_year" :value="year.ppmp_year">
                                            {{ year.ppmp_year }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label for="basedPrice" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Price Adjustment:
                                        <span class="text-sm text-[#8f9091]">Value: 100% - 120%</span>
                                    </label>
                                    <input v-model="consolidate.basePrice" type="number" id="basePrice" placeholder="Ex. 1=101% | 2=102%, etc...."
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md" min="100" max="120"/>
                                </div>

                                <div>
                                    <label for="basedPrice" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Quantity Adjustment:
                                        <span class="text-sm text-[#8f9091]">Value: 50%-100%</span>
                                    </label>
                                    <input v-model="consolidate.qtyAdjust"  type="number" id="qtyAdjust" placeholder="Value: 50%-100%"
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md" min="50" max="100"/>
                                </div>

                                <div>
                                    <button
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Consolidate
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <div class="p-6 text-gray-900 text-center">
                                        <DataTable class="w-full text-gray-900 display">
                                            <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                                <tr class="text-center">
                                                    <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Transaction No.</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">PPMP Year</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Version</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Price Adjustment</th>
                                                    <th scope="col" class="px-6 py-3 w-1/12">Quantity Adjustment</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Created/Updated By</th>
                                                    <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(transaction, index) in transactions" :key="transaction.code">
                                                    <td class="px-6 py-3">{{ index + 1 }}</td>
                                                    <td class="px-6 py-3">{{ transaction.code }}</td>
                                                    <td class="px-6 py-3">{{ transaction.ppmpYear }}</td>
                                                    <td class="px-6 py-3">v.{{ transaction.version }}</td>
                                                    <td class="px-6 py-3">{{ transaction.priceAdjust }}</td>
                                                    <td class="px-6 py-3">{{ transaction.qtyAdjust }}</td>
                                                    <td class="px-6 py-3">{{ transaction.updatedBy }}</td>
                                                    <td class="px-6 py-3">
                                                        <ViewButton :href="route('conso.ppmp.show', { ppmpTransaction: transaction.id })" tooltip="View"/>
                                                        <RemoveButton @click="''" tooltip="Trash"/>
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
        </div>
    </AuthenticatedLayout>
    </div>
</template>
