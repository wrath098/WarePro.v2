<script setup>
    import { Head, router } from '@inertiajs/vue3';
    import { reactive, ref, watch, computed } from 'vue';
    import { debounce } from 'lodash';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Pagination from '@/Components/Pagination.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';

    const props = defineProps({
        years: Object,
    });

    const consolidate = reactive({
        ppmpYear: '',
        priceAdjust: '',
        qtyAdjust: '',
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
                                        Year:
                                    </label>
                                    <select v-model="consolidate.ppmpYear" id="ppmpYear" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option value="" selected>Please choose the desired year</option>
                                        <option v-for="year in years" :key="year.ppmp_year" :value="year.ppmp_year">{{ year.ppmp_year }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="basedPrice" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Price Adjustment:
                                        <span class="text-sm text-[#8f9091]">Default value is 100%</span>
                                    </label>
                                    <input v-model="consolidate.priceAdjust" type="number" id="priceAdjust" placeholder="Ex. 1=101% | 2=102%, etc...."
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md" />
                                </div>

                                <div>
                                    <label for="basedPrice" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Quantity Adjustment:
                                        <span class="text-sm text-[#8f9091]">Default value is 0%</span>
                                    </label>
                                    <input v-model="consolidate.qtyAdjust"  type="number" id="qtyAdjust" placeholder="Value: 1%-100%"
                                        class="w-full rounded-md border border-[#e0e0e0] bg-white py-3 px-4 text-base font-medium text-[#2c2d30] outline-none focus:border-[#6A64F1] focus:shadow-md" />
                                </div>


                                <!-- <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        PPMP for CY:
                                    </label>
                                    <select v-model="create.ppmpYear" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" selected>Please choose year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div> -->

                                <!-- <div>
                                    <label for="ppmpYear" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Office
                                    </label>
                                    <select v-model="create.office" id="ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" selected>Please choose the office</option>
                                        <option v-for="office in props.offices" :key="office.id" :value="office.id">{{ office.office_name }}</option>
                                    </select>
                                </div> -->

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
                                    <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-end pb-4">
                                        <label for="search" class="sr-only">Search</label>
                                        <!-- <div class="relative">
                                            <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                                                <svg class="w-5 h-5 text-indigo-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                            </div>
                                            <input v-model="search" type="text" id="search" class="block p-2 ps-10 text-sm text-gray-900 border border-indigo-600 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 " placeholder="Search for Item Names">
                                        </div> -->
                                    </div>
                                    <table class="w-full text-left rtl:text-right text-gray-900">
                                        <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/6">
                                                    No#
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/6">
                                                    Office Code
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/6">
                                                    PPMP No.
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/6">
                                                    PPMP Type
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-1/6">
                                                    Based Price
                                                </th>
                                                <th scope="col" class="px-6 py-3 w-2/6">
                                                    Action/s
                                                </th>
                                            </tr>
                                        </thead>
                                        <!-- <tbody>
                                            <tr v-for="(ppmp, index) in officePpmps.data" :key="ppmp.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td scope="row" class="py-2 text-center text-sm">
                                                    {{  index+1 }}
                                                </td>
                                                <td class="py-2">
                                                    {{ ppmp.officeCode }}
                                                </td>
                                                <td scope="row" class="py-2 text-center text-sm">
                                                    {{  ppmp.ppmpCode }}
                                                </td>
                                                <td class="py-2 text-center">
                                                    {{ ppmp.ppmpType }}
                                                </td>
                                                <td class="py-2 text-center">
                                                    {{ ppmp.basedPrice }}
                                                </td>
                                                <td class="py-2 text-center">
                                                    <ViewButton :href="route('indiv.ppmp.show', { ppmpTransaction: ppmp.id })" tooltip="View"/>
                                                    <RemoveButton @click="openDropPpmpModal(ppmp)" tooltip="Remove"/>
                                                </td>
                                            </tr>
                                        </tbody> -->
                                    </table>
                                </div>
                                <!-- <div class="flex justify-center p-5">
                                    <Pagination :links="officePpmps.links" />
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
