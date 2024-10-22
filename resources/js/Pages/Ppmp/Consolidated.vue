<script setup>
    import { Head, router, usePage } from '@inertiajs/vue3';
    import { reactive, ref, watch, computed } from 'vue';
    import { debounce } from 'lodash';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Pagination from '@/Components/Pagination.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
import PrintButton from '@/Components/Buttons/PrintButton.vue';
import Dropdown from '@/Components/Dropdown.vue';

    const props = defineProps({
        ppmp: Object,
        user: Number,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isConfirmModalOpen = computed(() => modalState.value === 'add');
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
                        <div class="mx-2 w-full md:w-3/12 bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                            <div class="flex-1 flex items-start justify-between rounded-lg">
                                <div class="flex flex-col gap-1 ">
                                    <h2 class="text-lg font-semibold text-[#07074D] mb-4">PPMP Information</h2>
                                </div>
                                <div class="flex items-center">
                                    <Dropdown>
                                        <template #trigger>
                                            <button class="flex items-center rounded-full transition">
                                                <span class="sr-only">Open options</span>
                                                <svg class="w-7 h-7 text-indigo-500 hover:text-indigo-900" fill="currentColor" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                                                    <path d="m23.365,3.699l-1.322,1.322-3.064-3.064,1.234-1.234c.801-.801,2.108-.955,2.985-.237,1.009.825,1.064,2.316.166,3.214Zm-5.8-.328l-5.296,5.296c-.813.813-1.269,1.915-1.269,3.064v.769c0,.276.224.5.5.5h.769c1.149,0,2.251-.457,3.064-1.269l5.296-5.296-3.064-3.064Zm3.707,10.514l-.451-.26c.102-.544.153-1.088.153-1.625s-.051-1.081-.153-1.625l-.29-1.015-3.784,3.784c-1.196,1.196-2.786,1.855-4.478,1.855h-.77c-1.379,0-2.5-1.121-2.5-2.5v-.77c0-1.691.659-3.281,1.855-4.478l4.119-4.119v-.134c0-1.654-1.346-3-3-3s-3,1.346-3,3v.522c-1.047.37-2.016.929-2.857,1.649l-.45-.259c-.693-.398-1.501-.504-2.277-.295-.773.208-1.419.706-1.818,1.4-.4.694-.505,1.503-.296,2.277.208.773.706,1.419,1.401,1.819l.451.259c-.102.544-.153,1.088-.153,1.626s.051,1.082.153,1.626l-.451.259c-.695.4-1.192,1.046-1.401,1.819-.209.774-.104,1.583.295,2.276.399.695,1.045,1.193,1.819,1.401.776.21,1.584.104,2.277-.295l.45-.259c.841.721,1.81,1.279,2.857,1.649v.522c0,1.654,1.346,3,3,3s3-1.346,3-3v-.522c1.047-.37,2.016-.929,2.857-1.649l.45.259c.695.399,1.503.505,2.277.295.773-.208,1.419-.706,1.819-1.401.825-1.434.329-3.271-1.105-4.096Z"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <a :href="route('generatePdf.ConsolidatedPpmp', { ppmp: ppmp.id})" target="_blank" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                <svg class="w-6 h-6" aria-hidden="true"  xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                                                </svg>
                                                <span class="ml-2">Print List</span>
                                            </a>
                                            <button @click="showModal('add')" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                                                    <path d="m12,7V.46c.913.346,1.753.879,2.465,1.59l3.484,3.486c.712.711,1.245,1.551,1.591,2.464h-6.54c-.552,0-1-.449-1-1Zm-3.416,12h-3.584c-.552,0-1-.448-1-1s.448-1,1-1h3.07c-.041-.328-.07-.66-.07-1s.022-.672.063-1h-3.063c-.552,0-1-.448-1-1s.448-1,1-1h3.593c.296-.728.699-1.398,1.185-2h-4.778c-.552,0-1-.448-1-1s.448-1,1-1h5.774c-.479-.531-.774-1.23-.774-2V.024c-.161-.011-.322-.024-.485-.024h-4.515C2.243,0,0,2.243,0,5v14c0,2.757,2.243,5,5,5h10c.114,0,.221-.026.333-.034-3.066-.254-5.641-2.234-6.749-4.966Zm12.327.497c.939-1.319,1.365-3.028.96-4.843-.494-2.211-2.277-3.996-4.49-4.481-4.365-.956-8.163,2.843-7.208,7.208.485,2.213,2.27,3.996,4.481,4.49,1.816.406,3.525-.021,4.843-.96l2.796,2.796c.39.39,1.024.39,1.414,0h0c.39-.39.39-1.024,0-1.414l-2.796-2.796Zm-4.135-1.033l-.004.004c-.744.744-2.058.746-2.823-.019l-1.515-1.575c-.372-.387-.372-.999,0-1.386h0c.393-.409,1.047-.409,1.44,0l1.495,1.553,2.9-2.971c.392-.402,1.038-.402,1.43,0h0c.38.388.38,1.009,0,1.397l-2.925,2.997Z"/>
                                                </svg>
                                                <span class="ml-2">Confirm as Final</span>   
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="officeName" class="block text-sm font-medium text-[#07074D] mb-1">Version:</label>
                                <p class="text-lg text-gray-800 font-semibold">V.{{ ppmp.ppmp_version }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpCode" class="block text-sm font-medium text-[#07074D] mb-1">No.:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_code }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpType" class="block text-sm font-medium text-[#07074D] mb-1">Type:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_type }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="priceAdjustment" class="block text-sm font-medium text-[#07074D] mb-1">Adjusted Price:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.price_adjustment }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="priceAdjustment" class="block text-sm font-medium text-[#07074D] mb-1">Adjusted Quantity:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.qty_adjustment }}</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="ppmpRemarks" class="block text-sm font-medium text-[#07074D] mb-1">PPMP for CY:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.ppmp_year }}</p>
                            </div>       
                            
                            <div class="mb-4">
                                <label for="totalItems" class="block text-sm font-medium text-[#07074D] mb-1">Total Items Listed:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.totalItems }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="totalAmount" class="block text-sm font-medium text-[#07074D] mb-1">Total Amount:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.totalAmount }}</p>
                            </div>

                            <div class="mb-4">
                                <label for="totalAmount" class="block text-sm font-medium text-[#07074D] mb-1">Created/Updated By:</label>
                                <p class="text-lg text-gray-800 font-semibold">{{ ppmp.updater.name }}</p>
                            </div>
                        </div>

                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-5/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Jan (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">May (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in ppmp.transactions" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3">{{ particular.prodName }}</td>
                                                <td class="px-6 py-3">{{ particular.qtyFirst }}</td>
                                                <td class="px-6 py-3">{{ particular.qtySecond }}</td>
                                                <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                <td class="px-6 py-3">{{ particular.prodPrice }}</td>
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
    <Modal :show="isConfirmModalOpen" @close="closeModal"> 
        <form @submit.prevent="submitDrop">
            <div class="bg-gray-100 h-auto">
                <div class="bg-white p-6  md:mx-auto">
                    <svg class="text-indigo-700 w-16 h-16 mx-auto my-6" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24" width="512" height="512" fill="currentColor">
                        <path fill-rule="evenodd" d="M18.5,3h-5.53c-.08,0-.16-.02-.22-.05l-3.16-1.58c-.48-.24-1.02-.37-1.56-.37h-2.53C2.47,1,0,3.47,0,6.5v11c0,3.03,2.47,5.5,5.5,5.5h13c3.03,0,5.5-2.47,5.5-5.5V8.5c0-3.03-2.47-5.5-5.5-5.5Zm2.5,14.5c0,1.38-1.12,2.5-2.5,2.5H5.5c-1.38,0-2.5-1.12-2.5-2.5V8H20.95c.03,.16,.05,.33,.05,.5v9Zm-3.13-3.71c.39,.39,.39,1.02,0,1.41l-3.16,3.16c-.63,.63-1.71,.18-1.71-.71v-1.66H7.5c-.83,0-1.5-.67-1.5-1.5s.67-1.5,1.5-1.5h5.5v-1.66c0-.89,1.08-1.34,1.71-.71l3.16,3.16Z"/>
                    </svg>
                    <div class="text-center">
                        <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Confirm as Approved!</h3>
                        <p class="text-gray-600 my-2">Confirming this action will remark the selected PPMP as Final/Approved. This action can't be undone.</p>
                        <p> Please confirm if you wish to proceed.  </p>
                        <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                            <SuccessButton>
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                Confirm 
                            </SuccessButton>

                            <DangerButton @click="closeModal"> 
                                <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                Cancel
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </Modal>
    </div>
</template>
 