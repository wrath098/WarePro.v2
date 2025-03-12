<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Sidebar from './Sidebar.vue';

const showingNavigationDropdown = ref(false);
const showingAppSideBar = ref(true);
</script>

<template>
    <div>
        <nav class="flex items-center fixed w-screen h-16 z-40 top-0 left-0 bg-white transition px-8">
            <div class="w-80 flex items-center">
                <button class="inline-flex justify-center place-items-center rounded-lg hover:bg-indigo-200 mr-2 w-10 h-10 transition cursor-pointer lg:hidden"
                        @click="showingAppSideBar = !showingAppSideBar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M5 12h14M5 7h14"/>
                    </svg>
                </button>
                <a :href="route('dashboard')" class="inline-flex items-center text-2xl rounded text-gray-700 font-medium gap-2">
                    <svg class="w-8 text-indigo-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 32 32">
                        <path fill="currentColor" d="m22.504 17.636l-6-3.5a1 1 0 0 0-1.008 0l-6 3.5A1 1 0 0 0 9 18.5v7a1 1 0 0 0 .496.864l6 3.5a1 1 0 0 0 1.008 0l6-3.5A1 1 0 0 0 23 25.5v-7a1 1 0 0 0-.496-.864ZM21 23.834l-2.532-1.519c.013-.104.032-.207.032-.315a2.502 2.502 0 0 0-1.5-2.288v-2.97l4 2.332v4.76Zm-6-7.093v2.97A2.502 2.502 0 0 0 13.5 22c0 .108.019.21.032.315L11 23.834v-4.76l4-2.333Zm1 11.101l-3.952-2.305l2.507-1.504c.41.291.906.467 1.445.467s1.036-.176 1.445-.467l2.507 1.504L16 27.842Z"/>
                        <path fill="currentColor" d="M24.8 9.14C23.93 5.02 20.28 2 16 2S8.07 5.02 7.2 9.14C4.23 9.74 2 12.4 2 15.5c0 3.07 2.14 5.63 5 6.31V19.7c-1.74-.62-3-2.24-3-4.2c0-2.33 1.82-4.31 4.14-4.49l.82-.06l.1-.81C9.49 6.64 12.47 4 16 4s6.51 2.64 6.94 6.14l.1.81l.82.06c2.32.19 4.14 2.16 4.14 4.49c0 1.95-1.26 3.59-3 4.21v2.11c2.86-.68 5-3.26 5-6.32c0-3.11-2.23-5.76-5.2-6.36Z"/>
                    </svg>
                    <span>WarePro</span>
                </a>
            </div>

            <!-- Primary Navigation Menu -->
            <div class="ml-auto flex gap-4">
                <div class="flex gap">
                    <div class="hidden sm:ms-6 sm:flex sm:items-center">

                    <!-- Settings Dropdown -->
                    <div class="relative ms-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                    >
                                        {{ $page.props.auth.user.name }}

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
                                <DropdownLink
                                    :href="route('profile.edit')"
                                >
                                    Profile
                                </DropdownLink>
                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                    </div>

                    <!-- User -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="
                                showingNavigationDropdown =
                                    !showingNavigationDropdown
                            "
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 text-indigo-700">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18a9 9 0 0 0 0 18m0 0a9 9 0 0 0 5-1.5a4 4 0 0 0-4-3.5h-2a4 4 0 0 0-4 3.5a9 9 0 0 0 5 1.5m3-11a3 3 0 1 1-6 0a3 3 0 0 1 6 0"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="absolute bg-white translate-x-0 shadow-md rounded-md p-4 right-8 top-16 border-2" 
                :class="{
                    hidden: !showingNavigationDropdown,
                    'inline-flex': showingNavigationDropdown,
                }">
                <div class="flex gap-2 flex-col">
                    <div class="w-full h-auto justify-start rounded-sm p-2">
                        <div class="text-base font-medium text-gray-800">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm font-medium text-gray-500">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="w-full h-auto justify-start rounded-sm p-2">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <Sidebar 
            :class="{
                hidden: showingAppSideBar,
                block: !showingAppSideBar,
                'transition-all': true,
            }"
        />

        <!-- Page Content -->
        <main class="lg:ml-96 flex flex-col justify-between mt-16 lg:mr-8 transition">
            <header
                class="bg-white shadow mt-8 rounded-md"
                v-if="$slots.header"
            >
                <div class="mx-auto px-6 py-2 sm:px-4 lg:px-2">
                    <slot name="header" />
                </div>
            </header>
            <slot />
        </main>
    </div>
</template>
