<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import { ref, reactive, computed, onMounted } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import EditButton from '@/Components/Buttons/EditButton.vue';
    import Modal from '@/Components/Modal.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import RemoveButton from '@/Components/Buttons/RemoveButton.vue';
    import PrintButton from '@/Components/Buttons/PrintButton.vue';
    import AddButton from '@/Components/Buttons/AddButton.vue';
    import ModifyButton from '@/Components/Buttons/ModifyButton.vue';
    import Swal from 'sweetalert2';
    
    const page = usePage();
    const isLoading = ref(false);
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
        hasExpiry: '',
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
        hasExpiry: '',
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
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 5 }, (_, i) => currentYear - i);
    }

    const submitForm = (url, data) => {
        isLoading.value = true;
        Inertia.post(url, data, {
            onSuccess: () => {
                closeModal();
                isLoading.value = false;
            },
            onError: (errors) => {
                isLoading.value = false;
                console.error(`Form submission failed for ${url}`, errors);
            },
        });
    };

    const submit = () => submitForm('products/save', create);
    const submitEdit = () => submitForm('products/update', edit);
    const submitModify = () => submitForm('products/move-and-modify', edit);
    const submitDeactivate = () => submitForm('products/deactivate', edit);

    
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);
    onMounted(() => {
        if (message.value) {
            Swal.fire({
                title: 'Success',
                text: message.value,
                icon: 'success',
                confirmButtonText: 'OK',
            });
        }

        if (errMessage.value) {
            Swal.fire({
                title: 'Error',
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
                    <li class="flex flex-col lg:flex-row">
                        <AddButton @click="showModal('add')" class="mx-1">
                            <span class="mr-2">New Product</span>
                        </AddButton>
                        <PrintButton :href="route('generatePdf.ProductActiveList')" target="_blank" class="mx-1">
                            <span class="mr-2">Print List</span>
                        </PrintButton>
                    </li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-end">
                            <div class="flex mb:flex-column">
                                
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
                                        <span v-if="product.expiry == 'Yes'" class="inline-flex items-center p-1 text-xs rounded bg-red-100 text-red-800">
                                            Expiry
                                        </span>
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Creat New Product</h3>
                            <p class="text-sm text-gray-500"> Enter the details for the new Product you wish to add.</p>
                            <div class="mt-10">
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Choose Category | Item Class</p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.selectedCategory" @change="onCategoryChange(create)" name="category" id="category" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose the category applicable to the product</option>
                                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <label for="category" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category</label>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group" v-if="filteredItems.length">
                                        <select v-model="create.itemId" name="item" id="item" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose an item applicable to the product</option>
                                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                                {{ item.name }}
                                            </option>
                                        </select>
                                        <label for="item" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Class</label>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-1">
                                        <textarea v-model="create.prodDesc" type="text" name="prodDesc" id="prodDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required> </textarea>
                                        <label for="prodDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                    </div>
                                    <div class="grid lg:grid-cols-2 lg:gap-6 mt-3">
                                        <div class="relative z-0 w-full group">
                                            <input v-model="create.prodPrice" type="number" name="prodPrice" id="prodPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                            <label for="prodPrice" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                        </div>
                                        <div class="relative z-0 w-full group">
                                            <select v-model="create.prodUnit" name="prodUnit" id="prodUnit" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                                <option disabled selected value="">Select Unit Of Measure</option>
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
                                            <label for="prodUnit" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                        </div>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.prodRemarks" name="prodRemarks" id="prodRemarks" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected>Select the Product Year</option>
                                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                        </select>
                                        <label for="prodRemarks" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Year</label>
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="create.prodOldCode" type="number" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=""/>
                                        <label for="prodOldCode" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Old Stock Number</label>
                                    </div>
                                    <input type="hidden" v-model="create.createdBy">
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="create.hasExpiry" name="hasExpiry" id="hasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer">
                                            <option value="" disabled selected>Is Product has expiration?</option>
                                            <option :value="1">Yes</option>
                                            <option :value="2">No</option>
                                        </select>
                                        <label for="hasExpiry" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                    </div>
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline"> Edit Product</h3>
                            <p class="text-sm text-gray-500"> Edit the details of the product you wish to modify.</p>
                            <div class="mt-2">
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e] mb-2"> Product Information</p>
                                    <input type="hidden" v-model="edit.prodId">
                                    <div class="relative z-0 w-full mb-5 group">
                                        <textarea v-model="edit.prodDesc" type="text" name="editProdDesc" id="editProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required></textarea>
                                        <label for="editProdDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="edit.prodPrice" type="number" name="editProdPrice" id="editProdPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required/>
                                        <label for="editProdPrice" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                    </div>
                                    <input type="hidden" v-model="edit.updatedBy">
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.hasExpiry" name="editHasExpiry" id="editHasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer">
                                            <option value="" disabled selected>Is Product has expiration?</option>
                                            <option :value="1">Yes</option>
                                            <option :value="2">No</option>
                                        </select>
                                        <label for="editHasExpiry" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                    </div>
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
                            <h3 class="text-lg leading-6 font-medium text-[#86591e]" id="modal-headline">Modify and Move Product</h3>
                            <p class="text-sm text-gray-500"> Enter the details of the Product you wish to modify and move.</p>
                            <div class="mt-2">
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Choose Category | Item Class</p>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.selectedCategory" @change="onCategoryChange(edit)" name="modifyCategory" id="modifyCategory" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose the category applicable to the product</option>
                                            <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <label for="modifyCategory" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Category</label>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group" v-if="filteredItems.length">
                                        <select v-model="edit.itemId" name="modifyItem" id="modifyItem" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected class="pl-5">Please choose an item applicable to the product</option>
                                            <option v-for="item in filteredItems" :key="item.id" :value="item.id">
                                                {{ item.name }}
                                            </option>
                                        </select>
                                        <label for="modifyItem" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Item Class</label>
                                    </div>
                                </div>
                                <div class="mt-5">
                                    <p class="text-sm text-[#86591e]"> Product Information</p>
                                    <div class="relative z-0 w-full group my-1">
                                        <textarea v-model="edit.prodDesc" type="text" name="modifyProdDesc" id="modifyProdDesc" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder="" required> </textarea>
                                        <label for="modifyProdDesc" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Description</label>
                                    </div>
                                    <div class="grid lg:grid-cols-2 lg:gap-6 mt-3">
                                        <div class="relative z-0 w-full group">
                                            <input v-model="edit.prodPrice" type="number" name="modifyProdPrice" id="modifyProdPrice" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                            <label for="modifyProdPrice" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit Price</label>
                                        </div>
                                        <div class="relative z-0 w-full group">
                                            <select v-model="edit.prodUnit" name="modifyProdUnit" id="modifyProdUnit" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                                <option disabled selected value="">Select Unit Of Measure</option>
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
                                            <label for="modifyProdUnit" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Unit of Measure</label>
                                        </div>
                                    </div>
                                    <div class="relative z-0 w-full my-3 group">
                                        <select v-model="edit.prodRemarks" name="modifyProdRemarks" id="modifyProdRemarks" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" required>
                                            <option value="" disabled selected>Select the Product Year</option>
                                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                        </select>
                                        <label for="modifyProdRemarks" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Year</label>
                                    </div>
                                    <div class="relative z-0 w-full mb-5 group">
                                        <input v-model="edit.prodOldCode" type="number" name="modifyProdOldCode" id="modifyProdOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=""/>
                                        <label for="modifyProdOldCode" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Old Stock Number</label>
                                    </div>
                                    <div class="mt-5">
                                    <p class="text-sm text-[#86591e]">Product Expiration <span class="text-xs text-[#3b3b3b]">(Optional)</span></p>
                                        <div class="relative z-0 w-full my-3 group">
                                            <select v-model="edit.hasExpiry" name="editHasExpiry" id="editHasExpiry" class="block py-2.5 px-1 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer">
                                                <option value="" disabled selected>Is Product has expiration?</option>
                                                <option :value="1">Yes</option>
                                                <option :value="2">No</option>
                                            </select>
                                            <label for="editHasExpiry" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Has Expiry?</label>
                                        </div>
                                    </div>
                                    <input type="hidden" v-model="edit.updatedBy">
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
 