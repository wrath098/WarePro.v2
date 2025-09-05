<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import ModifyButton from '@/Components/Buttons/ModifyButton.vue';
    import Swal from 'sweetalert2';
    import TrashedButton from '@/Components/Buttons/TrashedButton.vue';
    import axios from 'axios';
    import RecycleIcon from '@/Components/Buttons/RecycleIcon.vue';
    import useAuthPermission from '@/Composables/useAuthPermission';
    import InputError from '@/Components/InputError.vue';
    import Dropdown from '@/Components/Dropdown.vue';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);

    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    const warningMessage = computed(() => page.props.flash.warning);

    const props = defineProps({
        products: Object,
        categories: Object,
        authUserId: Number,
    });

    const trashedItems = ref([]);
    const isTrashedActive = ref(false);
    const fetchTrashedItems = async () => {
        isLoading.value = true;
        isTrashedActive.value = true;
        try {
            const response = await axios.get('products/trashed-items');
            trashedItems.value = response.data;
            isLoading.value = false;
        } catch (error) {
            isLoading.value = false;
            console.error('Error fetching trashed items:', error);
        }
    };

    const create = useForm({
        selectedCategory: '',
        itemId: '',
        prodDesc: '',
        prodPrice: '',
        prodUnit: '',
        prodRemarks: '',
        prodOldCode: '',
        hasExpiry: '',
        createdBy: props.authUserId || '',
    });

    const edit = useForm({
        prodId: '',
        selectedCategory: '',
        itemId: '',
        prodDesc: '',
        prodPrice: '',
        prodUnit: '',
        prodRemarks: '',
        prodOldCode: '',
        hasExpiry: '',
        updatedBy: props.authUserId || '',
    });

    const restore = useForm({
        prodId: '',
    });

    const filteredItems = ref([]);
    const modalState = ref(null);
    const years = generateYears();

    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isModifyModalOpen = computed(() => modalState.value === 'modify');
    const isDeactivateModalOpen = computed(() => modalState.value === 'drop');
    const isRestoreModalOpen = computed(() => modalState.value === 'restore');
    
    const onCategoryChange = (context) => {
        const category = props.categories.find(cat => cat.id === context.selectedCategory);
        filteredItems.value = category ? category.items : [];
        create.itemId = '';
    };

    const showModal = (modalType) => {
        modalState.value = modalType;
    }

    const closeModal = () => {
        modalState.value = null;
    }

    const openEditModal = (product) => {
        edit.prodId = product.id;
        edit.prodOldCode = product.oldNo;
        edit.prodDesc = product.desc;
        edit.prodPrice = product.price;
        edit.hasExpiry = (product.expiry === 'Yes') ? 1 : 0;
        modalState.value = 'edit';
    };

    const openModifyModal = (product) => {
        edit.prodId = product.id;
        edit.selectedCategory = '';
        edit.itemId = '';
        edit.prodDesc = product.desc;
        edit.prodPrice = product.price;
        edit.prodUnit = product.unit;
        edit.prodRemarks = product.remarks;
        edit.prodOldCode = product.oldNo;
        edit.hasExpiry = (product.expiry === 'Yes') ? 1 : 0;
        modalState.value = 'modify';
    };

    const openDeactivateModal = (product) => {
        edit.prodId = product.id;
        modalState.value = 'drop';
    };

    const openRestoreModal = (prodId) => {
        restore.prodId = prodId;
        modalState.value = 'restore';
    };

    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 5 }, (_, i) => currentYear - i);
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
                }else if (warningMessage.value) {
                    formData.reset();
                    Swal.fire({
                        title: 'Warning!',
                        text: warningMessage.value,
                        icon: 'warning',
                        confirmButtonText: 'OK',
                    }).then(() => closeModal());
                } else {
                    formData.reset();
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => closeModal());
                }
            },
            onError: (errors) => {
                isLoading.value = false;
                console.log('Error: ' + JSON.stringify(errors));
            },
        });
    };

    const submit = () => submitForm('post', route('product.store'), create);
    const submitEdit = () => submitForm('put', route('product.update'), edit);
    const submitModify = () => submitForm('put', route('product.move.modify'), edit);
    const submitDeactivate = () => submitForm('put', route('product.deactivate'), edit);
    const submitRestore = () => submitForm('put', route('product.restore'), restore);

    const activeColumns = [
        {
            data: 'newNo',
            title: 'New Stock No#',
            width: '8.333333%'
        },
        {
            data: 'oldNo',
            title: 'Old Stock No#',
            width: '8.333333%'
        },
        {
            data: 'itemName',
            title: 'Item Class',
            width: '8.333333%'
        },
        {
            data: 'desc',
            title: 'Description',
            width: '25%',
            render: function(data, type, row) {
                const expiration = row.expiry === 'Yes' 
                    ? '<span class="inline-flex items-center p-1 text-xs rounded bg-red-100 text-red-800">With Expiration</span>' 
                    : '';
                return `${data} ${expiration}`;
            }
        },
        {
            data: 'unit',
            title: 'Unit Of Measurement',
            width: '8.333333%'
        },
        {
            data: 'price',
            title: 'Price',
            width: '8.333333%'
        },
        {
            data: 'remarks',
            title: 'Year',
            width: '8.333333%'
        },
        {
            data: null,
            title: 'Action',
            width: '16.666667%',
            render: '#action',
        },
    ];

    const trashedColumns = [
        {
            data: null,
            title: 'Action',
            width: '8.333333%',
            render: '#action',
        },
        {
            data: 'stockNo',
            title: 'New No#',
            width: '8.333333%'
        },
        {
            data: 'oldNo',
            title: 'Old No#',
            width: '8.333333%'
        },
        {
            data: 'desc',
            title: 'Description',
            width: '25%'
        },
        {
            data: 'unit',
            title: 'Unit of Measurement',
            width: '8.333333%'
        },
        {
            data: 'usedSince',
            title: 'Year',
            width: '8.333333%'
        },
        {
            data: 'updatedBy',
            title: 'Trashed By',
            width: '8.333333%'
        },
        {
            data: 'updatedAt',
            title: 'Trashed At',
            width: '16.666667%'
        },
    ];

    function formattedAmount(model, field) {
        const value = model[field];
        if (!value) return '';
        const number = parseFloat(value.replace(/,/g, ''));
        return isNaN(number) ? value : number.toLocaleString();
    }

    function handleAmountInput(e, model, field) {
        const raw = e.target.value.replace(/,/g, '');
        if (/^\d*\.?\d*$/.test(raw)) {
            model[field] = raw;
        }
    }

    const measurements = [
        { value: 'Book', label: 'Book' },
        { value: 'Bottle', label: 'Bottle' },
        { value: 'Box', label: 'Box' },
        { value: 'Bundle', label: 'Bundle' },
        { value: 'Cannister', label: 'Cannister' },
        { value: 'Cart', label: 'Cart' },
        { value: 'Gallon', label: 'Gallon' },
        { value: 'Kilo', label: 'Kilo' },
        { value: 'MeterGroup', label: 'Meter' },
        { value: 'Pad', label: 'Pad' },
        { value: 'Pack', label: 'Pack' },
        { value: 'Pair', label: 'Pair' },
        { value: 'Pc', label: 'Pc' },
        { value: 'Pouch', label: 'Pouch' },
        { value: 'Ream', label: 'Ream' },
        { value: 'Roll', label: 'Roll' },
        { value: 'Set', label: 'Set' },
        { value: 'Tube', label: 'Tube' },
        { value: 'Unit', label: 'Unit' },
        { value: 'Yard', label: 'Yard' },
    ];

</script>

<template>
    <Head title="Products" />
    <AuthenticatedLayout>
        <template #header>
            <nav class="flex justify-between flex-col lg:flex-row" aria-label="Breadcrumb">
                <ol class="inline-flex items-center justify-center space-x-1 md:space-x-3 bg">
                    <li class="inline-flex items-center" aria-current="page">
                        <a :href="route('product.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-4 h-4 w-4">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Products
                        </a>
                    </li>
                    <li :aria-current="!isTrashedActive ? 'page' : undefined">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a :href="route('product.display.active')" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Item List
                            </a>
                        </div>
                    </li>
                    <li v-if="isTrashedActive" aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Trashed
                            </a>
                        </div>
                    </li>
                </ol>
                <ol v-if="hasPermission('create-product-item') || hasPermission('print-product-list') || hasPermission('view-trashed-product-items') || hasAnyRole(['Developer'])">
                    <li class="flex flex-col lg:flex-row">
                        <AddButton v-if="hasPermission('create-product-item') ||  hasAnyRole(['Developer'])" @click="showModal('add')" class="mx-1 my-1 lg:my-0">
                            <span class="mr-2">New Product Item</span>
                        </AddButton>
                        <Dropdown>
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-medium leading-4 bg-rose-900 text-gray-50 transition duration-150 ease-in-out hover:bg-rose-800 focus:outline-none">
                                        Print
                                        <svg
                                            class="-me-0.5 ms-2 h-4 w-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                            <template #content>
                                <a v-if="hasPermission('print-product-list') || hasAnyRole(['Developer'])" :href="route('generatePdf.ProductActiveList')" target="_blank" rel="noopener noreferrer" class="flex flex-row w-full px-4 py-2 text-start text-base leading-5 text-gray-900 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    <span class="mr-2">PDF File</span> 
                                    <svg class="w-6 h-6" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
                                        <path fill="currentColor" d="M3.5 8H3V7h.5a.5.5 0 0 1 0 1M7 10V7h.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5z"/>
                                        <path fill="currentColor" fill-rule="evenodd" d="M1 1.5A1.5 1.5 0 0 1 2.5 0h8.207L14 3.293V13.5a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 1 13.5zM3.5 6H2v5h1V9h.5a1.5 1.5 0 1 0 0-3m4 0H6v5h1.5A1.5 1.5 0 0 0 9 9.5v-2A1.5 1.5 0 0 0 7.5 6m2.5 5V6h3v1h-2v1h1v1h-1v2z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                <a v-if="hasPermission('print-product-list') || hasAnyRole(['Developer'])" :href="route('generate.product.active.list.word')" target="_blank" rel="noopener noreferrer" class="flex flex-row w-full px-4 py-2 text-start text-base leading-5 text-gray-900 hover:bg-indigo-900 hover:text-gray-50 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                    <span class="mr-2">Word File</span>
                                    <svg class="w-6 h-6" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M17 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4zM2.859 2.877l12.57-1.795a.5.5 0 0 1 .571.494v20.848a.5.5 0 0 1-.57.494L2.858 21.123a1 1 0 0 1-.859-.99V3.867a1 1 0 0 1 .859-.99M11 8v4.989L9 11l-1.99 2L7 8H5v8h2l2-2l2 2h2V8z"/>
                                    </svg>
                                </a>
                            </template>
                        </Dropdown>
                        <TrashedButton v-if="hasPermission('view-trashed-product-items') || hasAnyRole(['Developer'])" @click="fetchTrashedItems" class="mx-1 my-1 lg:my-0" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                            <span class="mr-2">Trashed</span>
                        </TrashedButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="my-4 w-screen-2xl bg-zinc-300 shadow rounded-md mb-8">
            <div class="overflow-hidden p-4 shadow-sm sm:rounded-lg">
                <div class="relative overflow-x-auto">
                    <!-- ACTIVE TABLE -->
                    <DataTable
                        v-if="!isTrashedActive"
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="activeColumns"
                        :data="props.products"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }">
                            <template #action="props">
                                <EditButton v-if="hasPermission('edit-product-item') ||  hasAnyRole(['Developer'])" @click="openEditModal(props.cellData)" tooltip="Edit"/>
                                <ModifyButton v-if="hasPermission('modify-product-item') ||  hasAnyRole(['Developer'])" @click="openModifyModal(props.cellData)" tooltip="Move"/>
                                <RemoveButton v-if="hasPermission('delete-product-item') ||  hasAnyRole(['Developer'])" @click="openDeactivateModal(props.cellData)" tooltip="Remove"/>
                            </template>
                    </DataTable>

                    <!-- TRASHED TABLE -->
                    <DataTable
                        v-if="isTrashedActive"
                        class="display table-hover table-striped shadow-lg rounded-lg bg-zinc-100"
                        :columns="trashedColumns"
                        :data="trashedItems.data"
                        :options="{  paging: true,
                            searching: true,
                            ordering: true
                        }">
                            <template #action="props">
                                <RecycleIcon @click="openRestoreModal(props.cellData.id)" tooltip="Restore" />
                            </template>
                    </DataTable>
                </div>
            </div>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submit">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Creat New Product</h3>
                            <p class="text-sm text-zinc-700"> Enter the details for the new Product you wish to add.</p>
                            <div class="mt-10">
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]">Choose Category | Item Class</p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.selectedCategory" @change="onCategoryChange(create)" name="category" id="category" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose the category applicable to the product</option>
                                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <label for="category" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category</label>
                                        <InputError class="mt-2" :message="create.errors.selectedCategory" />
                                    </div>
                                    <div class="relative z-0 w-full my-3 group" v-if="filteredItems.length">
                                        <select v-model="create.itemId" name="item" id="item" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose an item applicable to the product</option>
                                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                                {{ item.name }}
                                            </option>
                                        </select>
                                        <label for="item" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Class</label>
                                        <InputError class="mt-2" :message="create.errors.itemId" />
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-1">
                                        <textarea v-model="create.prodDesc" type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required> </textarea>
                                        <label for="prodDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                        <InputError class="mt-2" :message="create.errors.prodDesc" />
                                    </div>
                                    <div class="grid lg:grid-cols-2 lg:gap-6 mt-3">
                                        <div class="relative z-0 w-full group">
                                            <input :value="formattedAmount(create, 'prodPrice')" @input="e => handleAmountInput(e, create, 'prodPrice')" type="text" name="prodPrice" id="prodPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " required />
                                            <label for="prodPrice" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                            <InputError class="mt-2" :message="create.errors.prodPrice" />
                                        </div>
                                        <div class="relative z-0 w-full group">
                                            <select v-model="create.prodUnit" name="prodUnit" id="prodUnit" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                                <option disabled selected value="">Select Unit Of Measure</option>
                                                <option v-for="(measure, index) in measurements" :key="index" :value="measure.value">{{ measure.label }}</option>
                                            </select>
                                            <label for="prodUnit" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                            <InputError class="mt-2" :message="create.errors.prodUnit" />
                                        </div>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.prodRemarks" name="prodRemarks" id="prodRemarks" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                            <option value="" disabled selected>Select the Product Year</option>
                                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                        </select>
                                        <label for="prodRemarks" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Year</label>
                                        <InputError class="mt-2" :message="create.errors.prodRemarks" />
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="create.prodOldCode" type="text" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=""/>
                                        <label for="prodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Old Stock Number</label>
                                        <InputError class="mt-2" :message="create.errors.prodOldCode" />
                                    </div>
                                    <input type="hidden" v-model="create.createdBy">
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.hasExpiry" name="hasExpiry" id="hasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer">
                                            <option value="" disabled selected>Is Product has expiration?</option>
                                            <option :value="1">Yes</option>
                                            <option :value="0">No</option>
                                        </select>
                                        <label for="hasExpiry" class="font-semibold text-zinc-700 absolute text-sm 00 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                        <InputError class="mt-2" :message="create.errors.hasExpiry" />
                                    </div>
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
        <Modal :show="isEditModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Edit Product</h3>
                            <p class="text-sm text-zinc-700"> Edit the details of the product you wish to modify.</p>
                            <div class="mt-2">
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037] mb-2"> Product Information</p>
                                    <input type="hidden" v-model="edit.prodId">
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="edit.prodOldCode" type="text" name="editprodOldCode" id="editprodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" />
                                        <label for="editprodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Old Stock No</label>
                                        <InputError class="mt-2" :message="edit.errors.prodOldCode" />
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <textarea v-model="edit.prodDesc" type="text" name="editProdDesc" id="editProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required></textarea>
                                        <label for="editProdDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                        <InputError class="mt-2" :message="edit.errors.prodDesc" />
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input :value="formattedAmount(edit, 'prodPrice')" @input="e => handleAmountInput(e, edit, 'prodPrice')" type="text" name="editProdPrice" id="editProdPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                        <label for="editProdPrice" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                        <InputError class="mt-2" :message="edit.errors.prodPrice" />
                                    </div>
                                    <input type="hidden" v-model="edit.updatedBy">
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.hasExpiry" name="editHasExpiry" id="editHasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer">
                                            <option value="" disabled selected>Is Product has expiration?</option>
                                            <option :value="1">Yes</option>
                                            <option :value="0">No</option>
                                        </select>
                                        <label for="editHasExpiry" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                        <InputError class="mt-2" :message="edit.errors.hasExpiry" />
                                    </div>
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
        <Modal :show="isModifyModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitModify">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline">Move Product</h3>
                            <p class="text-sm text-zinc-700"> Enter the details of the Product you wish to move.</p>
                            <div class="mt-2">
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]">Choose Category | Item Class</p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.selectedCategory" @change="onCategoryChange(edit)" name="modifyCategory" id="modifyCategory" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose the category applicable to the product</option>
                                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <label for="modifyCategory" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category</label>
                                        <InputError class="mt-2" :message="edit.errors.selectedCategory" />
                                    </div>
                                    <div class="relative z-0 w-full my-3 group" v-if="filteredItems.length">
                                        <select v-model="edit.itemId" name="modifyItem" id="modifyItem" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose an item applicable to the product</option>
                                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                                {{ item.name }}
                                            </option>
                                        </select>
                                        <label for="modifyItem" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Class</label>
                                        <InputError class="mt-2" :message="edit.errors.itemId" />
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-1">
                                        <textarea v-model="edit.prodDesc" type="text" name="modifyProdDesc" id="modifyProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required readonly> </textarea>
                                        <label for="modifyProdDesc" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                        <InputError class="mt-2" :message="edit.errors.prodDesc" />
                                    </div>
                                    <div class="grid lg:grid-cols-2 lg:gap-6 mt-3">
                                        <div class="relative z-0 w-full group">
                                            <input v-model="edit.prodPrice" type="text" name="modifyProdPrice" id="modifyProdPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " required readonly />
                                            <label for="modifyProdPrice" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                            <InputError class="mt-2" :message="edit.errors.prodPrice" />
                                        </div>
                                        <div class="relative z-0 w-full group">
                                            <select v-model="edit.prodUnit" name="modifyProdUnit" id="modifyProdUnit" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required disabled>
                                                <option disabled selected value="">Select Unit Of Measure</option>
                                                <option v-for="(measure, index) in measurements" :key="index" :value="measure.value">{{ measure.label }}</option>
                                            </select>
                                            <label for="modifyProdUnit" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                            <InputError class="mt-2" :message="edit.errors.prodUnit" />
                                        </div>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.prodRemarks" name="modifyProdRemarks" id="modifyProdRemarks" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required disabled>
                                            <option value="" disabled selected>Select the Product Year</option>
                                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                        </select>
                                        <label for="modifyProdRemarks" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Year</label>
                                        <InputError class="mt-2" :message="edit.errors.prodRemarks" />
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="edit.prodOldCode" type="number" name="modifyProdOldCode" id="modifyProdOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" readonly/>
                                        <label for="modifyProdOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Old Stock Number</label>
                                        <InputError class="mt-2" :message="edit.errors.prodOldCode" />
                                    </div>
                                    <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                        <div class="relative z-0 w-full my-3 group">
                                            <select v-model="edit.hasExpiry" name="editHasExpiry" id="editHasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" disabled>
                                                <option value="" disabled selected>Is Product has expiration?</option>
                                                <option :value="1">Yes</option>
                                                <option :value="0">No</option>
                                            </select>
                                            <label for="editHasExpiry" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                            <InputError class="mt-2" :message="edit.errors.hasExpiry" />
                                        </div>
                                    </div>
                                    <input type="hidden" v-model="edit.updatedBy">
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
                        Save Changes 
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
        <Modal :show="isDeactivateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDeactivate">
                <input type="hidden" v-model="edit.prodId">
                <input type="hidden" v-model="edit.updatedBy">
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-rose-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Move to Trash!</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will remove the selected Product into the list. This action can't be undone.</p>
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
        <Modal :show="isRestoreModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitRestore">
                <div class="bg-zinc-300 h-auto">
                    <div class="p-6  md:mx-auto">
                        <svg class="text-emerald-600 w-16 h-16 mx-auto my-6" fill="currentColor" viewBox="0 -0.5 25 25" xmlns="http://www.w3.org/2000/svg">
                            <path d="m11.538 16.444-.211 5.178-.028.31-5.91-.408c-.37-.039-.696-.201-.943-.443-.277-.253-.501-.56-.654-.905l-.007-.017c-.095-.225-.167-.486-.202-.759l-.002-.015c-.009-.085-.014-.184-.014-.284 0-.223.026-.441.074-.65l-.004.019q.106-.521.165-.774c.102-.368.205-.667.324-.959l-.021.059q.239-.647.267-.743 1.099.167 7.164.392zm-5.447-8.245 2.533 5.333-2.068-1.294c-.536.606-1.051 1.269-1.524 1.964l-.044.069c-.352.503-.692 1.08-.986 1.682l-.034.077q-.338.743-.555 1.33c-.104.251-.194.548-.255.856l-.005.031-.056.295-2.673-5.023c-.15-.222-.243-.493-.253-.786v-.003c-.003-.039-.005-.085-.005-.131 0-.19.032-.372.091-.541l-.003.012.112-.253q.495-.886 1.604-2.641l-1.967-1.215zm17.32 7.275-2.641 5.051c-.109.268-.286.49-.509.652l-.004.003c-.172.136-.378.236-.602.286l-.01.002-.253.056q-.999.098-3.081.165l.112 2.311-3.236-5.164 2.971-5.094.098 2.434c.711.083 1.534.131 2.368.131.568 0 1.131-.022 1.687-.065l-.074.005c.875-.058 1.69-.224 2.462-.485l-.068.02zm-11.042-13.002q-.66.886-3.729 6.121l-4.457-2.631-.267-.165 3.166-5.009c.2-.298.49-.521.831-.632l.011-.003c.261-.097.562-.152.876-.152.088 0 .175.004.261.013l-.011-.001c.251.022.483.081.698.171l-.015-.006c.227.092.419.192.601.306l-.016-.009c.218.146.409.299.585.466l-.002-.002q.338.31.507.485t.507.555q.341.38.454.493zm9.216 4.319 2.983 5.108c.122.238.194.519.194.817 0 .09-.007.179-.019.266l.001-.01c-.058.392-.194.744-.393 1.052l.006-.01c-.133.199-.286.371-.461.518l-.004.003c-.158.137-.333.267-.517.383l-.018.01c-.194.115-.42.219-.656.301l-.027.008q-.429.155-.66.225t-.725.197l-.647.165q-.479-1.013-3.729-6.135l4.404-2.744zm-2.012-3.18 1.998-1.168-3.095 5.249-5.897-.281 2.125-1.21c-.355-.933-.71-1.702-1.109-2.443l.054.11c-.348-.671-.701-1.239-1.091-1.779l.028.041q-.485-.655-.908-1.126c-.204-.238-.42-.452-.652-.648l-.008-.007-.239-.183 5.695.014c.047-.005.101-.008.157-.008.24 0 .468.058.668.16l-.008-.004c.217.098.4.234.55.401l.001.002.155.211q.549.854 1.576 2.669z"/>
                        </svg>

                        <div class="text-center">
                            <h3 class="md:text-2xl text-base font-semibold text-[#1a0037] text-center">Restore Trashed Item?</h3>
                            <p class="text-zinc-700 my-2">Confirming this action will restore the selected Product from the trash list. <br> Are you sure you want to restore?</p>
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

    :deep(table.dataTable tbody > tr > td:nth-child(4)) {
        text-align: left !important;
    }

</style>