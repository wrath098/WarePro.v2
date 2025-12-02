<script setup>
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import { ref, computed, reactive } from 'vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Modal from '@/Components/Modal.vue';
    import SuccessButton from '@/Components/Buttons/SuccessButton.vue';
    import DangerButton from '@/Components/Buttons/DangerButton.vue';
    import Swal from 'sweetalert2';

    const page = usePage();
    const isLoading = ref(false);
    const isSearchLoading = ref(false);
    const message = computed(() => page.props.flash.message);
    const errMessage = computed(() => page.props.flash.error);

    const props = defineProps({
        user: Object,
    });

    const modalState = ref(null);
    const closeModal = () => { modalState.value = null; }
    const isProductModalOpen = computed(() => modalState.value === 'add');

    const initialProductState = {
        id: null,
        image_path: '',
        newNo: '',
        itemName: '',
        unit: '',
        className: '',
        desc: '',
        price: 0,
        firstQty: 0,
        secondQty: 0
    };

    const requestedProduct = reactive({ ...initialProductState });

    const resetRequestedProduct = () => {
        Object.assign(requestedProduct, initialProductState);
    };

    const openProductModal = (product) => 
    {
        requestedProduct.id = product.id;
        requestedProduct.newNo = product.newNo;
        requestedProduct.desc = product.desc;
        requestedProduct.className = product.className;
        requestedProduct.itemName = product.itemName;
        requestedProduct.price = product.price;
        requestedProduct.unit = product.unit;
        requestedProduct.image_path = product.image_path;

        modalState.value = 'add';
    }

    const create = useForm({
        ppmpType: 'Office',
        ppmpYear: '',
        officeName: props.user.office.office_name,
        officeId: props.user.office.id,
        user: props.user,
    });

    const productCatalog = ref([]);
    const filterOfficeNoPpmp = async () => {
        if (!create.ppmpYear) return;

        productCatalog.value = [];

        isSearchLoading.value = true;
        try {
            const response = await axios.get('validate-office', {
                params: create
            });

            productCatalog.value = response.data?.data;
        } catch (error) {
            console.error('Error fetching office data:', error);
        } finally {
            isSearchLoading.value = false;
        }
    };

    const years = generateYears();
    function generateYears() {
        const currentYear = new Date().getFullYear() + 2;
        return Array.from({ length: 3 }, (_, i) => currentYear - i);
    }

    const selectedItems = ref([]);
    const selected = () => {
        if (!selectedItems.value.some(i => i.id === requestedProduct.id)) {
            selectedItems.value.push({ ...requestedProduct });
            selectedItems.value.sort(sortByNewNo);
        }

        productCatalog.value = productCatalog.value.filter(
            item => item.id !== requestedProduct.id
        );

        resetRequestedProduct();
        closeModal();
    }

    const deleteRow = (product) => {
        if (!productCatalog.value.some(i => i.id === product.id)) {
            productCatalog.value.push(product);
        }

        productCatalog.value.sort(sortByNewNo);

        const index = selectedItems.value.findIndex(
            (item) => item.id === product.id
        );

        if (index !== -1) {
            selectedItems.value.splice(index, 1);
        }
    }

    const formatDecimal = (value) => {
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    };

    function sortByNewNo(a, b) {
        const aParts = a.newNo.split('-').map(Number);
        const bParts = b.newNo.split('-').map(Number);
        for (let i = 0; i < Math.max(aParts.length, bParts.length); i++) {
            const diff = (aParts[i] || 0) - (bParts[i] || 0);
            if (diff !== 0) return diff;
        }
        return 0;
    }

    const submit = () => {
        isLoading.value = true;

        create.transform(data => ({
            ...data,
            requestProducts: selectedItems.value
        })).post(route('store-office-ppmp'), {
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
                    Swal.fire({
                        title: 'Failed',
                        text: errMessage.value,
                        icon: 'error',
                    });
                } else {
                    create.reset();
                    isLoading.value = false;
                    Swal.fire({
                        title: 'Success',
                        text: message.value,
                        icon: 'success',
                    }).then(() => {
                        closeModal();
                        router.visit(route('office.ppmp.creation'), {
                            preserveScroll: true,
                            preserveState: false,
                        });
                    });
                }
            }
        })
    };
</script>

<template>
    <Head title="PPMP - Create" />
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
                    <li aria-current="page">
                        <div class="flex items-center">
                            <span class="mx-2.5 text-gray-800 ">/</span>
                            <a href="#" class="ml-1 inline-flex text-sm font-medium text-gray-800 hover:underline md:ml-2">
                                Create
                            </a>
                        </div>
                    </li>
                </ol>
            </nav>
        </template>
        <div class="w-full my-4 bg-zinc-300 shadow rounded-md">
            <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                <h3 class="font-bold text-lg leading-6 text-zinc-300">
                    Project Procurement Management Plan (PPMP)
                </h3>
                <p class="text-sm text-zinc-300">
                    The PPMP is prepared to identify and outline all materials, services, and equipment needed for the project, including their estimated costs and procurement schedules. It ensures that all required resources are acquired on time and in compliance with project guidelines and budgeting requirements, supporting the smooth and timely implementation of the project.
                </p>
            </div>
            <div class="p-4 shadow-md sm:rounded-lg">
                <div class="mx-4 lg:mx-0">
                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-3 lg:gap-4">
                        <div class="relative z-0 w-full my-3 group">
                            <select v-model="create.ppmpYear" @change="filterOfficeNoPpmp" name="ppmpYear" id="ppmpYear" class="block py-2.5 px-2 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" required>
                                <option value="" disabled selected>Choose the PPMP year you want to view or create.</option>
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                            <label for="ppmpYear" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Calendar Year</label>
                        </div>
                        <div v-if="create.ppmpYear" class="relative z-0 w-full my-3 group">
                            <input v-model="create.officeName" type="text" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" readonly/>
                            <label for="prodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Office Name</label>
                        </div>
                        <div v-if="create.ppmpYear" class="relative z-0 w-full my-3 group">
                            <input v-model="create.ppmpType" type="text" name="prodOldCode" id="prodOldCode" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" readonly/>
                            <label for="prodOldCode" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">PPMP Type</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="isSearchLoading" class="flex justify-center items-center">
            <div class="text-center p-6">
                <div class="w-24 h-24 border-4 border-dashed rounded-full animate-spin border-[#380252] mx-auto"></div>
                <h2 class="text-zinc-900 mt-4">Loading...</h2>
                <p class="text-zinc-600 ">Waiting is a virtue.</p>
            </div>
        </div>
        <div v-else-if="productCatalog.length > 0" class="my-4 w-screen-2xl mb-8">
            <div class="overflow-hidden">
                <div class="sm:mx-4 lg:mx-0">
                    <div class="flex flex-col sm:flex-row md:gap-4 lg:gap-4">
                        <div class="rounded-md w-full sm:w-3/12">
                            <div class="bg-zinc-600 px-4 py-5 sm:px-6 rounded-t-lg">
                                <h3 class="font-bold text-lg leading-6 text-zinc-300">
                                    Select a Product
                                </h3>
                                <p class="text-sm text-zinc-300">
                                    Choose an item from the list below.
                                </p>
                            </div>
                            <div class="h-[calc(60vh-8rem)] overflow-y-auto bg-zinc-300 b-zinc-400">
                                <TransitionGroup name="list" tag="div">
                                    <div v-for="product in productCatalog" :key="product.id" class="flex flex-col w-full p-2">
                                        <button @click="openProductModal(product)" class="flex flex-col sm:flex-row border-b-2 border-r-2 border-zinc-400 py-1 px-1 w-full text-center sm:text-left rounded-lg bg-zinc-200 hover:bg-zinc-100 transition">
                                            <img :src="product.image_path" :alt="product.itemName" class="flex-shrink-0 m-2 w-16 h-16 rounded-md bg-zinc-100 self-center">
                                            <div class="flex flex-col py-2 pr-2">
                                                <h4 class="text-sm font-semibold">{{ product.itemName }} <span class="text-xs font-hairline">#{{ product.newNo }}</span></h4>
                                                <p class="text-xs font-hairline">{{ product.desc }}</p>
                                            </div>
                                        </button>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </div>
                            
                        <div class="w-full sm:w-9/12 p-2 rounded-md mt-5 lg:mt-0">
                            <div class="p-2 overflow-hidden">
                                <div v-if="selectedItems.length === 0" class="relative overflow-x-auto">
                                    <main class="flex flex-1 w-full flex-col items-center justify-center text-center px-4 sm:mt-18 mt-10"> 
                                        <p class="border rounded-2xl py-1 px-4 text-slate-500 text-sm mb-5 hover:scale-105 transition duration-300 ease-in-out">Explore the product list and select the items you wish to include with a simple click.</p>
                                        <h1 class="mx-auto max-w-4xl font-display text-5xl font-bold tracking-normal text-slate-900 sm:text-7xl">
                                            Create Your
                                            <span class="relative whitespace-nowrap text-purple-400">
                                                <svg aria-hidden="true" viewBox="0 0 418 42" class="absolute top-2/3 left-0 h-[0.58em] w-full fill-purple-300/70" preserveAspectRatio="none"><path d="M203.371.916c-26.013-2.078-76.686 1.963-124.73 9.946L67.3 12.749C35.421 18.062 18.2 21.766 6.004 25.934 1.244 27.561.828 27.778.874 28.61c.07 1.214.828 1.121 9.595-1.176 9.072-2.377 17.15-3.92 39.246-7.496C123.565 7.986 157.869 4.492 195.942 5.046c7.461.108 19.25 1.696 19.17 2.582-.107 1.183-7.874 4.31-25.75 10.366-21.992 7.45-35.43 12.534-36.701 13.884-2.173 2.308-.202 4.407 4.442 4.734 2.654.187 3.263.157 15.593-.78 35.401-2.686 57.944-3.488 88.365-3.143 46.327.526 75.721 2.23 130.788 7.584 19.787 1.924 20.814 1.98 24.557 1.332l.066-.011c1.201-.203 1.53-1.825.399-2.335-2.911-1.31-4.893-1.604-22.048-3.261-57.509-5.556-87.871-7.36-132.059-7.842-23.239-.254-33.617-.116-50.627.674-11.629.54-42.371 2.494-46.696 2.967-2.359.259 8.133-3.625 26.504-9.81 23.239-7.825 27.934-10.149 28.304-14.005.417-4.348-3.529-6-16.878-7.066Z"></path></svg>
                                                <span class="relative">PPMP</span>
                                            </span>
                                        </h1>
                                        <p class="mx-auto mt-12 max-w-xl text-lg text-slate-700 leading-7">
                                            This approach incorporates light technological enhancements into the existing workflow, enabling the gradual transition from manual processes to automated systems, improving accuracy, speed, and overall productivity.
                                        </p>
                                    </main>
                                </div>
                                <div v-else>
                                    <div class="relative overflow-x-auto">
                                        <table class="w-full text-gray-900 display border-2 border-[#7393dc]">
                                            <thead class="text-sm uppercase bg-[#d8d8f6] text-center text-[#03244d]">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 w-2/12 border-2 border-[#7393dc]">
                                                        Stock No. 
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-4/12 border-2 border-[#7393dc]">
                                                        Description 
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]">
                                                        Unit Of Measure
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-2/12 border-2 border-[#7393dc]">
                                                        Price
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]">
                                                        First Semester <br> (January)
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]">
                                                        Second Semester <br> (May)
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 w-1/12 border-2 border-[#7393dc]"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-xs">
                                                <tr v-for="particular in selectedItems" :key="particular.id" class="bg-zinc-100 border-b hover:bg-zinc-400">
                                                    <td class="px-4 py-2 text-center border-r-2 border-[#7393dc]">{{ particular.newNo }}</td>
                                                    <td class="px-4 py-2 text-left border-r-2 border-[#7393dc]">{{ particular.desc }}</td>
                                                    <td class="px-4 py-2 text-center border-r-2 border-[#7393dc]">{{ particular.unit }}</td>
                                                    <td class="px-4 py-2 text-right border-r-2 border-[#7393dc]">{{ formatDecimal(particular.price) }}</td>
                                                    <td class="px-4 py-2 text-center border-r-2 border-[#7393dc]">{{ particular.firstQty }}</td>
                                                    <td class="px-4 py-2 text-center border-r-2 border-[#7393dc]">{{ particular.secondQty }}</td>
                                                    <td class="px-4 py-2 text-center border-r-2 border-[#7393dc]">
                                                        <button @click="deleteRow(particular)" class="px-2 py-1 rounded">
                                                            <svg class="w-5 h-5 text-rose-500 hover:text-rose-900 transition duration-75" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 1216 1312">
                                                                <path fill="currentColor" d="M1202 1066q0 40-28 68l-136 136q-28 28-68 28t-68-28L608 976l-294 294q-28 28-68 28t-68-28L42 1134q-28-28-28-68t28-68l294-294L42 410q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 294l294-294q28-28 68-28t68 28l136 136q28 28 28 68t-28 68L880 704l294 294q28 28 28 68"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="w-full">
                                        <form @submit.prevent="submit" class="flex justify-center my-5">
                                            <button type="submit" class="w-1/2 inline-flex items-center justify-center rounded cursor-pointer bg-[#6427f1] px-6 py-3 font-semibold text-white transition [box-shadow:rgb(171,_196,245)-8px_8px] hover:[box-shadow:rgb(171,_196,_245)0px_0px]">
                                                <span class="mr-2">Submit Form</span>
                                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 15 16">
                                                    <path fill="currentColor" d="M12.49 7.14L3.44 2.27c-.76-.41-1.64.3-1.4 1.13l1.24 4.34c.05.18.05.36 0 .54l-1.24 4.34c-.24.83.64 1.54 1.4 1.13l9.05-4.87a.98.98 0 0 0 0-1.72Z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="create.ppmpYear && !isSearchLoading && productCatalog.length == 0">
            <main class="flex flex-1 w-full flex-col items-center justify-center text-center px-4 sm:mt-18 m-10"> 
                <h1 class="mx-auto max-w-4xl font-display text-5xl font-bold tracking-normal text-slate-900 sm:text-7xl">
                    PPMP Already
                    <span class="relative whitespace-nowrap text-purple-400">
                        <svg aria-hidden="true" viewBox="0 0 418 42" class="absolute top-2/3 left-0 h-[0.58em] w-full fill-purple-300/70" preserveAspectRatio="none"><path d="M203.371.916c-26.013-2.078-76.686 1.963-124.73 9.946L67.3 12.749C35.421 18.062 18.2 21.766 6.004 25.934 1.244 27.561.828 27.778.874 28.61c.07 1.214.828 1.121 9.595-1.176 9.072-2.377 17.15-3.92 39.246-7.496C123.565 7.986 157.869 4.492 195.942 5.046c7.461.108 19.25 1.696 19.17 2.582-.107 1.183-7.874 4.31-25.75 10.366-21.992 7.45-35.43 12.534-36.701 13.884-2.173 2.308-.202 4.407 4.442 4.734 2.654.187 3.263.157 15.593-.78 35.401-2.686 57.944-3.488 88.365-3.143 46.327.526 75.721 2.23 130.788 7.584 19.787 1.924 20.814 1.98 24.557 1.332l.066-.011c1.201-.203 1.53-1.825.399-2.335-2.911-1.31-4.893-1.604-22.048-3.261-57.509-5.556-87.871-7.36-132.059-7.842-23.239-.254-33.617-.116-50.627.674-11.629.54-42.371 2.494-46.696 2.967-2.359.259 8.133-3.625 26.504-9.81 23.239-7.825 27.934-10.149 28.304-14.005.417-4.348-3.529-6-16.878-7.066Z"></path></svg>
                        <span class="relative">Exist!</span>
                    </span>
                </h1>
                <p class="mx-auto mt-12 max-w-2xl text-lg text-slate-700 leading-7">
                    A PPMP for calendar year <b> {{ create.ppmpYear }} </b> has already been submitted for your office.
                </p>
            </main>
        </div>
        <Modal :show="isProductModalOpen" @close="closeModal"> 
            <div class="bg-zinc-300 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-zinc-200 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-8 w-8 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M15 4H9v16h6V4Zm2 16h3a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3v16ZM4 4h3v16H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-[#1a0037]" id="modal-headline"> Select Item</h3>
                        <p class="text-sm text-zinc-700"> Enter the desired quantity to request.</p>
                        <div class="my-3">
                            <p class="text-sm font-semibold text-[#1a0037]"> Item Information: </p>
                            <div class="rounded-lg mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 bg-zinc-700">
                                <img :src="requestedProduct.image_path" alt="" class="rounded-l">
                                <div class="p-2 text-zinc-200">
                                    <p class="font-semibold">#{{ requestedProduct.newNo }}</p>
                                    <p class="text-sm">{{ requestedProduct.itemName }} ({{ requestedProduct.unit }})</p>
                                    <p class="text-sm">{{ requestedProduct.className}}</p>

                                    <p class="my-3 text-sm">{{ requestedProduct.desc}}</p>
                                    <p class="font-bold">₱ {{ requestedProduct.price}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="my-5">
                            <p class="text-sm font-semibold text-[#1a0037]">Enter the requested item quantity below: </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="requestedProduct.firstQty" type="number" name="firstQty" id="firstQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder="" required/>
                                    <label for="firstQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">January (1st Sem)</label>
                                </div>
                                <div class="relative z-0 w-full group my-2">
                                    <input v-model="requestedProduct.secondQty" type="number" name="secondQty" id="secondQty" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=""/>
                                    <label for="secondQty" class="font-semibold text-zinc-700 absolute text-sm duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">May (2nd Sem)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-zinc-400 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <SuccessButton @click="selected()" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
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
        </Modal>
    </AuthenticatedLayout>
</template>
 
<style scoped>
    .upload-area {
        border: 2px dashed #007BFF;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }
    .upload-area:hover {
        border-color: #08396d;
    }

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

    :deep(table.dataTable tbody > tr > td:nth-child(1)) {
        text-align: left !important;
    }

    .list-enter-active,
    .list-leave-active {
        transition: all 0.5s ease;
    }
    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }
</style>