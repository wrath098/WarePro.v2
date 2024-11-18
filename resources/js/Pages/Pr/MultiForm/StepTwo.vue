<script setup>
    import { Head } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Sidebar from '@/Components/Sidebar.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';

    const props = defineProps({
        toPr: Object,
    });

    const nextStep = () => {
        Inertia.get('step-3', selectItems.value);
    };

    const goBack = () => {
        Inertia.get('step-1', form.value);
    };
</script>

<template>
    <Head title="PR" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Purchase Request</li> 
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
                    <div class="mx-2 w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0 flex flex-col justify-center align-middle">
                        <h3 class="my-2 text-[#07074D] text-center">
                            Step 2: Purchase Request Particulars
                        </h3>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="relative overflow-x-auto md:overflow-hidden">
                                <form @submit.prevent="nextStep" class="space-y-5 mx-2">
                                    <div class="w-full mx-auto sm:px-6 lg:px-2">
                                        <div class="overflow-hidden shadow-sm sm:rounded-lg">
                                            <div class="flex flex-col md:flex-row items-start justify-center">
                                                <div class="mx-2 w-full bg-white rounded-md shadow">
                                                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                                        <div class="relative overflow-x-auto md:overflow-hidden">
                                                            <div class="p-6 text-gray-900 text-center">
                                                                <DataTable class="w-full text-gray-900 display">
                                                                    <thead class="text-sm text-gray-100 uppercase bg-indigo-600" >
                                                                        <tr class="text-center">
                                                                            <th scope="col" class="px-6 py-3 w-1/12"></th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                                            <th scope="col" class="px-6 py-3 w-6/12 text-center">Description</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Unit Of Measure</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Quantity</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr v-for="particular in Object.values(toPr)" :key="particular">
                                                                            <td class="px-6 py-3"></td>
                                                                            <td class="px-6 py-3" >{{ particular.prodCode }}</td>
                                                                            <td class="px-6 py-3 text-left">{{ particular.prodName }}</td>
                                                                            <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                                            <td class="px-6 py-3">{{ particular.prodPrice }}</td>
                                                                            <td class="px-6 py-3">{{ particular.qty }}</td>
                                                                            <td class="px-6 py-3">{{ particular.amount }}</td>
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
                                    <div>
                                        <button
                                            class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                            Next
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
  </template>