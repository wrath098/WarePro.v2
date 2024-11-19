<script setup>
    import { Head } from '@inertiajs/vue3';
    import { Inertia } from '@inertiajs/inertia';
    import { ref, computed } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';   
    import Sidebar from '@/Components/Sidebar.vue';

    const props = defineProps({
        toPr: {
            type: Object,
            default: () => ({}),
        },
    });

    const particularList = computed(() => {
        return props.toPr ? Object.values(props.toPr) : [];
    });

    const requestItems = ref([]);
    const selectedItems = ref([]);

    const handleCheckboxChange = (prodCode, isChecked) => {
        if (isChecked) {
            const particular = Object.values(props.toPr).find(item => item.prodCode === prodCode);
            const exist = requestItems.value.find(item => item.prodCode === prodCode);
            if (particular && exist == null) {
                requestItems.value.push(particular);
            }
        } else {
            const index = requestItems.value.findIndex(item => item.prodCode === prodCode);
            if (index !== -1) {
                requestItems.value.splice(index, 1);
            }
        }
    };

    const isAllSelected = computed(() => {
        return particularList.value.length > 0 && 
                particularList.value.every(item => 
                selectedItems.value.some(selectedItem => selectedItem.prodCode === item.prodCode)
                );
    });

    const toggleSelectAll = (isChecked) => {
        if (isChecked) {
            selectedItems.value = [...particularList.value];
            selectedItems.value.forEach(particular => {
                handleCheckboxChange(particular.prodCode, true)
            });
        } else {
            selectedItems.value = [];
            requestItems.value = [];
        }
    };

    const nextStep = () => {
        Inertia.post('step-3', requestItems.value);
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
                                                                            <th scope="col" class="px-6 py-3 w-1/12">
                                                                                <div class="px-6 py-3">
                                                                                    <input 
                                                                                        type="checkbox" 
                                                                                        :checked="isAllSelected"
                                                                                        @change="toggleSelectAll($event.target.checked)"
                                                                                    />
                                                                                </div>
                                                                            </th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                                            <th scope="col" class="px-6 py-3 w-6/12 text-center">Description</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Unit Of Measure</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Quantity</th>
                                                                            <th scope="col" class="px-6 py-3 w-1/12">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr v-for="particular in particularList" :key="particular.prodCode">
                                                                            <td class="px-6 py-3">
                                                                                <input type="checkbox" 
                                                                                v-model="selectedItems" 
                                                                                :value="particular.prodCode"
                                                                                :checked="selectedItems.some(item => item.prodCode === particular.prodCode)"
                                                                                @change="handleCheckboxChange(particular.prodCode, $event.target.checked)"
                                                                                />
                                                                            </td>
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