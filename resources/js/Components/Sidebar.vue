<script setup>
    import { ref } from 'vue';
    import SidebarLink from '@/Components/Buttons/SidebarLink.vue';
    import SidebarDropdown from '@/Components/Buttons/SidebarDropdown.vue';
    import SubSidebarLink from '@/Components/Buttons/SubSidebarLink.vue';
    import Humburger from '@/Components/Svgs/Hamburger.vue';
    import Pie from '@/Components/Svgs/Pie.vue';
    import ArrowDown from '@/Components/Svgs/ArrowDown.vue';
    import ArrowRight from '@/Components/Svgs/ArrowRight.vue';
    import Bag from '@/Components/Svgs/Bag.vue';
    import Inbox from '@/Components/Svgs/Inbox.vue';
    import Documents from '@/Components/Svgs/Documents.vue';
    import Files from '@/Components/Svgs/Files.vue';
    import CircleDot from '@/Components/Svgs/CircleDot.vue';
    import Import from '@/Components/Svgs/Import.vue';
    import ClipboardList from '@/Components/Svgs/ClipboardList.vue';

    const isComponentOpen = ref(false);
    const isiPPMPOpen = ref(false);
    const iscPPMPOpen = ref(false);
    
    const toggleComponent = () => {
        isComponentOpen.value = !isComponentOpen.value;
    };
    const toggleiPPMP = () => {
        isiPPMPOpen.value = !isiPPMPOpen.value;
    };
    const togglecPPMP = () => {
        iscPPMPOpen.value = !iscPPMPOpen.value;
    };

</script>

<template>
    <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="inline-flex items-center p-2 mt-2 ml-3 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
        <span class="sr-only">Dashboard</span>
        <Humburger/>
    </button>

    <aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidenav">
        <div class="overflow-y-auto py-5 px-3 h-full  border-r border-gray-200 bg-gray-50">
            <ul class="space-y-2">
                <li class="px-5">
                    <div class="flex flex-row items-center">
                        <div class="text-sm font-light tracking-wide text-gray-500">Navigation</div>
                    </div>
                </li>
                <li>
                    <SidebarLink :href="route('dashboard')" :active="true">
                        <Pie/>
                        <span class="ml-3">Dashboard</span>
                    </SidebarLink>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">Core</div>
                        </div>
                    </div>
                    <SidebarDropdown  @click="toggleComponent" :active="false" aria-controls="dropdown-component" data-collapse-toggle="dropdown-component">
                        <Files/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Components</span>
                        <ArrowDown/>
                    </SidebarDropdown>
                    <ul :class="{ 'hidden': !isComponentOpen, 'py-2 space-y-2': false }" id="dropdown-component">
                        <li>
                            <SubSidebarLink :href="route('fund.display.all')">Fund Clusters</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Categories</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Products</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Offices</SubSidebarLink>
                        </li>
                    </ul>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">PPMP</div>
                        </div>
                    </div>
                    <SidebarLink :href="route('dashboard')" :active="true">
                        <Import/>
                        <span class="ml-3">Upload</span>
                    </SidebarLink>
                </li>
                <li>
                    <ul id="dropdown-iPPMP" :class="{ 'hidden': !isiPPMPOpen, 'py-2 space-y-2': false }">
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Drafts</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Initials</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Approved</SubSidebarLink>
                        </li>
                    </ul>
                </li>
                <li>
                    <SidebarDropdown @click="togglecPPMP" :active="true"  aria-controls="dropdown-cPPMP" data-collapse-toggle="dropdown-cPPMP">
                        <ClipboardList/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Consolidated</span>
                        <ArrowDown/>
                    </SidebarDropdown>
                    <ul id="dropdown-cPPMP" :class="{ 'hidden': !iscPPMPOpen, 'py-2 space-y-2': false }">
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Drafts</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Initials</SubSidebarLink>
                        </li>
                        <li>
                            <SubSidebarLink :href="route('dashboard')" :active="true">Approved</SubSidebarLink>
                        </li>
                    </ul>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">Purchase Request</div>
                        </div>
                    </div>
                    <SidebarDropdown :active="true" aria-controls="dropdown-sales" data-collapse-toggle="dropdown-sales">
                        <Bag/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Purchase Request</span>
                        <ArrowDown/>
                    </SidebarDropdown>
                    <ul id="dropdown-sales" class="hidden py-2 space-y-2">
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Products</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Billing</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Invoice</a>
                            </li>
                    </ul>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">IAR</div>
                        </div>
                    </div>
                    <SidebarDropdown :active="true" aria-controls="dropdown-sales" data-collapse-toggle="dropdown-sales">
                        <Inbox/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Reiceving</span>
                        <ArrowDown/>
                    </SidebarDropdown>
                    <ul id="dropdown-sales" class="hidden py-2 space-y-2">
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Products</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Billing</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Invoice</a>
                            </li>
                    </ul>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">RIS</div>
                        </div>
                    </div>
                    <SidebarDropdown :active="true" aria-controls="dropdown-sales" data-collapse-toggle="dropdown-sales">
                        <ArrowRight/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Releasing</span>
                        <ArrowDown/>
                    </SidebarDropdown>
                    <ul id="dropdown-sales" class="hidden py-2 space-y-2">
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Products</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Billing</a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">Invoice</a>
                            </li>
                    </ul>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <SidebarLink :href="route('dashboard')" :active="true">
                        <Documents/>
                        <span class="ml-3">Reports</span>
                    </SidebarLink>
                </li>
                <li>
                    <SidebarLink :href="route('dashboard')" :active="true">
                        <CircleDot/>
                        <span class="ml-3">Help</span>
                    </SidebarLink>
                </li>
            </ul>
        </div>
    </aside>
</template>
