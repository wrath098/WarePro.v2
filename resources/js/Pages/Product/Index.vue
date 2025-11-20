<script setup>
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, watch } from 'vue';
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
    import InputMenu from '@/Components/InputMenu.vue';
    import General from '@/Components/Buttons/Icon/general.vue';
    import { debounce } from 'lodash';

    const {hasAnyRole, hasPermission} = useAuthPermission();
    const page = usePage();
    const isLoading = ref(false);
    const isSearchLoading = ref(false);

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
        file: '',
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
        file: '',
        updatedBy: props.authUserId || '',
    });

    const restore = useForm({
        prodId: '',
    });

    const filterCatalog = useForm({
        selectedCategory: '',
        selectedItem: '',
    });

    const filteredItems = ref([]);
    const modalState = ref(null);
    const years = generateYears();

    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isViewModalOpen = computed(() => modalState.value === 'view');
    const isUploadModalOpen = computed(() => modalState.value === 'upload');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isModifyModalOpen = computed(() => modalState.value === 'modify');
    const isDeactivateModalOpen = computed(() => modalState.value === 'drop');
    const isRestoreModalOpen = computed(() => modalState.value === 'restore');
    
    const onCategoryChange = (context) => {
        const category = props.categories.find(cat => cat.id === context.selectedCategory);
        filteredItems.value = category ? category.items : [];
        create.itemId = '';
    };

    const onCategoryChange_byName = (context) => {
        const category = props.categories.find(cat => cat.name === context.selectedCategory);
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

    const openUploadModal = (product) => {
        edit.prodId = product.id;
        edit.prodDesc = product.desc;
        modalState.value = 'upload';
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

    const openViewModal = (product) => {
        create.prodId = product;
        modalState.value = 'view';
    }

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

    function limit(text, max = 35) {
        if (!text) return ''
        return text.length > max ? text.substring(0, max) + '...' : text
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

    const categoryNames = computed(() =>
        props.categories.map(cat => cat.name)
    );

    const itemNames = computed(() =>
        filteredItems.value.map(item => item.name)
    );

    const productCatalog = ref(props.products);
    const fetchProductCatalog = async () => {
        productCatalog.value = [];
        isSearchLoading.value = true;
        
        try {
            const response = await axios.get('api/filter-product-catalog', {
                params: {
                    category: filterCatalog.selectedCategory,
                    item: filterCatalog.selectedItem,
                }
            });
            productCatalog.value = response.data.data;
            isSearchLoading.value = false;

        } catch (error) {
            isSearchLoading.value = false;
            console.error('Error fetching product catalog:', error);
        }
    };

    const search = ref('');
    const runSearch = async () => {
        const keyword = search.value.trim()
        productCatalog.value = [];

        if (!keyword) {
            productCatalog.value = props.products
            return
        }
        
        isSearchLoading.value = true

        try {
            const response = await axios.get('api/search-product-catalog', {
                params: { search: keyword }
            })

            productCatalog.value = response.data.data
        } catch (error) {
            console.error('Error fetching product catalog:', error)
        } finally {
            isSearchLoading.value = false
        }
    };

    const file = ref([]);
    const fileInput = ref(null);

    const onFileChange = (event) => {
        const selectedFile = event.target.files[0];
        if (selectedFile) {
            file.value = selectedFile;
        }
    };

    const onDrop = (event) => {
        event.preventDefault();
        const droppedFile = event.dataTransfer.files[0];
        if (droppedFile) {
            file.value = droppedFile;
        }
    };
    
    const submitUpload = () => {
        if (!file.value) {
            alert('Please select a file first!');
            return;
        }

        isLoading.value = true;
        edit.file = file.value;
        edit.post(route('upload.product.image'), {
            preserveScroll: true,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            forceFormData: true,
            onError: (errors) => {
                isLoading.value = false;
                console.error('Error: ' + JSON.stringify(errors));
            },
            onSuccess: () => {
                if (errMessage.value) {
                    isLoading.value = false;
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    });
                } else {
                    isLoading.value = false;
                    edit.reset();
                    file.value = [];
                    closeModal('upload');
                    fetchProductCatalog();

                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    });
                }
            }
        })
    };

    const debouncedSearch = debounce(runSearch, 1000);

    watch(search, () => {
        debouncedSearch()
    });

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
                            <span class="mr-2">New</span>
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
                <div v-if="!isTrashedActive" class="relative overflow-x-auto">
                    <div class="lg:flex justify-between">
                        <div class="flex flex-wrap items-end justify-center gap-2 mb-4">  
                            <InputMenu v-model="filterCatalog.selectedCategory" @change="onCategoryChange_byName(filterCatalog)" :items="categoryNames" placeholder="Select category">
                                <template #title>
                                    Categories
                                </template>
                            </InputMenu>

                            <InputMenu v-model="filterCatalog.selectedItem" :items="itemNames" placeholder="Select Item Name">
                                <template #title>
                                    Item Name
                                </template>
                            </InputMenu>

                            <button type="button" @click="fetchProductCatalog" class="flex w-auto justify-center items-center min-w-[150px] px-2 py-2 text-white transition-all bg-gray-600 rounded-md sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 16 16">
                                    <path fill="currentColor" d="M1 2h14v2L9 9v7l-2-2V9L1 4V2zm0-2h14v1H1V0z"/>
                                </svg>
                                Apply Filter
                            </button>
                        </div>
                        <div class="flex flex-wrap items-end justify-center gap-2 mb-4">
                            <input
                                v-model="search"
                                class="appearance-none border-2 pl-2 border-purple-300 hover:border-purple-400 transition-colors rounded-md w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-purple-600 focus:border-purple-600 focus:shadow-outline"
                                id="search_product"
                                type="text"
                                placeholder="Search Product..."
                            />
                        </div>
                    </div>
                    <section v-if="isSearchLoading && isLoading == false" class="flex justify-center items-center">
                        <div class="text-center p-6">
                            <div class="w-24 h-24 border-4 border-dashed rounded-full animate-spin border-[#380252] mx-auto"></div>
                            <h2 class="text-zinc-900 mt-4">Loading...</h2>
                            <p class="text-zinc-600 ">Waiting is a virtue.</p>
                        </div>
                    </section>
                    <section v-else-if="productCatalog.length == 0" class="mt-10">
                        <div class="max-w-screen-xl mx-auto text-center py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
                            <h2 class="text-3xl leading-9 font-extrabold tracking-tight text-zinc-700 sm:text-4xl sm:leading-10">
                                No Product Found!
                            </h2>
                        </div>
                    </section>
                    <section v-else class="p-1 flex flex-wrap items-center justify-center gap-10">
                        <article v-for="product in productCatalog" :key="product.id" class="max-w-xs w-full bg-purple-50/100 rounded-lg shadow-lg overflow-hidden transform duration-500 hover:-translate-y-2">
                            <div class="relative">
                                <img class="object-cover h-80 w-full" :src="product.image_path" alt="Converse sneakers" />
                                <p v-if="product.expiry == 'Yes'" class="absolute flex flex-row bottom-1 left-1 p-1 text-xs rounded bg-red-100 text-rose-800">Limited-life products</p>
                                <button @click="openUploadModal(product)" class="absolute flex flex-row bottom-1 right-1 bg-black/30 text-white px-2 py-1 rounded text-xs hover:bg-black/80">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M11 16V7.85l-2.6 2.6L7 9l5-5l5 5l-1.4 1.45l-2.6-2.6V16zm-5 4q-.825 0-1.412-.587T4 18v-3h2v3h12v-3h2v3q0 .825-.587 1.413T18 20z"/>
                                    </svg>
                                    <span class="mr-2"> Upload</span>
                                </button>
                            </div>

                            <div class="flex flex-col gap-1 mt-4 px-4">
                                <div class="flex justify-between">
                                    <h2 class="text-lg font-semibold text-zinc-700">{{ product.itemName }}</h2>
                                    <p class="font-semibold text-zinc-700">#{{ product.newNo }}</p>
                                </div>
                            
                                <span class="font-normal text-zinc-700">{{ limit(product.desc) }}</span>
                                <span class="font-semibold text-zinc-700">₱ {{ product.price }}</span>
                            </div>

                            <div class="mt-4 p-4 flex justify-center">
                                <General @click="openViewModal(product)" tooltip="View">
                                    <svg class="w-7 h-7 text-zinc-900 hover:text-emerald-700" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M2 15V9q0-.825.588-1.412T4 7t1.413.588T6 9v6q0 .825-.587 1.413T4 17t-1.412-.587T2 15m7 4q-.825 0-1.412-.587T7 17V7q0-.825.588-1.412T9 5h6q.825 0 1.413.588T17 7v10q0 .825-.587 1.413T15 19zm9-4V9q0-.825.588-1.412T20 7t1.413.588T22 9v6q0 .825-.587 1.413T20 17t-1.412-.587T18 15"/>
                                    </svg>
                                </General>
                                <General v-if="hasPermission('edit-product-item') ||  hasAnyRole(['Developer'])" @click="openEditModal(product)" tooltip="Edit">
                                    <svg class="w-7 h-7 text-zinc-900 hover:text-blue-700" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M10 15q-.425 0-.712-.288T9 14v-2.425q0-.4.15-.763t.425-.637l8.6-8.6q.3-.3.675-.45t.75-.15q.4 0 .763.15t.662.45L22.425 3q.275.3.425.663T23 4.4t-.137.738t-.438.662l-8.6 8.6q-.275.275-.637.438t-.763.162zm9.6-9.2l1.425-1.4l-1.4-1.4L18.2 4.4zM5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h6.5q.35 0 .575.175t.35.45t.087.55t-.287.525l-4.65 4.65q-.275.275-.425.638T7 10.75V15q0 .825.588 1.412T9 17h4.225q.4 0 .763-.15t.637-.425L19.3 11.75q.25-.25.525-.288t.55.088t.45.35t.175.575V19q0 .825-.587 1.413T19 21z"/>
                                    </svg>
                                </General>
                                <General v-if="hasPermission('edit-product-item') ||  hasAnyRole(['Developer'])" @click="openModifyModal(product)" tooltip="Move">
                                    <svg class="w-7 h-7 text-zinc-900 hover:text-indigo-700" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v14q0 .825-.587 1.413T19 21zm7-5q.8 0 1.475-.413t1.1-1.087q.15-.225.375-.363t.5-.137H19V5H5v9h3.55q.275 0 .5.138t.375.362q.425.675 1.1 1.088T12 16m-1-5.85V7q0-.425.288-.712T12 6t.713.288T13 7v3.15l.875-.875q.15-.15.338-.225t.375-.062t.375.087t.337.225q.275.3.288.7t-.288.7l-2.6 2.6q-.15.15-.325.213t-.375.062t-.375-.062t-.325-.213l-2.6-2.6q-.15-.15-.225-.337T8.4 9.988t.075-.363T8.7 9.3q.3-.3.713-.312t.712.287z"/>
                                    </svg>
                                </General>
                                <General v-if="hasPermission('edit-product-item') ||  hasAnyRole(['Developer'])" @click="openDeactivateModal(product)" tooltip="Trash">
                                    <svg class="w-7 h-7 text-zinc-900 hover:text-rose-700" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M7 21q-.825 0-1.412-.587T5 19V6q-.425 0-.712-.288T4 5t.288-.712T5 4h4q0-.425.288-.712T10 3h4q.425 0 .713.288T15 4h4q.425 0 .713.288T20 5t-.288.713T19 6v13q0 .825-.587 1.413T17 21zm5-7.1l1.9 1.9q.275.275.7.275t.7-.275t.275-.7t-.275-.7l-1.9-1.9l1.9-1.9q.275-.275.275-.7t-.275-.7t-.7-.275t-.7.275L12 11.1l-1.9-1.9q-.275-.275-.7-.275t-.7.275t-.275.7t.275.7l1.9 1.9l-1.9 1.9q-.275.275-.275.7t.275.7t.7.275t.7-.275z"/>
                                    </svg>
                                </General>
                            </div>
                        </article>
                    </section>
                    <!-- <div v-if="productCatalog.length > 20" class="flex justify-center gap-2 mt-4">
                        <button
                            @click="loadMore"
                            class="px-4 py-2 border-2 border-zinc-800 rounded text-zinc-800 hover:bg-zinc-800 hover:text-zinc-50"
                            :disabled="isLoading"
                        >
                            Load More
                        </button>

                        <button
                            @click="loadAll"
                            class="px-4 py-2 border-2 border-zinc-800 rounded text-zinc-800 hover:bg-zinc-800 hover:text-zinc-50"
                            :disabled="isLoading"
                        >
                            Load All ({{ total }})
                        </button>
                    </div> -->
                </div>

                <div v-if="isTrashedActive" class="relative overflow-x-auto">
                    <!-- TRASHED TABLE -->
                    <DataTable
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
        <Modal :show="isViewModalOpen" @close="closeModal"> 
            <div class="flex bg-white rounded-lg flex-col md:flex-row">
                <div class="relative w-full md:w-5/12 flex justify-center items-center">
                    <img :src="create.prodId.image_path" alt="" class="object-cover h-full w-full rounded-t-lg md:rounded-l-lg md:rounded-t-none">
                </div>
                <div class="flex-auto p-6 w-full md:w-7/12">
                    <div class="flex flex-wrap">
                        <h1 class="flex-auto text-xl font-semibold text-zinc-800">Code #{{ create.prodId.newNo }}</h1>
                        <div class="text-xl font-semibold text-zinc-800">₱ {{ create.prodId.price }} </div>
                        <div class="flex-none w-full mt-2 text-sm font-medium text-zinc-800">Classification: {{ create.prodId.itemName }} - {{ create.prodId.className }}</div>
                    </div>
                    <div class="flex mt-4 mb-6 text-zinc-700">
                        Description: {{ create.prodId.desc }}
                    </div>
                    <div class="mt-4 mb-6 text-zinc-700">
                        <p>Unit: {{ create.prodId.unit }}</p>
                        <p>Previous Code: # {{ create.prodId.oldNo }}</p>
                    </div>
                    <div class="flex mb-4 text-sm font-medium">
                        <p class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 focus:ring-offset-indigo-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg ">Available</p>
                    </div>
                    <p v-if="create.prodId.expiry == 'Yes'" class="text-sm font-semibold text-rose-700 text-center">Limited-life products</p>
                </div>
            </div>
            <div class="flex justify-center items-center m-5">
                <DangerButton @click="closeModal"> 
                    <svg class="w-5 h-5 text-white mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Close
                </DangerButton>
            </div>
        </Modal>
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
        <Modal :show="isUploadModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitUpload">
                <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Upload Product Image</h3>
                            <p class="text-sm text-zinc-700"> Choose an image file from your device to use as the product's display photo.</p>
                            <div class="mt-2">
                                <div class="mt-5">
                                    <p class="text-sm font-semibold text-[#1a0037] mb-2"> Product Information</p>
                                    <textarea class="w-full rounded-md h-auto bg-zinc-200" disabled>{{ edit.prodDesc }}</textarea>
                                </div>
                                <div class="pt-4"> 
                                    <p class="text-sm font-semibold text-[#1a0037] mb-2"> Upload File</p>

                                    <div class="mb-8 border-2 border-dashed border-slate-400 hover:border-slate-600 bg-zinc-100 hover:bg-zinc-200" @dragover.prevent @drop="onDrop">
                                        <input type="file" ref="fileInput" @change="onFileChange" multiple name="files[]" id="file" class="sr-only" accept=".png,.jpeg,.jpg"/>
                                        <label for="file" class="relative flex min-h-[200px] items-center justify-center rounded-md border border-dashed border-[#e0e0e0] p-12 text-center cursor-pointer">
                                            <div class="cursor-pointer w-full mx-auto flex flex-col items-center justify-center">
                                                <svg class="w-10 h-10 text-sky-600" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                                    <path fill="currentColor" d="M5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v14q0 .825-.587 1.413T19 21zm0-2h14V5H5zm0 0V5zm2-2h10q.3 0 .45-.275t-.05-.525l-2.75-3.675q-.15-.2-.4-.2t-.4.2L11.25 16L9.4 13.525q-.15-.2-.4-.2t-.4.2l-2 2.675q-.2.25-.05.525T7 17m1.5-7q.625 0 1.063-.438T10 8.5t-.437-1.062T8.5 7t-1.062.438T7 8.5t.438 1.063T8.5 10"/>
                                                </svg>

                                                <span class="mb-2 block text-base font-semibold text-[#545557]">
                                                    Drag and Drop a PNG or JPEG file here
                                                </span>
                                                <span class="mb-2 block text-base font-medium text-[#6B7280]">
                                                    Or
                                                </span>
                                                <span class="inline-flex rounded border-2 border-dashed border-slate-400 hover:border-slate-600 bg-gray-100 text-gray-400 hover:bg-gray-100 hover:text-[#1f2024] py-2 px-7 text-base font-medium">
                                                    Browse
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                    <InputError :message="edit.errors.file" />

                                    <div v-if="file">
                                        <h4 class="text-sm font-semibold text-zinc-700">Selected Files: <span class="text-base text-gray-700 italic">{{ file.name }}</span></h4>
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