<script setup>
    import { Head, useForm, usePage} from '@inertiajs/vue3';
    import { reactive, ref, computed } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Dropdown from '@/Components/Dropdown.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import Swal from 'sweetalert2';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        ppmp: Object,
        countTrashed: Number,
        user: Number,
    });

    const form = useForm({
        conId: props.ppmp.id,
        user: props.user,
    });
    
    const addParticular  = useForm({
        transId: props.ppmp.id,
        prodCode: '',
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
    });

    const dropParticular = useForm({
        pId: '',
        user: props.user,
    });

    const modalState = ref(null);
    const showModal = (modalType) => { modalState.value = modalType; }
    const closeModal = () => { modalState.value = null; }
    const isAddPPModalOpen = computed(() => modalState.value === 'add');
    const isConfirmModalOpen = computed(() => modalState.value === 'confirm');
    const isEditPPModalOpen = computed(() => modalState.value === 'edit');
    const isDropPPModalOpen = computed(() => modalState.value === 'drop');

    const openEditPpmpModal = (particular) => {
        editParticular.partId = particular.pId;
        editParticular.prodCode = particular.prodCode;
        editParticular.prodDesc = particular.prodName;
        editParticular.firstQty = numberWithoutCommas(particular.qtyFirst);
        editParticular.secondQty = numberWithoutCommas(particular.qtySecond);
        modalState.value = 'edit';
    }

    const openDropPpmpModal = (particular) => {
        dropParticular.pId = particular.pId;
        modalState.value = 'drop';
    }

    const numberWithoutCommas = (qty) => {
        return qty.replace(/,/g, '');
    };

    const submitRequest = (method, url, formData) => {
        isLoading.value = true;

        formData[method](url, {
            preserveScroll: true,
            onSuccess: () => {
                if (errMessage.value) {
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    }).then(() => {
                        isLoading.value = false;
                    });
                } else {
                    formData.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        isLoading.value = false;
                    });
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.info('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submit = () => submitRequest('post', route('proceed.to.final.ppmp', { ppmpTransaction: form.conId}), form);
    const submitEdit = () => submitRequest('put', route('conso-particular-update', { ppmpConsolidated: editParticular.partId }), editParticular);
    const submitDrop = () => submitRequest('delete', route('conso-particular-destroy', { ppmpConsolidated: dropParticular.pId }), dropParticular);
    const submitAdd = () => submitRequest('post', route('conso-particular-store'), addParticular);

    const columns = [
        {
            data: 'prodCode',
            title: 'Stock No#',
            width: '8%'
        },
        {
            data: 'prodName',
            title: 'Description',
            width: '29%'
        },
        {
            data: 'qtyFirst',
            title: 'Jan (Qty)',
            width: '8%',
        },
        {
            data: 'qtySecond',
            title: 'May (Qty)',
            width: '8%'
        },
        {
            data: 'prodUnit',
            title: 'Unit',
            width: '8%'
        },
        {
            data: 'prodPrice',
            title: 'Price',
            width: '8%'
        },
        {
            data: 'totalQty',
            title: 'Total (Qty)',
            width: '8%'
        },
        {
            data: 'amount',
            title: 'Total (Amount)',
            width: '8%'
        },
        {
            data: null,
            title: 'Action',
            width: '25%',
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
                            <a :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Consolidated PPMP List
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                No# {{ ppmp.ppmp_code }}
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 max-w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-4 lg:gap-4">
                        <div class="transition-shadow duration-300">
                            <div class="bg-white rounded-lg shadow-md hover:shadow-lg">
                                <div class="bg-indigo-600 text-white p-4 flex justify-between rounded-t-md">
                                    <div class="font-bold text-lg">PPMP Information</div>
                                    <div v-if="hasPermission('add-app-particular') || hasPermission('print-app-summary-overview') || hasPermission('print-app') || hasPermission('confirm-app-finalization') || hasAnyRole(['Developer'])" class="flex items-center">
                                        <div class="rounded-full mx-1">
                                            <Dropdown>
                                                <template #trigger>
                                                    <button class="flex items-center rounded-full transition">
                                                        <span class="sr-only">Open options</span>
                                                        <svg class="w-7 h-7 text-indigo-100 hover:text-emerald-200" fill="currentColor" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                                                            <path d="m23.365,3.699l-1.322,1.322-3.064-3.064,1.234-1.234c.801-.801,2.108-.955,2.985-.237,1.009.825,1.064,2.316.166,3.214Zm-5.8-.328l-5.296,5.296c-.813.813-1.269,1.915-1.269,3.064v.769c0,.276.224.5.5.5h.769c1.149,0,2.251-.457,3.064-1.269l5.296-5.296-3.064-3.064Zm3.707,10.514l-.451-.26c.102-.544.153-1.088.153-1.625s-.051-1.081-.153-1.625l-.29-1.015-3.784,3.784c-1.196,1.196-2.786,1.855-4.478,1.855h-.77c-1.379,0-2.5-1.121-2.5-2.5v-.77c0-1.691.659-3.281,1.855-4.478l4.119-4.119v-.134c0-1.654-1.346-3-3-3s-3,1.346-3,3v.522c-1.047.37-2.016.929-2.857,1.649l-.45-.259c-.693-.398-1.501-.504-2.277-.295-.773.208-1.419.706-1.818,1.4-.4.694-.505,1.503-.296,2.277.208.773.706,1.419,1.401,1.819l.451.259c-.102.544-.153,1.088-.153,1.626s.051,1.082.153,1.626l-.451.259c-.695.4-1.192,1.046-1.401,1.819-.209.774-.104,1.583.295,2.276.399.695,1.045,1.193,1.819,1.401.776.21,1.584.104,2.277-.295l.45-.259c.841.721,1.81,1.279,2.857,1.649v.522c0,1.654,1.346,3,3,3s3-1.346,3-3v-.522c1.047-.37,2.016-.929,2.857-1.649l.45.259c.695.399,1.503.505,2.277.295.773-.208,1.419-.706,1.819-1.401.825-1.434.329-3.271-1.105-4.096Z"/>
                                                        </svg>
                                                    </button>
                                                </template>
                                                <template #content>
                                                    <button v-if="hasPermission('add-app-particular') ||  hasAnyRole(['Developer'])" @click="showModal('add')" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                                                        </svg>
                                                        <span class="ml-2">Add Particular</span>   
                                                    </button>
                                                    <div v-if="hasPermission('print-app-summary-overview') ||  hasAnyRole(['Developer'])">
                                                        <a v-if="ppmp.ppmp_type == 'Consolidated'" :href="route('generatePdf.summaryOfConsolidated', { ppmp: ppmp.id})" target="_blank" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                            <svg class="w-6 h-6" aria-hidden="true"  xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                                                            </svg>
                                                            <span class="ml-2">Summary Overview</span>
                                                        </a>
                                                    </div>
                                                    <a v-if="hasPermission('print-app') ||  hasAnyRole(['Developer'])" :href="route('generatePdf.ConsolidatedPpmp', { ppmp: ppmp.id})" target="_blank" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                        <svg class="w-6 h-6" aria-hidden="true"  xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                                                        </svg>
                                                        <span class="ml-2">Print List</span>
                                                    </a>
                                                    <button v-if="hasPermission('confirm-app-finalization') ||  hasAnyRole(['Developer'])" @click="showModal('confirm')" class="flex w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-indigo-100 focus:bg-indigo-100 transition duration-150 ease-in-out">
                                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="Layer 1" viewBox="0 0 24 24">
                                                            <path d="m12,7V.46c.913.346,1.753.879,2.465,1.59l3.484,3.486c.712.711,1.245,1.551,1.591,2.464h-6.54c-.552,0-1-.449-1-1Zm-3.416,12h-3.584c-.552,0-1-.448-1-1s.448-1,1-1h3.07c-.041-.328-.07-.66-.07-1s.022-.672.063-1h-3.063c-.552,0-1-.448-1-1s.448-1,1-1h3.593c.296-.728.699-1.398,1.185-2h-4.778c-.552,0-1-.448-1-1s.448-1,1-1h5.774c-.479-.531-.774-1.23-.774-2V.024c-.161-.011-.322-.024-.485-.024h-4.515C2.243,0,0,2.243,0,5v14c0,2.757,2.243,5,5,5h10c.114,0,.221-.026.333-.034-3.066-.254-5.641-2.234-6.749-4.966Zm12.327.497c.939-1.319,1.365-3.028.96-4.843-.494-2.211-2.277-3.996-4.49-4.481-4.365-.956-8.163,2.843-7.208,7.208.485,2.213,2.27,3.996,4.481,4.49,1.816.406,3.525-.021,4.843-.96l2.796,2.796c.39.39,1.024.39,1.414,0h0c.39-.39.39-1.024,0-1.414l-2.796-2.796Zm-4.135-1.033l-.004.004c-.744.744-2.058.746-2.823-.019l-1.515-1.575c-.372-.387-.372-.999,0-1.386h0c.393-.409,1.047-.409,1.44,0l1.495,1.553,2.9-2.971c.392-.402,1.038-.402,1.43,0h0c.38.388.38,1.009,0,1.397l-2.925,2.997Z"/>
                                                        </svg>
                                                        <span class="ml-2">Proceed as Final/Approve</span>   
                                                    </button>
                                                </template>
                                            </Dropdown>
                                        </div>
                                    </div>
                                </div>
                                <div class="flow-root rounded-lg border border-gray-100 py-3 shadow-sm">
                                    <dl class="-my-3 divide-y divide-gray-100 text-base">
                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Version</dt>
                                            <dd class="text-gray-700 sm:col-span-2">V.{{ ppmp.ppmp_version }}</dd>
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
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.price_adjustment * 100 }}%</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Quantity Adjustment</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.qty_adjustment * 100 }}% of the original quantity</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Maximum Allowed Quantity per Office</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.tresh_adjustment * 100 }}% of the adjustment quantity</dd>
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
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.totalAmount }}</dd>
                                        </div>

                                        <div class="grid grid-cols-1 gap-1 p-3 even:bg-gray-50 sm:grid-cols-3 sm:gap-4">
                                            <dt class="font-medium text-gray-900">Updated By</dt>
                                            <dd class="text-gray-700 sm:col-span-2">{{ ppmp.updater.name }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            <div v-if="countTrashed > 0" class="py-2">
                                <div class="rounded-lg bg-red-200 text-red-900 text-md font-semibold p-2 border-2 border-red-300 shadow-sm">
                                    <p>Item/s on Trashed : <span>{{ countTrashed }}</span></p>
                                </div>  
                            </div>
                        </div>

                        <div class="col-span-3 p-2 bg-white rounded-md shadow mt-5 lg:mt-0">
                            <div class="bg-white p-2 overflow-hidden">
                                <div class="relative overflow-x-auto">
                                    <DataTable
                                        class="display table-hover table-striped shadow-lg rounded-lg"
                                        :columns="columns"
                                        :data="ppmp.transactions"
                                        :options="{  paging: true,
                                            searching: true,
                                            ordering: false
                                        }">
                                            <template #action="props">
                                                <EditButton v-if="hasPermission('edit-app-particular') ||  hasAnyRole(['Developer'])" @click="openEditPpmpModal(props.cellData)" tooltip="Edit"/>
                                                <RemoveButton v-if="hasPermission('delete-app-particular') ||  hasAnyRole(['Developer'])" @click="openDropPpmpModal(props.cellData)" tooltip="Remove"/>
                                            </template>
                                    </DataTable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
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
                        <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Additional Particular</h3>
                        <p class="text-sm text-gray-500"> Enter the details for the add Product/Particular you wish to add.</p>
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]"> Product No: </p>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="addParticular.prodCode" type="text" name="prodCode" id="prodCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="prodCode" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Stock No.</label>
                                <InputError class="mt-2" :message="addParticular.errors.prodCode" />
                            </div>
                        </div>
                        <div class="mt-5">
                            <p class="text-sm text-[#86591e]"> Quantity: </p>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="addParticular.firstQty" type="number" name="firstQty" id="firstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="firstQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">First Semester (Qty)</label>
                            </div>
                            <div class="relative z-0 w-full group my-2">
                                <input v-model="addParticular.secondQty" type="number" name="secondQty" id="secondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                <label for="secondQty" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Second Semester (Qty)</label>
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
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Update Quantity</h3>
                        <p class="text-sm text-gray-500"> Enter the quantity you want to update on the input field.</p>
                        <div class="mt-3">
                            <p class="text-sm text-gray-500"> Product Information: </p>
                            <input v-model="editParticular.prodCode" type="text" id="prodCode" class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-01" readonly>
                            
                            <textarea v-model="editParticular.prodDesc" type="text" id="prodCode" class="mt-2 p-2 bg-gray-100 border border-gray-100 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Ex. 01-01-01" readonly></textarea>
                        </div>
                        <div class="mt-5">
                            <p class="text-sm text-gray-500"> Quantity: </p>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                    <span class="text-gray-600 text-sm font-semibold">1st Qty: </span>
                                </div>
                                <input v-model="editParticular.firstQty" type="number" id="firstQty" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="First Semester" required>
                            </div>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 left-0 pt-2 flex items-center pl-3 pointer-events-none">
                                    <span class="text-gray-600 text-sm font-semibold">2nd Qty: </span>
                                </div>
                                <input v-model="editParticular.secondQty" type="number" id="secondQty" class="mt-2 pl-16 p-2.5 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Second Semester">
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
    <Modal :show="isConfirmModalOpen" @close="closeModal"> 
        <form @submit.prevent="submit">
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
        border: 1px solid #03244d;
        margin-left: 1px;
        width: 75px;
    }

    :deep(div.dt-container .dt-search input) {
        border: 1px solid #03244d;
        margin-right: 1px;
        width: 250px;
    }

    :deep(div.dt-length > label) {
        display: none;
    }

    :deep([data-v-b21bd927] table.dataTable tbody > tr > td:nth-child(2)) {
        text-align: left !important;
    }

    :deep([data-v-b21bd927] table.dataTable tbody > tr > td:nth-child(8)) {
        text-align: right !important;
    }
</style>