<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <nav aria-label="breadcrumb" class="font-semibold text-lg"> 
                <ol class="flex space-x-2 leading-tight">
                    <li><a class="after:content-['/'] after:ml-2 text-green-700">Requisition and Issuance</a></li>
                    <li class="after:content-['/'] after:ml-2 text-green-700" aria-current="page">Transactions</li> 
                </ol>
            </nav>
            <div v-if="$page.props.flash.message" class="text-green-600 my-2">
                {{ $page.props.flash.message }}
            </div>
            <div v-else-if="$page.props.flash.error" class="text-red-600 my-2">
                {{ $page.props.flash.error }}
            </div>
        </template>

        <section class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-2">
                        
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
