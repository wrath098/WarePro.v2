<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
</script>

<template>
    <Head title="PPMP" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li class="text-green-700" aria-current="page">Purchase Request</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex flex-col md:flex-row items-start justify-center">
                        <div class="mx-2 w-full md:w-3/12 bg-white p-4 rounded-md shadow">
                            <form @submit.prevent="submit" class="space-y-5">
                                <div>
                                    <label for="ppmpType" class="mb-1 block text-base font-medium text-[#07074D]">
                                        Create Purchase Request:
                                    </label>
                                    <select id="ppmpType" class="pl-3 border border-gray-300 rounded-md w-full focus:outline-none focus:ring focus:border-indigo-500 text-gray-800" required>
                                        <option value="" selected>Please choose PPMP Type</option>
                                        <option value="individual">Individual</option>
                                        <option value="contingency">Contingency</option>
                                    </select>
                                </div>
                                <div>
                                    <button
                                        class="hover:shadow-form w-full rounded-md bg-indigo-500 hover:bg-indigo-700 py-3 text-center text-base font-semibold text-white outline-none">
                                        Create PPMP
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="mx-2 w-full md:w-9/12 bg-white p-4 rounded-md shadow mt-5 md:mt-0">
                            <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="relative overflow-x-auto md:overflow-hidden">
                                    <DataTable class="w-full text-gray-900 display">
                                        <thead class="text-sm text-gray-100 uppercase bg-indigo-600">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/6">No#</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Office Code</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP No.</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">PPMP Type</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Price Adjustment</th>
                                                <th scope="col" class="px-6 py-3 w-1/6">Action/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- <tr v-for="(ppmp, index) in officePpmps" :key="ppmp.id">
                                                <td class="px-6 py-3">{{ index + 1 }}</td>
                                                <td class="px-6 py-3">{{ ppmp.officeCode }}</td>
                                                <td class="px-6 py-3">{{ ppmp.ppmpCode }}</td>
                                                <td class="px-6 py-3">{{ ppmp.ppmpType }}</td>
                                                <td class="px-6 py-3">{{ ppmp.basedPrice }}</td>
                                                <td class="px-6 py-3">
                                                    <ViewButton :href="route('indiv.ppmp.show', { ppmpTransaction: ppmp.id })" tooltip="View"/>
                                                    <RemoveButton @click="openDropPpmpModal(ppmp)" tooltip="Trash"/>
                                                </td>
                                            </tr> -->
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