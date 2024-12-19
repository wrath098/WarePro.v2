<script setup>
    import { Head, router} from '@inertiajs/vue3';
    import { ref, reactive, computed, watch } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import { debounce } from 'lodash';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import Pagination from '@/Components/Pagination.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import ModifyButton from '@/Components/Buttons/ModifyButton.vue';
    
    const props = defineProps({
        products: Object,
        categories: Object,
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
        edit.prodOldCode = product.oldNo;
        modalState.value = 'modify';
    };

    const openDeactivateModal = (product) => {
        edit.prodId = product.id;
        modalState.value = 'drop';
    };

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
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Products</a></li>
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-indigo-400 my-2 italic">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-gray-400 my-2 italic">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-end">
                            <div class="flex mb:flex-column">
                                <AddButton @click="showModal('add')">
                                    <span class="mr-2">New Product</span>
                                </AddButton>
                                <PrintButton :href="route('generatePdf.ProductActiveList')" target="_blank">
                                    <span class="mr-2">Print List</span>
                                </PrintButton>
                            </div>
                        </div>
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
                                <tr v-for="(product, index) in products" :key="product.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
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
                                        <EditButton @click="openEditModal(product)" tooltip="Edit"/>
                                        <ModifyButton @click="openModifyModal(product)" tooltip="Modify"/>
                                        <RemoveButton @click="openDeactivateModal(product)" tooltip="Remove"/>
                                    </td>
                                </tr>
                            </tbody>
                        </DataTable>
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
                                    <input type="number" v-model="create.prodPrice" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Unit Price : Ex. 251.00" required>
                                    <select v-model="create.prodUnit" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" required>
                                        <option disabled value="">Select Unit Of Measure</option>
                                        <option value="Book">Book</option>
                                        <option value="Bottle">Bottle</option>
                                        <option value="Box">Box</option>
                                        <option value="Bundle">Bundle</option>
                                        <option value="Cannister">Cannister</option>
                                        <option value="Cart">Cart</option>
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
                                    <input type="number" v-model="edit.prodPrice" class="mt-2 p-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500" placeholder="Unit Price : Ex. 251.00" required>
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
                                        <option value="Cart">Cart</option>
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
 