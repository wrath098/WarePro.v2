<script setup>
    import { Head, router } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';

    const props = defineProps({
        toPr: Object
    });

    const filteredYear = ref([]);
    const generatePr = reactive({
        selectedType: '',
        selectedYear: '',
        semester: '',
        qtyAdjust: '',
    });

    const onTypeChange = (context) => {
        const type = props.toPr.find(typ => typ.ppmp_type === context.selectedType);
        filteredYear.value = type ? type.years : [];
        generatePr.selectedYear = '';
    };

    const submitForm = (url, data) => {
        router.post(url, data, {
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };
    const submitPr = () => submitForm('pr/create-pr', generatePr);
</script>

<template>
    <Head title="PPMP" />
    <div>
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
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submitPr" class="space-y-5">
                                <div>
                                    <label for="ppmpType" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Create Purchase Request:
                                    </label>
                                    <select v-model="generatePr.selectedType" @change="onTypeChange(generatePr)" id="ppmpType" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option value="" selected>Please choose PPMP Type</option>
                                        <option v-for="type in props.toPr" :key="type.ppmp_type" :value="type.ppmp_type">
                                            {{ type.ppmp_type }}
                                        </option>
                                    </select>

                                    <select v-model="generatePr.selectedYear" v-if="filteredYear.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Please choose the PPMP Year</option>
                                        <option v-for="year in filteredYear" :key="year.ppmp_year" :value="year.ppmp_year">
                                            {{ year.ppmp_year }}
                                        </option>
                                    </select>  
                                </div>

                                <div v-if="generatePr.selectedType == 'consolidated'" class="mt-5" >
                                    <select v-model="generatePr.semester" class="mb-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Select Semester</option>
                                        <option value="qty_first">1st</option>
                                        <option value="qty_second">2nd</option>
                                    </select> 

                                    <p class="text-sm text-gray-500"> Quantity Adjustment: <span class="text-sm text-[#8f9091]">Value: 50% - 100%</span></p>
                                    <input 
                                        v-model="generatePr.qtyAdjust"
                                        type="number"
                                        id="qtyAdjust"
                                        min="50"
                                        max="100"
                                        placeholder="Enter from 50 to 100"
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-2 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md"
                                    />
                                </div>

                                <div>
                                    <button
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Create PR
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/6">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Office Code</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP Type</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Price Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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