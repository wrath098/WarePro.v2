<script setup>
    import { Head, router} from '@inertiajs/vue3';
    import { ref, reactive, computed, watch } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import Pagination from '@/Components/Pagination.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import { debounce } from 'lodash';
    
    const props = defineProps({
        products: Object,
        categories: Object,
        filters:Object,
        authUserId: Number,
    });

    const create = reactive({
        selectedCategory: '',
        itemId: '',
        prodDesc: '',
        prodPrice: '',
        prodUnit: '',
        prodRemarks: '',
        prodOldCode: '',
        createdBy: props.authUserId || '',
    });

    const edit = reactive({
        prodId: '',
        selectedCategory: '',
        itemId: '',
        prodDesc: '',
        prodPrice: '',
        prodUnit: '',
        prodRemarks: '',
        prodOldCode: '',
        updatedBy: props.authUserId || '',
    });

    const filteredItems = ref([]);
    const modalState = ref(null);
    const years = generateYears();

    const isAddModalOpen = computed(() => modalState.value === 'add');
    const isEditModalOpen = computed(() => modalState.value === 'edit');
    const isModifyModalOpen = computed(() => modalState.value === 'modify');
    const isDeactivateModalOpen = computed(() => modalState.value === 'drop');
    
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
        edit.prodDesc = product.desc;
        edit.prodPrice = product.price;
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
        edit.prodOldCOde = product.oldNo;
        modalState.value = 'modify';
    };

    const openDeactivateModal = (product) => {
        edit.prodId = product.id;
        modalState.value = 'drop';
    };

    let search = ref(props.filters.search);

    watch(search, debounce(function (value) {
        router.get('products', { search: value }, {
            preserveState: true,
            preserveScroll:true,
            replace:true
        });
    }, 500));

    function generateYears() {
        const currentYear = new Date().getFullYear() + 5;
        return Array.from({ length: 11 }, (_, i) => currentYear - i);
    }

    const submitForm = (url, data) => {
        Inertia.post(url, data, {
            onSuccess: () => closeModal(),
            onError: (errors) => {
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };

    const submit = () => submitForm('products/save', create);
    const submitEdit = () => submitForm('products/update', edit);
    const submitModify = () => submitForm('products/move-and-modify', edit);
    const submitDeactivate = () => submitForm('products/deactivate', edit);
</script>

<template>
    <Head title="Products" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-3">Offices</h2>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                            <button @click="showModal('add')" class="flex items-center text-white bg-gradient-to-r from-indigo-500 via-indigo-600 to-indigo-700 hover:bg-gradient-to-br font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2">
                                <span class="mr-2">Add New Product</span>
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4.243a1 1 0 1 0-2 0V11H7.757a1 1 0 1 0 0 2H11v3.243a1 1 0 1 0 2 0V13h3.243a1 1 0 1 0 0-2H13V7.757Z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-indigo-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="search" type="text" id="search" class="block p-2 ps-10 text-sm text-gray-900 border border-indigo-600 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 " placeholder="Search for Item Names">
                            </div>
                        </div>
                        <table class="w-full text-left rtl:text-right text-gray-900 ">
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
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Item Class
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-3/12">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Unit Of Measure
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Price
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Remarks
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Action/s
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(product, index) in products.data" :key="product.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  index+1 }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  product.newNo }}
                                    </td>
                                    <td class="py-2 text-center text-sm">
                                        {{ product.oldNo }}
                                    </td>
                                    <td class="py-2">
                                        {{ product.itemName }}
                                    </td>
                                    <td class="py-2">
                                        {{ product.desc }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ product.unit }}
                                    </td>
                                    <td class="py-2 text-right">
                                        {{ product.price }}
                                    </td>
                                    
                                    <td class="py-2 text-center">
                                        {{ product.remarks }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <EditButton @click="openEditModal(product)" >
                                            <svg class="w-5 h-5 text-white hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                                                <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                                            </svg>
                                        </EditButton>
                                        <EditButton @click="openModifyModal(product)" class="bg-blue-500 hover:bg-blue-100">
                                            <svg class="w-5 h-5 text-white hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h13M4 16l4-4m-4 4 4 4M20 8H7m13 0-4 4m4-4-4-4"/>
                                            </svg>
                                        </EditButton>
                                        <RemoveButton @click="openDeactivateModal(product)">
                                            <svg class="w-5 h-5 text-white hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm5.757-1a1 1 0 1 0 0 2h8.486a1 1 0 1 0 0-2H7.757Z" clip-rule="evenodd"/>
                                            </svg>
                                        </RemoveButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-center p-5">
                        <Pagination :links="products.links" />
                    </div>
                </div>
            </div>
        </div>
        <Modal :show="isAddModalOpen" @close="closeModal"> 
            <form @submit.prevent="submit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">Creat New Product</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500"> Enter the details for the new Product you wish to add.</p>
                                <div class="mt-5">
                                    <p class="text-sm text-gray-500">Choose Category | Item Class</p>
                                    <select v-model="create.selectedCategory" @change="onCategoryChange(create)" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Please choose the category applicable to the product</option>
                                        <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>

                                    <select v-model="create.itemId" v-if="filteredItems.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Please choose an item applicable to the product</option>
                                        <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-gray-500"> Product Information</p>
                                    <input type="text" v-model="create.prodDesc" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Description : Ex. 500ml/bottle, 70% Ethyl" required>
                                    <input type="text" v-model="create.prodPrice" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Unit Price : Ex. 251.00" required>
                                    <select v-model="create.prodUnit" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option disabled value="">Select Unit Of Measure</option>
                                        <option value="Book">Book</option>
                                        <option value="Bottle">Bottle</option>
                                        <option value="Box">Box</option>
                                        <option value="Bundle">Bundle</option>
                                        <option value="Cannister">Cannister</option>
                                        <option value="Gallon">Gallon</option>
                                        <option value="Kilo">Kilo</option>
                                        <option value="Meter">Meter</option>
                                        <option value="Pad">Pad</option>
                                        <option value="Pack">Pack</option>
                                        <option value="Pair">Pair</option>
                                        <option value="Pc">Pc</option>
                                        <option value="Pouch">Pouch</option>
                                        <option value="Ream">Ream</option>
                                        <option value="Roll">Roll</option>
                                        <option value="Set">Set</option>
                                        <option value="Tube">Tube</option>
                                        <option value="Unit">Unit</option>
                                        <option value="Yard">Yard</option>
                                    </select>
                                    <select v-model="create.prodRemarks" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500">
                                        <option disabled value="">Select the Product Year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                    <input type="text" v-model="create.prodOldCode" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Old Stock No: Ex. 1001 | 1002 | 1003 | etc.">
                                    <input type="hidden" v-model="create.createdBy">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
            </form>
        </Modal>
        <Modal :show="isEditModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitEdit">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline"> Edit Product</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500"> Edit the details of the product you wish to modify.</p>
                                
                                <div class="mt-5">
                                    <p class="text-sm text-gray-500"> Product Information</p>
                                    <input type="hidden" v-model="edit.prodId">
                                    <input type="text" v-model="edit.prodDesc" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Description : Ex. 500ml/bottle, 70% Ethyl" required>
                                    <input type="text" v-model="edit.prodPrice" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Unit Price : Ex. 251.00" required>
                                    <input type="hidden" v-model="edit.updatedBy">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
            </form>
        </Modal>
        <Modal :show="isModifyModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitModify">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">Modify and Move Product</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500"> Enter the details of the Product you wish to modify and move.</p>
                                <div class="mt-5">
                                    <p class="text-sm text-gray-500">Choose Category | Item Class</p>
                                    <select v-model="edit.selectedCategory" @change="onCategoryChange(edit)" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Please choose the category applicable to the product</option>
                                        <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>

                                    <select v-model="edit.itemId" v-if="filteredItems.length" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option value="" disabled>Please choose an item applicable to the product</option>
                                        <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-gray-500"> Product Information</p>
                                    <input type="text" v-model="edit.prodDesc" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Description : Ex. 500ml/bottle, 70% Ethyl" required>
                                    <input type="text" v-model="edit.prodPrice" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Unit Price : Ex. 251.00" required>
                                    <select v-model="edit.prodUnit" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option disabled value="">Select Unit Of Measure</option>
                                        <option value="Book">Book</option>
                                        <option value="Bottle">Bottle</option>
                                        <option value="Box">Box</option>
                                        <option value="Bundle">Bundle</option>
                                        <option value="Cannister">Cannister</option>
                                        <option value="Gallon">Gallon</option>
                                        <option value="Kilo">Kilo</option>
                                        <option value="Meter">Meter</option>
                                        <option value="Pad">Pad</option>
                                        <option value="Pack">Pack</option>
                                        <option value="Pair">Pair</option>
                                        <option value="Pc">Pc</option>
                                        <option value="Pouch">Pouch</option>
                                        <option value="Ream">Ream</option>
                                        <option value="Roll">Roll</option>
                                        <option value="Set">Set</option>
                                        <option value="Tube">Tube</option>
                                        <option value="Unit">Unit</option>
                                        <option value="Yard">Yard</option>
                                    </select>
                                    <select v-model="edit.prodRemarks" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500">
                                        <option disabled value="">Select the Product Year</option>
                                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                    </select>
                                    <input type="text" v-model="edit.prodOldCode" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Old Stock No: Ex. 1001 | 1002 | 1003 | etc.">
                                    <input type="hidden" v-model="edit.updatedBy">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
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
            </form>
        </Modal>
        <Modal :show="isDeactivateModalOpen" @close="closeModal"> 
            <form @submit.prevent="submitDeactivate">
                <input type="hidden" v-model="edit.prodId">
                <input type="hidden" v-model="edit.updatedBy">
                <div class="bg-gray-100 h-auto">
                    <div class="bg-white p-6  md:mx-auto">
                        <svg class="text-red-600 w-16 h-16 mx-auto my-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-center">
                            <h3 class="md:text-2xl text-base text-gray-900 font-semibold text-center">Are you sure!</h3>
                            <p class="text-gray-600 my-2">Confirming this action will remove the selected Product into the list. you can't redo this</p>
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
    </AuthenticatedLayout>
    </div>
</template>
 