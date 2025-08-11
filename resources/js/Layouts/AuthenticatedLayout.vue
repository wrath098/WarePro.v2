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
        <nav class="flex items-center fixed w-screen h-16 z-40 top-0 left-0 bg-gradient-to-r from-indigo-500 to-purple-500 transition px-8 shadow-sm">
            <div class="w-80 flex items-center">
                <button class="inline-flex justify-center place-items-center rounded-lg hover:bg-indigo-200 mr-2 w-10 h-10 transition lg:hidden text-gray-50 hover:text-gray-800"
                        @click="showingAppSideBar = !showingAppSideBar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M5 12h14M5 7h14"/>
                    </svg>
                </button>
                <a :href="route('dashboard')" class="inline-flex items-center text-2xl rounded text-gray-700 font-medium gap-2">
                    <img src="/WarePro.v2/assets/images/Nav_Winvexis.png" class="w-48">
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
                                        class="inline-flex items-center rounded-md border border-transparent px-3 py-2 text-sm font-medium leading-4 text-gray-50 transition duration-150 ease-in-out hover:bg-purple-200 hover:text-gray-700 focus:outline-none"
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
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-purple-200 hover:text-gray-800 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 text-gray-50 hover:text-gray-800">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18a9 9 0 0 0 0 18m0 0a9 9 0 0 0 5-1.5a4 4 0 0 0-4-3.5h-2a4 4 0 0 0-4 3.5a9 9 0 0 0 5 1.5m3-11a3 3 0 1 1-6 0a3 3 0 0 1 6 0"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="absolute bg-gray-100 translate-x-0 shadow-md rounded-md p-4 right-8 top-16 border-2" 
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
                class="bg-slate-50 shadow mt-8 rounded-md"
                v-if="$slots.header"
            >
                <div class="mx-auto px-6 py-2 sm:px-4 lg:px-2">
                    <slot name="header" />
                </div>
            </header>
            <slot />
            <footer class="mb-2 px-4 border-gray-500 flex justify-center items-center">
                <div class="text-xs text-gray-400"> © 2025 PGSO Warehouse | CG</div>
            </footer>
        </main>
    </div>
</template>
