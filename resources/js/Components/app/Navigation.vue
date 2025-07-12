<template>
    <nav class="min-w-[200px] bg-slate-300 navigation-tt">
        <div>
            <Link :href="route('myFiles')" class="h-[80px] px-3 flex items-center gap-3">
                <!-- <ApplicationLogo
                class="block h-9 w-auto fill-current text-gray-800"
                /> -->
            SamaAlAman
            </Link>
        </div>
        <div class="px-3">
            <CreateNewDropdown />
            <div class="py-3 flex flex-row flex-wrap gap-2 sm:flex-col sm:gap-0">
                <NavLink :href="route('myFiles')" :active="$page.props.route_name === 'myFiles'">My Files</NavLink>
                <NavLink :href="route('file.sharedWithMe')" :active="$page.props.route_name === 'file.sharedWithMe'">Shared with me</NavLink>
                <NavLink :href="route('file.sharedByMe')" :active="$page.props.route_name === 'file.sharedByMe'">Shared by me</NavLink>
                <NavLink :href="route('trash')" :active="$page.props.route_name === 'trash'">Trash</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import ApplicationLogo from '../ApplicationLogo.vue';
import {Link} from '@inertiajs/vue3'
import CreateNewDropdown from '@/Components/app/CreateNewDropdown.vue';
import NavLink from '@/Components/NavLink.vue';

//Imports


//Uses


//Refs


//Props and Emit


//Computed


//Methods


//Hooks


</script>
