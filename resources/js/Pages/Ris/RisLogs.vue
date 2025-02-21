<script setup>
    import { Head, usePage } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import Print from '@/Components/Buttons/Print.vue';
    import ViewButton from '@/Components/Buttons/ViewButton.vue';
    import { computed, onMounted } from 'vue';
    import Swal from 'sweetalert2';

    const page = usePage();

    const props = defineProps({
        transactions: Object,
    });

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
                title: 'Failed',
                text: errMessage.value,
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Purchase Request</a></li>
                    <li><a class="after:content-['/'] after:ml-2 text-[#86591e]">Procurement Basis</a></li>
                </ol>
            </nav>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="w-full bg-white h-auto mb-2 rounded-md">
                    <div class="relative isolate flex overflow-hidden bg-indigo-600 px-6 py-2.5 rounded-md">
                        <div class="flex flex-wrap">
                            <p class="text-base text-gray-100">
                                <strong class="font-semibold">Product Information and Date of Duration</strong>
                                <svg viewBox="0 0 2 2" class="mx-2 inline size-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                                <span class="text-sm">Please enter the required data in the input field.</span>
                            </p>
                        </div>
                    </div>
                    <form>
                        <div class="grid lg:grid-cols-4 lg:gap-6 my-5 mx-3">
                            <div class="relative z-0 w-full group my-5">
                                <input type="text" name="fetchProduct" id="fetchProduct" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="fetchProduct" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">Enter Product</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group">
                                <input type="date" name="from" id="from" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="from" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">From (Start Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group">
                                <input type="date" name="to" id="to" class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" " required />
                                <label for="to" class="peer-focus:font-medium absolute text-sm text-gray-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6">To (End Date)</label>
                            </div>

                            <div class="relative z-0 w-full my-5 group text-center">
                                <button type="submit" class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                    Filter
                                </button>
                                <a 
                                    :href="route('generatePdf.StockCard', { productDetails: searchProductInfo })" 
                                    target="_blank"
                                    class="inline-block w-auto text-center mx-1 min-w-[125px] px-6 py-3 text-white transition-all bg-gray-600 rounded-md shadow-xl sm:w-auto hover:bg-gray-900 hover:text-white shadow-neutral-300 hover:shadow-2xl hover:shadow-neutral-400 hover:-tranneutral-y-px">
                                    Print
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr class="text-center">
                                                <th scope="col" class="px-6 py-3 w-1/12">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">RIS No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Office (Requestee)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Stock No.</th>
                                                <th scope="col" class="px-6 py-3 w-2/12">Description</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Unit of Measure</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Requested (Qty)</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued To</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Issued By</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Date Released</th>
                                                <th scope="col" class="px-6 py-3 w-1/12">Attachment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(transaction, index) in transactions" :key="transaction.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ transaction.risNo }}</td>
                                                <td class="px-6 py-3">{{ transaction.officeRequestee }}</td>
                                                <td class="px-6 py-3">{{ transaction.stockNo }}</td>
                                                <td class="px-6 py-3">{{ transaction.prodDesc }}</td>
                                                <td class="px-6 py-3">{{ transaction.unit }}</td>
                                                <td class="px-6 py-3">{{ transaction.qty }}</td>
                                                <td class="px-6 py-3">{{ transaction.issuedTo }}</td>
                                                <td class="px-6 py-3">{{ transaction.releasedBy }}</td>
                                                <td class="px-6 py-3">{{ transaction.dateReleased }}</td>
                                                <td v-if="transaction.attachment" class="px-6 py-3">
                                                    <ViewButton :href="route('ris.show.attachment', {transactionId : transaction.id})" tooltip="View" target="_blank"/>
                                                </td>
                                                <td v-else class="italic text-gray-400">No Attachment</td>
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
    </AuthenticatedLayout>
    </div>
</template>
