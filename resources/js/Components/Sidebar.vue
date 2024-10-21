<script setup>
    import { ref} from 'vue';
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
    import ArrowHeadRight from './Svgs/ArrowHeadRight.vue';
    import Column from '@/Components/Svgs/Column.vue';
    import Add from '@/Components/Svgs/Add.vue';
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
                    <SidebarLink :href="route('dashboard')" :active="$page.component == 'Dashboard'">
                        <Pie :class="{'text-white': $page.component == 'Dashboard'}"/>
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">Dashboard</span>
                    </SidebarLink>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">Core</div>
                        </div>
                    </div>
                    <SidebarDropdown :active="route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active')">
                            <Files :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active')}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Components</span>
                            <ArrowDown :class="{'text-white': route().current('fund.display.all') || route().current('item.display.active') || route().current('office.display.active') || route().current('category.display.active')}" />
                        <template #dropdown-items>
                            <li>
                                <SubSidebarLink :href="route('fund.display.all')" :active="route().current('fund.display.all')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('fund.display.all')}"/>
                                    Fund Clusters
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('category.display.active')" :active="route().current('category.display.active')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('category.display.all')}"/>
                                    Categories
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('item.display.active')" :active="route().current('item.display.active')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('item.display.active')}"/>
                                    Item Classes
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('office.display.active')" :active="route().current('office.display.active')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('office.display.active')}"/>
                                    Offices
                                </SubSidebarLink>
                            </li>
                        </template>
                    </SidebarDropdown>
                </li>
                <li>
                    <SidebarDropdown :active="route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')">
                            <Column :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Products</span>
                            <ArrowDown :class="{'text-white': route().current('product.display.active') || route().current('product.display.active.pricelist') || route().current('product.unmodified.list')}" />
                        <template #dropdown-items>
                            <li>
                                <SubSidebarLink :href="route('product.display.active')" :active="route().current('product.display.active')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active')}"/>
                                    Lists
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('product.display.active.pricelist')" :active="route().current('product.display.active.pricelist')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.display.active.pricelist')}"/>
                                    Prices
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="'https://localhost'" :active="false">
                                    <ArrowHeadRight :class="{ 'text-white' : false}"/>
                                    Inventory
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('product.unmodified.list')" :active="route().current('product.unmodified.list')">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('product.unmodified.list')}"/>
                                    Unmodified
                                </SubSidebarLink>
                            </li>
                        </template>
                    </SidebarDropdown>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">PPMP</div>
                        </div>
                    </div>
                    <SidebarLink :href="route('import.ppmp.index')" :active="route().current('import.ppmp.index')">
                        <Add :class="{ 'text-white' : route().current('import.ppmp.index')}"/>
                        <span class="ml-3">Create</span>
                    </SidebarLink>
                </li>
                <li>
                    <SidebarDropdown :active="route().current('indiv.ppmp.type')">
                            <ClipboardList :class="{'text-white': false}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Individual</span>
                            <ArrowDown :class="{'text-white': false}" />
                        <template #dropdown-items>
                            <li>
                                <SubSidebarLink :href="route('indiv.ppmp.type', { type: 'individual' , status: 'draft'})" :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'})">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('indiv.ppmp.type', { type: 'individual' , status: 'draft'})}"/>
                                    Draft
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="route('indiv.ppmp.type', { type: 'individual' , status: 'final'})" :active="route().current('indiv.ppmp.type', { type: 'individual' , status: 'final'})">
                                    <ArrowHeadRight :class="{ 'text-white' : route().current('indiv.ppmp.type', { type: 'individual' , status: 'final'})}"/>
                                    Final
                                </SubSidebarLink>
                            </li>
                        </template>
                    </SidebarDropdown>
                </li>
                <li>
                    <SidebarDropdown :active="false">
                            <ClipboardList :class="{'text-white': false}" />
                            <span class="flex-1 ml-3 text-left whitespace-nowrap">Consolidated</span>
                            <ArrowDown :class="{'text-white': false}" />
                        <template #dropdown-items>
                            <li>
                                <SubSidebarLink :href="route('conso.ppmp.type', { type: 'consolidated' , status: 'draft'})" :active="false">
                                    <ArrowHeadRight :class="{ 'text-white' : false}"/>
                                    Draft
                                </SubSidebarLink>
                            </li>
                            <li>
                                <SubSidebarLink :href="'https://localhost'" :active="false">
                                    <ArrowHeadRight :class="{ 'text-white' : false}"/>
                                    Final
                                </SubSidebarLink>
                            </li>
                        </template>
                    </SidebarDropdown>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">Purchase Request</div>
                        </div>
                    </div>
                    <SidebarLink :href="'https://localhost'" :active="false">
                        <Bag/>
                        <span class="ml-3">Purchase Request</span>
                    </SidebarLink>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">IAR</div>
                        </div>
                    </div>
                    <SidebarLink :href="'https://localhost'" :active="false">
                        <Inbox/>
                        <span class="ml-3">Receiving</span>
                    </SidebarLink>
                </li>
                <li>
                    <div class="px-5 pt-2">
                        <div class="flex flex-row items-center">
                            <div class="text-sm font-light tracking-wide text-gray-500">RIS</div>
                        </div>
                    </div>
                    <SidebarLink :href="'https://localhost'" :active="false">
                        <ArrowRight/>
                        <span class="ml-3">Releasing</span>
                    </SidebarLink>
                </li>
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <SidebarLink :href="route('dashboard')" :active="false">
                        <Documents/>
                        <span class="ml-3">Reports</span>
                    </SidebarLink>
                </li>
                <li>
                    <SidebarLink :href="route('dashboard')" :active="false">
                        <CircleDot/>
                        <span class="ml-3">Help</span>
                    </SidebarLink>
                </li>
            </ul>
        </div>
    </aside>
</template>
