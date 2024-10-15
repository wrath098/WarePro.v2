<script setup>
    import { Head } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    
    const props = defineProps({
        ppmpTransaction: Object,
        ppmp: Object,
    });
</script>

<template>
    <Head title="Office" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg leading-3"> 
                <ol class="flex space-x-2">
                    <li><a class="after:content-['/'] after:ml-2 text-gray-600 hover:text-green-700">Project Procurement and Manangement Plan</a></li>
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">{{ props.ppmp.type }}</li> 
                    <li class="text-green-700" aria-current="page">{{ props.ppmp.status }}</li> 
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
                <div class="bg-white p-2 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="relative overflow-x-auto">
                        <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                            <!-- 
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 rtl:inset-r-0 rtl:right-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-indigo-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="search" type="text" id="search" class="block p-2 ps-10 text-sm text-gray-900 border border-indigo-600 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 " placeholder="Search for Office Code, Office Name">
                            </div> -->
                        </div>
                        <table class="w-full text-left rtl:text-right text-gray-900 ">
                            <thead class="text-sm text-center text-gray-100 uppercase bg-indigo-600">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        No#
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Transaction No.
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Office Code
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-3/12">
                                        Office Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        For CY
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-1/12">
                                        Created / Updated
                                    </th>
                                    <th scope="col" class="px-6 py-3 w-2/12">
                                        Action/s
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(transaction, index) in ppmpTransaction" :key="transaction.id" class="odd:bg-white even:bg-gray-50 border-b text-base">
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  index+1 }}
                                    </td>
                                    <td scope="row" class="py-2 text-center text-sm">
                                        {{  transaction.ppmp_code }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ transaction.requestee.office_code  }}
                                    </td>
                                    <td class="py-2">
                                        {{ transaction.requestee.office_name  }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ transaction.ppmp_remarks  }}
                                    </td>
                                    <td class="py-2 text-center">
                                        {{ transaction.updater.name  }}
                                    </td>
                                    <!-- <td class="py-2 text-center">
                                        <EditButton @click="openEditModal(office)" tooltip="Edit"/>
                                        <RemoveButton @click="openDeactivateModal(office)" tooltip="Remove"/>
                                    </td> -->
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- <div class="flex justify-center p-5">
                        <Pagination :links="offices.links" />
                    </div> -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
 