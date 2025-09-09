<script setup>
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import { reactive, ref, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        ppmp: Object,
        ppmpParticulars: Object,
        products: Object,
        totalItems: String,
        formattedOverallPrice: String,
        createdAt: String,
        user: Number,
    });

    const ppmpParticularsArray = Object.values(props.ppmpParticulars);

    const stockNo = ref('');
    const stockData = ref(null);
    const createForm = useForm({});

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
        transType: props.ppmp.ppmp_type,
        prodCode: stockNo,
        firstQty: '',
        secondQty: '',
        user: props.user,
    });

    const editParticular = useForm({
        partId: '',
        prodCode: '',
        prodDesc: '',
        firstQty: '',
        secondQty: '',
        user: props.user,
        transType: props.ppmp.ppmp_type,
    });

    const dropParticular = useForm({
        partId: '',
        user: props.user,
        transType: props.ppmp.ppmp_type,
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
        dropParticular.partId = particular.id;
        modalState.value = 'drop';
    }

    const submitForm = (method, url, formData) => {
        isLoading.value = true;

        formData[method](url, {
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    });
                } else {
                    formData.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        router.visit(route('indiv.ppmp.show', { ppmpTransaction: props.ppmp.id}), {
                            preserveScroll: true,
                            preserveState: false,
                        });
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.error('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submitAdd = () => submitForm('post', route('indiv.particular.store', { param : addParticular }), createForm);
    const submitEdit = () => submitForm('put', route('indiv.particular.update'), editParticular);
    const submitDrop = () => submitForm('delete', route('indiv.particular.delete'), dropParticular);

    const columns = [
        {
            data: 'prodCode',
            title: 'Stock No#',
            width: '10%'
        },
        {
            data: 'prodName',
            title: 'Description',
            width: '30%'
        },
        {
            data: 'firstQty',
            title: 'Jan (Qty)',
            width: '10%',
        },
        {
            data: 'secondQty',
            title: 'May (Qty)',
            width: '10%'
        },
        {
            data: 'prodUnit',
            title: 'Unit',
            width: '10%'
        },
        {
            data: 'prodPrice',
            title: 'Price',
            width: '10%'
        },
        {
            data: null,
            title: 'Action',
            width: '20%',
            render: '#action',
        },
    ];
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
                                {{ ppmp.ppmp_code }}
                            </a>
                        </div>
                    </li>
                </ol>
                <ol class="flex flex-col lg:flex-row">
                    <li>
                        <AddButton v-if="hasPermission('create-office-ppmp-particular') ||  hasAnyRole(['Developer'])" @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Add New Particular</span>
                        </AddButton>
                    </li>
                    <li v-if="ppmp.ppmp_type == 'individual'">
                        <PrintButton v-if="hasPermission('print-office-ppmp') ||  hasAnyRole(['Developer'])" :href="route('generatePdf.DraftedOfficePpmp', { ppmp: ppmp.id, version: 'raw' })" target="_blank" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Print</span>
                        </PrintButton>
                    </li>
                    <li v-else>
                        <PrintButton v-if="hasPermission('print-office-ppmp') ||  hasAnyRole(['Developer'])" :href="route('generatePdf.emergencyPpmp', { ppmp: ppmp.id})" target="_blank" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">Print</span>
                        </PrintButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="shadow sm:rounded-lg bg-zinc-300 mb-5">
                        <div class="bg-zinc-400 px-4 py-5 sm:px-6 rounded-t-lg">
                            <h3 class="font-bold text-lg leading-6 text-zinc-900">
                                PPMP Information
                            </h3>
                            <p class="text-sm text-zinc-700">
                                ID# [{{ ppmp.ppmp_code }}]
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2 lg:gap-2">
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Office
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.requestee.office_name }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Type
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.ppmp_type == 'individual' ? 'Office' : ppmp.ppmp_type }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        PPMP for CY
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.ppmp_year }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Date Created
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ createdAt }}
                                    </dd>
                                </div>
                            </dl>
                            <dl class="font-semibold text-base">
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Price Adjustment
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ ppmp.price_adjustment * 100}}%
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Number Of Item Listed
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        {{ totalItems }}
                                    </dd>
                                </div>
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-gray-600">
                                        Total Amount
                                    </dt>
                                    <dd class="mt-1 text-gray-900 sm:mt-0 sm:col-span-2">
                                        Php. {{ formattedOverallPrice }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="col-span-3 p-2 bg-zinc-300 rounded-md shadow mt-5 lg:mt-0">
                        <div class="p-2 overflow-hidden">
                            <div class="relative overflow-x-auto">
                                <DataTable
                                    class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                                    :columns="columns"
                                    :data="ppmpParticularsArray"
                                    :options="{  paging: true,
                                        searching: true,
                                        ordering: false,
                                    }">
                                        <template #action="props">
                                            <EditButton v-if="hasPermission('edit-office-ppmp-particular') ||  hasAnyRole(['Developer'])" @click="openEditPpmpModal(props.cellData)" tooltip="Edit"/>
                                            <RemoveButton v-if="hasPermission('delete-office-ppmp-particular') ||  hasAnyRole(['Developer'])" @click="openDropPpmpModal(props.cellData)" tooltip="Remove"/>
                                        </template>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isAddPPModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitAdd">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Additional Particular</h3>
                            <p class="text-sm text-zinc-700"> Enter the details for the add Product/Particular you wish to add.</p>
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Information: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="stockNo" @input="fetchData" type="text" name="stockNo" id="stockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " required />
                                    <label for="stockNo" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock Number</label>
                                </div>
                                <div v-if="stockData">
                                    <div>
                                        <div class="relative z-0 w-full my-5 group">
                                            <textarea type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " disabled :value="stockData.desc"></textarea>
                                            <label for="prodDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Product Description</label>
                                        </div>
                                        <div class="relative z-0 lg:w-1/2 my-5 group">
                                            <input type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " disabled :value="stockData.unit"/>
                                            <label for="prodDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                        </div>                               
                                    </div>
                                </div>
                                <div v-else-if="stockNo">
                                    <p class="text-rose-500 text-xs italic mt-2">No product found!</p>
                                </div>
                            </div>
                            <div v-if="ppmp.ppmp_type == 'individual'" class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="addParticular.firstQty" type="number" name="firstQty" id="firstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                    <label for="firstQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">1st Semester(Qty)</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="addParticular.secondQty" type="number" name="secondQty" id="secondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=""/>
                                    <label for="secondQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">2nd Semester(Qty)</label>
                                </div>
                            </div>
                            <div v-else class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="addParticular.firstQty" type="number" name="firstQty" id="firstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                    <label for="firstQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Quantity</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Update Quantity</h3>
                            <p class="text-sm text-zinc-700"> Enter the quantity you want to update on the input field.</p>
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Information: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.prodCode" type="text" name="editStockNo" id="editStockNo" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required readonly/>
                                    <label for="editStockNo" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock No</label>
                                    <InputError class="mt-2" :message="editParticular.errors.partId" />
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <textarea v-model="editParticular.prodDesc" type="text" name="editProdDesc" id="editProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" readonly></textarea>
                                    <label for="editProdDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                    <InputError class="mt-2" :message="editParticular.errors.prodDesc" />
                                </div>
                            </div>
                            <div v-if="ppmp.ppmp_type == 'individual'" class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.firstQty" type="number" name="editFirstQty" id="editFirstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editFirstQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">1st Semester (Qty)</label>
                                    <InputError class="mt-2" :message="editParticular.errors.firstQty" />
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.secondQty" type="number" name="editSecondQty" id="editSecondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editSecondQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">2nd Semester (Qty)</label>
                                    <InputError class="mt-2" :message="editParticular.errors.secondQty" />
                                </div>
                            </div>
                            <div v-else class="mt-5">
                                <p class="text-sm font-semibold text-[#1a0037]"> Product Quantity: </p>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="editParticular.firstQty" type="number" name="editFirstQty" id="editFirstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                    <label for="editFirstQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Quantity</label>
                                    <InputError class="mt-2" :message="editParticular.errors.firstQty" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
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
<style scoped>
    :deep(table.dataTable) {
        border: 2px solid #7393dc;
    }

    :deep(table.dataTable thead > tr > th) {
        background-color: #d8d8f6;
        border: 2px solid #7393dc;
        text-align: center;
        color: #03244d;
    }

    :deep(table.dataTable tbody > tr > td) {
        border-right: 2px solid #7393dc;
        text-align: center;
    }

    :deep(div.dt-container select.dt-input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        background-color: #fafafa;
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep(table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }
</style>