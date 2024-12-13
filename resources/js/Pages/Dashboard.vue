<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    core: Array
});
</script>

<template>
    <Head title="Dashboard" />
    <div>
    <Sidebar/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-3">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <div class="rounded-lg">
                            <div class="lg:grid lg:grid-cols-4 gap-4 mb-4">
                                <article class="flex items-center lg:m-0 my-2 gap-6 rounded-lg bg-gradient-to-r from-pink-100  to-pink-300 p-6 shadow-sm">
                                    <span class="p-2 rounded-full opacity-30">
                                        <svg class="w-16 h-16 text-pink-800" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7.24 2H5.34C3.15 2 2 3.15 2 5.33V7.23C2 9.41 3.15 10.56 5.33 10.56H7.23C9.41 10.56 10.56 9.41 10.56 7.23V5.33C10.57 3.15 9.42 2 7.24 2Z" fill="currentColor"/>
                                            <path opacity="0.4" d="M18.6695 2H16.7695C14.5895 2 13.4395 3.15 13.4395 5.33V7.23C13.4395 9.41 14.5895 10.56 16.7695 10.56H18.6695C20.8495 10.56 21.9995 9.41 21.9995 7.23V5.33C21.9995 3.15 20.8495 2 18.6695 2Z" fill="#292D32"/>
                                            <path d="M18.6695 13.4302H16.7695C14.5895 13.4302 13.4395 14.5802 13.4395 16.7602V18.6602C13.4395 20.8402 14.5895 21.9902 16.7695 21.9902H18.6695C20.8495 21.9902 21.9995 20.8402 21.9995 18.6602V16.7602C21.9995 14.5802 20.8495 13.4302 18.6695 13.4302Z" fill="currentColor"/>
                                            <path opacity="0.4" d="M7.24 13.4302H5.34C3.15 13.4302 2 14.5802 2 16.7602V18.6602C2 20.8502 3.15 22.0002 5.33 22.0002H7.23C9.41 22.0002 10.56 20.8502 10.56 18.6702V16.7702C10.57 14.5802 9.42 13.4302 7.24 13.4302Z" fill="#292D32"/>
                                        </svg>
                                    </span>

                                    <div class="flex flex-col space-y-1 text-center">
                                        <p class="text-3xl font-semibold text-pink-800">{{ core.category }}</p>
                                        <p class="text-sm text-pink-600">Active</p>
                                    </div>

                                    <div class="ml-auto flex flex-col items-end space-y-1">
                                        <p class="text-3xl text-pink-400">Category</p>
                                    </div>
                                </article>
                                <article class="flex items-center gap-6 lg:m-0 my-2 rounded-lg bg-gradient-to-r from-sky-100  to-sky-300 p-6 shadow-sm">
                                    <span class="p-2 rounded-full opacity-30">
                                        <svg class="w-16 h-16 text-sky-800" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.2505 5.77V9C21.2505 11.1146 20.5427 11.899 18.6312 12.0068C18.3555 12.0223 18.1305 11.7961 18.1305 11.52V10.38C18.1305 7.26 16.7405 5.87 13.6205 5.87H12.4805C12.2043 5.87 11.9781 5.64494 11.9937 5.36924C12.1014 3.45773 12.8859 2.75 15.0005 2.75H18.2305C20.5205 2.75 21.2505 3.48 21.2505 5.77Z" fill="currentColor"/>
                                        <path d="M16.6291 10.3811V13.6211C16.6291 15.9011 15.8991 16.6311 13.6191 16.6311H10.3791C8.09914 16.6311 7.36914 15.9011 7.36914 13.6211V10.3811C7.36914 8.10109 8.09914 7.37109 10.3791 7.37109H13.6191C15.8991 7.37109 16.6291 8.10109 16.6291 10.3811Z" fill="currentColor"/>
                                        <path d="M11.52 18.1305C11.7961 18.1305 12.0223 18.3555 12.0068 18.6312C11.899 20.5427 11.1146 21.2505 9 21.2505H5.77C3.48 21.2505 2.75 20.5205 2.75 18.2305V15.0005C2.75 12.8859 3.45773 12.1014 5.36924 11.9937C5.64494 11.9781 5.87 12.2043 5.87 12.4805V13.6205C5.87 16.7405 7.26 18.1305 10.38 18.1305H11.52Z" fill="currentColor"/>
                                        </svg>
                                    </span>

                                    <div class="flex flex-col space-y-1 text-center">
                                        <p class="text-3xl font-semibold text-sky-800">{{ core.item }}</p>
                                        <p class="text-sm text-sky-600">Active</p>
                                    </div>

                                    <div class="ml-auto flex flex-col items-end space-y-1">
                                        <p class="text-3xl text-sky-400">Item Class</p>
                                    </div>
                                </article>
                                <article class="flex items-center gap-6 lg:m-0 my-2 rounded-lg bg-gradient-to-r from-teal-100  to-teal-300 p-6 shadow-sm">
                                    <span class="p-2 rounded-full opacity-30">
                                        <svg class="w-16 h-16 text-teal-800" viewBox="0 0 16 16" id="meteor-icon-kit__solid-products-s" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7 5H14C15.1046 5 16 5.89543 16 7V14C16 15.1046 15.1046 16 14 16H7C5.89543 16 5 15.1046 5 14V7C5 5.89543 5.89543 5 7 5zM3 11H2C0.89543 11 0 10.1046 0 9V2C0 0.89543 0.89543 0 2 0H9C10.1046 0 11 0.89543 11 2V3H7C4.79086 3 3 4.79086 3 7V11z" fill="currentColor"/>
                                        </svg>
                                    </span>

                                    <div class="flex flex-col space-y-1 text-center">
                                        <p class="text-3xl font-semibold text-teal-800">{{ core.product }}</p>
                                        <p class="text-sm text-teal-600">Active</p>
                                    </div>

                                    <div class="ml-auto flex flex-col items-end space-y-1">
                                        <p class="text-3xl text-teal-400">Products</p>
                                    </div>
                                </article>
                                <article class="flex items-center gap-6 lg:m-0 my-2 rounded-lg bg-gradient-to-r from-rose-100  to-rose-300 p-6 shadow-sm">
                                    <span class="p-2 rounded-full opacity-30">
                                        <svg class="w-16 h-16 text-pink-800" fill="currentColor" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 544.527 544.527" xml:space="preserve">
                                        <g>
                                            <g>
                                                <polygon points="286.334,234.111 148.331,234.111 152.68,286.132 281.986,286.132 		"/>
                                                <polygon points="144.993,384.052 138.331,301.432 22.951,301.432 44.117,384.052 		"/>
                                                <polygon points="146.278,399.352 48.199,399.352 60.916,448.312 150.284,448.312 		"/>
                                                <path d="M424.71,253.111c16.849-16.87,33.694-34.299,50.542-49.6h43.568c27.54,0,27.54-48.96,0-48.96h-52.381
                                                    c-5.125,0-9.36,1.799-12.741,4.045c-1.983,1.077-3.927,2.708-5.777,4.56c-21.105,21.132-42.21,42.402-63.318,63.535
                                                    c-2.312,2.316-3.95,4.36-5.067,7.42h-77.567l-4.349,52.021h118.178L424.71,253.111z"/>
                                                <polygon points="18.869,286.132 137.046,286.132 132.698,234.111 5.053,234.111 		"/>
                                                <polygon points="268.748,448.312 272.754,399.352 161.912,399.352 165.914,448.312 		"/>
                                                <polygon points="411.715,301.432 296.334,301.432 289.67,384.052 390.549,384.052 		"/>
                                                <polygon points="386.467,399.352 288.385,399.352 284.382,448.312 373.749,448.312 		"/>
                                                <polygon points="280.701,301.432 153.965,301.432 160.626,384.052 274.036,384.052 		"/>
                                                <circle cx="108.117" cy="503.339" r="41.188"/>
                                                <circle cx="326.546" cy="503.339" r="41.188"/>
                                                <path d="M125.351,171.366l3.418-29.707c121.029,13.926,163.694,52.405,163.694,52.405S290.615,46.846,141.651,29.707L145.069,0
                                                    L5.506,70.759L125.351,171.366z"/>
                                            </g>
                                        </g>
                                        </svg>
                                    </span>

                                    <div class="flex flex-col space-y-1 text-center">
                                        <p class="text-3xl font-semibold text-rose-800">{{ core.redorder }}</p>
                                        <p class="text-sm text-rose-600">Products</p>
                                    </div>

                                    <div class="ml-auto flex flex-col items-end space-y-1">
                                        <p class="text-3xl text-rose-400">Out Of Stock</p>
                                    </div>
                                </article>
                            </div>
                            <div class="lg:grid lg:grid-cols-3 gap-4">
                                <div class="col-span-2 flex items-center justify-center rounded bg-gray-200 h-auto">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-200 h-auto">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                            </div>
                            <!-- <div class="flex items-center justify-center h-48 mb-4 rounded bg-gray-50 dark:bg-gray-800">
                                <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center justify-center h-48 mb-4 rounded bg-gray-50 dark:bg-gray-800">
                                <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                                <div class="flex items-center justify-center rounded bg-gray-50 h-28 dark:bg-gray-800">
                                    <p class="text-2xl text-gray-400 dark:text-gray-500">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/>
                                    </svg>
                                    </p>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    </div>
</template>
