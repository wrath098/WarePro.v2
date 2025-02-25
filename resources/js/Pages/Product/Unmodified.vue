<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, reactive, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Layouts/Sidebar.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        products: Object,
        list: Object,
        authUserId: Number,
    });

    const stockNo = ref('');
    const stockData = ref(null);

    const fetchData = () => {
        if (stockNo.value.length > 0) {
            const foundStock = props.list.find(item => item.code === stockNo.value);
            if (foundStock) {
            stockData.value = {
                ...foundStock,
            };
            } else {
            stockData.value = null;
            }
        } else {
            stockData.value = null;
        }
    };

    const create = reactive({
        prod: stockData,
        ppmpYear: '',
    });

    const edit = reactive({
        prodId: '',
    });

    const openDropUnmodifiedModal = (product) => {
        edit.prodId = product.id;
        modalState.value = 'drop';
    };

    const years = generateYears();
    const modalState = ref(null);

    const isAddUnmodifiedModalOpen = computed(() => modalState.value === 'add');
    const isDropUnmodifiedModalOpen = computed(() => modalState.value === 'drop');
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => { 
                closeModal();
                isLoading.value = false;
            },
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
                isLoading.value = false;
            },
        });
    };

    const submitAddUnmodified = () => submitForm('store-unmodified-product', create);
    const submitDropUnmodify = () => submitForm('deactivate-unmodified-product', edit);

    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

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
    <Head title="Products" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Products</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Unchanged (Qty)</a></li>
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')">
                            <span class="mr-2">New</span>
                        </AddButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="px-5">
                            <DataTable class="w-full text-left rtl:text-right text-gray-900 ">
                                <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 w-1/12">
                                            No#
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-1/12">
                                            New Stock No.
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-1/12">
                                            Old Stock No.
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-5/12">
                                            Description
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-1/12">
                                            Unit Of Measure
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-1/12">
                                            PPMP Year
                                        </th>
                                        <th scope="col" class="px-6 py-3 w-2/12">
                                            Action/s
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(product, index) in products" :key="product.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                        <td scope="row" class="py-2 text-center text-sm">
                                            {{  index+1 }}
                                        </td>
                                        <td scope="row" class="py-2 text-center text-sm">
                                            {{  product.code }}
                                        </td>
                                        <td class="py-2 text-center text-sm">
                                            {{ product.oldCode }}
                                        </td>
                                        <td class="py-2">
                                            {{ product.desc }}
                                        </td>
                                        <td class="py-2 text-center">
                                            {{ product.unit }}
                                        </td>
                                        <td class="py-2 text-center">
                                            {{ product.year }}
                                        </td>
                                        <td class="py-2 text-center">
                                            <RemoveButton @click="openDropUnmodifiedModal(product)" tooltip="Trash"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isAddUnmodifiedModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAddUnmodified">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Add New Product to be Unmodified</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500"> Enter the details of the Product you wish to add on unmodified.</p>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-2">
                                        <input v-model="stockNo" @input="fetchData" type="text" name="stockNo" id="stockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                        <label for="stockNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock Number</label>
                                    </div>
                                    <div v-if="stockData">
                                        <div>
                                            <div class="relative z-0 w-full my-5 group">
                                                <textarea type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " disabled :value="stockData.desc"></textarea>
                                                <label for="prodDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Product Description</label>
                                            </div>
                                            <div class="relative z-0 lg:w-1/2 my-5 group">
                                                <input type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " disabled :value="stockData.unit"/>
                                                <label for="prodDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                            </div>                               
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]"> PPMP Calendar Year</p>
                                    <select v-model="create.ppmpYear" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option disabled value="">Select the Ppmp Year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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
            </form>
        </Modal>
        <Modal :show="isDropUnmodifiedModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDropUnmodify">
                <input type="hidden" v-model="edit.prodId">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Product into the list. This action can't be undone.</p>
                            <p> Please confirm if you wish to proceed.  </p>
                            <div class="px-4 py-6 sm:px-6 flex justify-center flex-col sm:flex-row-reverse">
                                <SuccessButton :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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
    </AuthenticatedLayout>
    </div>
</template>
 