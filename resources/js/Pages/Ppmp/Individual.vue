<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { reactive, ref, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import AddIcon from '@/Components/Buttons/AddIcon.vue';
    import PrintIcon from '@/Components/Buttons/PrintIcon.vue';
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const isLoading = ref(false);

    const props = defineProps({
        ppmp: Object,
        ppmpParticulars: Object,
        products: Object,
        user: Number,
    });

    const stockNo = ref('');
    const stockData = ref(null);

    const fetchData = () => {
        if (stockNo.value.length > 0) {
            const foundStock = props.products.find(item => item.code === stockNo.value);
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

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isAddPPModalOpen = computed(() => modalState.value === 'add');
    const isEditPPModalOpen = computed(() => modalState.value === 'edit');
    const isDropPPModalOpen = computed(() => modalState.value === 'drop');
    
    const addParticular  = reactive({
        transId: props.ppmp.id,
        prodCode: stockNo,
        firstQty: '',
        secondQty: '',
        user: props.user,
    });

    const editParticular = reactive({
        partId: '',
        prodCode: '',
        prodDesc: '',
        firstQty: '',
        secondQty: '',
        user: props.user,
    });

    const dropParticular = reactive({
        pId: '',
        user: props.user,
    });

    const openEditPpmpModal = (particular) => {
        editParticular.partId = particular.id;
        editParticular.prodCode = particular.prodCode;
        editParticular.prodDesc = particular.prodName;
        editParticular.firstQty = particular.firstQty;
        editParticular.secondQty = particular.secondQty;
        modalState.value = 'edit';
    }

    const openDropPpmpModal = (particular) => {
        dropParticular.pId = particular.id;
        modalState.value = 'drop';
    }

    const submitForm = (action, url, data) => {
        let method;

        switch (action) {
            case 'post':
                method = 'post';
                break;
            case 'put':
                method = 'put';
                break;
            case 'delete':
                method = 'delete';
                break;
            default:
                throw new Error('Invalid action specified');
        }

        isLoading.value = true;
        Inertia[method](url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
        });
    };

    const submitAdd = () => submitForm('post', 'create', addParticular);
    const submitEdit = () => submitForm('put', 'edit', editParticular);
    const submitDrop = () => {
        const ppmpParticular = dropParticular.pId;
        submitForm('delete', `delete/${ppmpParticular}`, null)
    };

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
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center">
                        <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Project Procurement Management Plan
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('import.ppmp.index')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                {{ ppmp.requestee.office_name }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-4 lg:gap-2">
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                                <div class="bg-indigo-600 text-white p-4 flex justify-between">
                                    <div class="font-bold text-lg">PPMP Information</div>
                                    <div class="flex items-center">
                                        <div class="rounded-full mx-1">
                                            <a :href="route('generatePdf.IndividualPpmp', { ppmp: ppmp.id})" target="_blank" title="Print">
                                                <svg class="w-6 h-6 text-gray-50 transition duration-75 hover:text-emerald-100" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 432 384">
                                                    <path fill="currentColor" d="M363 107q26 0 45 18.5t19 45.5v128h-86v85H85v-85H0V171q0-27 18.5-45.5T64 107h299zm-64 234V235H128v106h171zm63.5-149q8.5 0 15-6.5t6.5-15t-6.5-15t-15-6.5t-15 6.5t-6.5 15t6.5 15t15 6.5zM341 0v85H85V0h256z"/>
                                                </svg>
                                            </a>
                                            <!-- <PrintIcon :href="route('generatePdf.IndividualPpmp', { ppmp: ppmp.id})" target="_blank" class="bg-gray-50" ></PrintIcon> -->
                                        </div>
                                        <div class="rounded-full mx-1">
                                            <button @click="showModal('add')" title="Add New Item">
                                                <svg class="w-6 h-6 text-gray-50 transition duration-75 hover:text-emerald-100" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                                    <g id="galaAdd0" fill="none" stroke="currentColor" stroke-dasharray="none" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="4" stroke-opacity="1" stroke-width="16">
                                                        <circle id="galaAdd1" cx="128" cy="128" r="112"/>
                                                        <path id="galaAdd2" d="M 79.999992,128 H 176.0001"/>
                                                        <path id="galaAdd3" d="m 128.00004,79.99995 v 96.0001"/>
                                                    </g>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="flex flex-col gap-1">
                                    <h2 class="text-lg justify-center font-semibold text-[#ededee] mb-4">PPMP Information</h2>
                                </div> -->


                            <div class="p-2">
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Requestee</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.requestee.office_name }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Transaction No.</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.ppmp_code }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Type</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.ppmp_type }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Price Adjustment</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.price_adjustment * 100}}%</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Calendar Year</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.ppmp_year }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Total Items Listed</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.totalItems }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Total Amount</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.formattedOverallPrice }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-3 mx-2 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-3/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Jan (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">May (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Price</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(particular, index) in ppmpParticulars" :key="particular.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                                <td class="px-6 py-3">{{ ++index }}</td>
                                                <td class="px-6 py-3">{{ particular.prodCode }}</td>
                                                <td class="px-6 py-3">{{ particular.prodName }}</td>
                                                <td class="px-6 py-3">{{ particular.firstQty }}</td>
                                                <td class="px-6 py-3">{{ particular.secondQty }}</td>
                                                <td class="px-6 py-3">{{ particular.prodUnit }}</td>
                                                <td class="px-6 py-3">{{ particular.prodPrice }}</td>
                                                <td class="px-6 py-3 text-center">
                                                    <EditButton @click="openEditPpmpModal(particular)" tooltip="Edit"/>
                                                    <RemoveButton @click="openDropPpmpModal(particular)" tooltip="Remove"/>
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
        <Modal :show="isAddPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAdd">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Additional Particular</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the add Product/Particular you wish to add.</p>
                            <div class="mt-3">
                                <p class="text-sm text-[#86591e]"> Product Information: </p>
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
                                <div v-else-if="stockNo">
                                    <p class="text-red-500 text-xs italic mt-2">No product found!</p>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="addParticular.firstQty" type="number" name="firstQty" id="firstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="firstQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">1st Semester(Qty)</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="addParticular.secondQty" type="number" name="secondQty" id="secondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=""/>
                                    <label for="secondQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">2nd Semester(Qty)</label>
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
        <Modal :show="isEditPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Update Quantity</h3>
                            <p class="text-sm text-gray-500"> Enter the quantity you want to update on the input field.</p>
                            <div class="mt-3">
                                <p class="text-sm text-[#86591e]"> Product Information: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.prodCode" type="text" name="editStockNo" id="editStockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required readonly/>
                                    <label for="editStockNo" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock No</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <textarea v-model="editParticular.prodDesc" type="text" name="editProdDesc" id="editProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" readonly></textarea>
                                    <label for="editProdDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                </div>
                            </div>
                            <div class="mt-5">
                                <p class="text-sm text-[#86591e]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.firstQty" type="number" name="editFirstQty" id="editFirstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editFirstQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">1st Semester (Qty)</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.secondQty" type="number" name="editSecondQty" id="editSecondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editSecondQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">2nd Semester (Qty)</label>
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
        <Modal :show="isDropPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDrop">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Move to Trash!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Product from the list. This action can't be undone.</p>
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
</template>
 