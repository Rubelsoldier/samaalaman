<template>
    <AuthenticatedLayout>
        <!-- breadcumbs | starts  -->        
        <nav class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 p-2 sm:p-1 mb-3">
            <ol class="flex flex-wrap items-center space-x-1 md:space-x-3 overflow-x-auto text-xs sm:text-sm">
                <li v-for="ans of ancestors.data" :key="ans.id" class="inline-flex items-center">
                    <Link v-if="!ans.parent_id" :href="route('myFiles')"
                          class="inline-flex gap-1 items-center font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <HomeIcon class="w-4 h-4"/>
                        <span class="hidden xs:inline">My Files</span>
                    </Link>
                    <div v-else class="flex items-center">
                        <svg aria-hidden="true" class="w-4 h-4 sm:w-6 sm:h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <Link :href="route('myFiles', {folder: ans.path})"
                              class="ml-1 font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ ans.name }}
                        </Link>
                    </div>
                </li>
            </ol>

            <div class="flex flex-wrap gap-2 sm:gap-3 items-center mt-2 sm:mt-0">
                <div class="relative sm:mr-24 md:mr-32 lg:mr-40 xl:mr-56">
                    <button
                        @click="dropdownOpen = !dropdownOpen"
                        class="flex items-center gap-2 px-3 py-2 bg-white rounded shadow hover:bg-gray-100 text-xs sm:text-sm justify-center w-full text-center"
                    >
                        Actions
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div
                        v-if="dropdownOpen"
                        class="absolute z-10 mt-2 left-0 bg-white border rounded shadow-lg flex flex-col gap-2 p-3 text-left"
                        :class="{
                            'w-56': $screen('sm'), // desktop width
                            'w-[90vw] right-1 left-1 text-center': !$screen('sm') // mobile width and center
                        }"
                    >
                        <MoveFiles
                            :all-selected="allSelected"
                            :selected-ids="selectedIds"
                            :ancestors="ancestors"
                            :selected-file="selectedFile"
                            class="w-full"
                            @click="closeDropdown"
                            @moved="closeDropdown"
                        />
                        <label class="flex items-center text-xs sm:text-sm w-full" :class="{'justify-center': !$screen('sm')}" @click="closeDropdown">
                            Favourites
                            <Checkbox @change="showOnlyFavourites" v-model:checked="onlyFavourites" class="ml-2"/>
                        </label>
                        <ShareFilesButton
                            :all-selected="allSelected"
                            :selected-ids="selectedIds"
                            class="w-full text-center"
                            @click="closeDropdown"
                        />
                        <DownloadFilesButton
                            :all="allSelected"
                            :ids="selectedIds"
                            class="mr-2 w-full text-center"
                            @click="closeDropdown"
                        />
                        <DeleteFilesButton
                            :delete-all="allSelected"
                            :delete-ids="selectedIds"
                            @delete="onDelete"
                            class="w-full text-center"
                            @click="closeDropdown"
                        />
                    </div>
                </div>
            </div>
        </nav>
        <!-- breadcumbs | ends  -->
        <div class="flex-1 overflow-auto">
            <div class="w-full overflow-x-auto">
            <table class="min-w-full bg-slate-200 text-xs sm:text-sm">
            <thead class="bg-gray-100 border-b bg-slate-200">
                <tr>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left w-[30px] max-w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                    </th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left"></th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left">
                        Name 
                    </th>
                    <th v-if="search" class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left">
                        Path 
                    </th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left">
                        Owner
                    </th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left">
                        Last Modified
                    </th>
                    <th class="font-medium text-gray-900 px-2 sm:px-6 py-2 sm:py-4 text-left">
                        Size
                    </th>
                </tr>
            </thead>
            <tbody>
            <tr v-for="(file, idx) in allFiles.data" :key="file.id"
                @click="event => toggleFileSelect(file, idx, event)"
                @dblclick="openFolder(file)"                
                class="border-b transition duration-300 ease-in-out hover:bg-slate-300 cursor-pointer"
                :class="(selected[file.id] || allSelected ) ? 'bg-slate-300' : 'bg-slate-200'">  

                <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap font-medium text-gray-900 w-[30px] max-w-[30px] pr-0">
                    <Checkbox @click.stop="$event => onSelectCheckboxChange(file)" v-model="selected[file.id]" :checked="selected[file.id] || allSelected"/>
                </td>
                <td class="px-2 sm:px-6 py-2 sm:py-4 max-w-[40px] font-medium text-gray-900 text-yellow-500">
                    <div @click.stop.prevent="addRemoveFavourite(file)">
                        <svg v-if="!file.is_favourite" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="w-5 h-5 sm:w-6 sm:h-6">
                            <path fill-rule="evenodd"
                                    d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z"
                                    clip-rule="evenodd"/>
                        </svg>
                    </div>
                </td>
                <td class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap font-medium text-gray-900 flex items-center">
                    <FileIcon :file="file"/>
                    <span class="truncate max-w-[120px] sm:max-w-none">{{ file.name }}</span>
                </td>
                <td v-if="search" class="px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap font-medium text-gray-900">
                    <span class="truncate max-w-[120px] sm:max-w-none">{{ file.path }}</span>
                </td>
                <td class="font-light px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                    <span class="truncate max-w-[80px] sm:max-w-none">{{ file.owner }}</span>
                </td>
                <td class="font-light px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                    <span class="truncate max-w-[80px] sm:max-w-none">{{ file.created_at }}</span>
                </td>
                <td class="font-light px-2 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                    <span class="truncate max-w-[80px] sm:max-w-none">{{ file.size }}</span>
                </td>
            </tr>
            </tbody>
            </table>
            </div>
            <div v-if="!allFiles.data.length"
                class="py-8 text-center text-sm text-gray-400"
                >There is no data in this folder!
            </div>
            <div ref="loadMoreIntersect"></div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
//Imports

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { HomeIcon } from '@heroicons/vue/20/solid';
import { router,Link, usePage } from '@inertiajs/vue3';
import FileIcon from '@/Components/app/FileIcon.vue'
import DeleteFilesButton from '@/Components/app/DeleteFilesButton.vue'
import ShareFilesButton from '@/Components/app/ShareFilesButton.vue'
import MoveFiles from '@/Components/app/MoveFiles.vue';
import { ref, computed, onMounted, onUpdated } from "vue";
import { httpGet , httpPost } from '@/Helper/http-helper';
import Checkbox from '@/Components/Checkbox.vue';
import DownloadFilesButton from '@/Components/app/DownloadFilesButton.vue';
import {emitter, ON_SEARCH, showSuccessNotification} from "@/event-bus.js";


//Uses
const page = usePage();
let params = null;

//Props and Emit
const props = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object
})


//Refs
let search = ref('');
const selected = ref({});
const loadMoreIntersect = ref(null)
const onlyFavourites = ref(false);
const lastSelectedFileIndex = ref(null);
const dropdownOpen = ref(false);

const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next
})

//Computed
const selectedFile = computed(() => {
    const found = allFiles.value.data.find(file => selected.value[file.id] || allSelected.value);
    return found || null;
})

const selectedIds = computed(() => Object.entries(selected.value).filter(a => a[1]).map(a => a[0]))

const allSelected = computed({
  get() {
    if (allFiles.value.data.length === 0) {
      return false;
    }
    const allFileIds = allFiles.value.data.map(f => f.id);
    const selectedCount = allFileIds.filter(id => selected.value[id]).length;
    return selectedCount === allFiles.value.data.length;
  },
  set(value) {
    allFiles.value.data.forEach(file => {
      selected.value[file.id] = value;
    });
  }
});

//Methods
function openFolder(file){    
    
    if(!file.is_folder){
        alert('not folder');
        return;
    }
    router.visit(route('myFiles',{folder:file.path}));
}

function closeDropdown() {
    dropdownOpen.value = false;
}

function loadMore(){
    if (allFiles.value.next === null) {
        return
    }

    httpGet(allFiles.value.next)
        .then(res => {
            allFiles.value.data = [...allFiles.value.data, ...res.data]
            allFiles.value.next = res.links.next
        })
}

function onSelectAllChange() {
    allFiles.value.data.forEach(f => {
        selected.value[f.id] = allSelected.value
    })
}

function onSelectCheckboxChange(file){
    // make checkAll false, if at least one checkbox is not selected
    if( !selected.value[file.id] ){
        allSelected.value = false;
    } else{   // checking, all files in a folder. If any is not checked break this loop. Otherwise, make allselected true.
        let checked = true;
        for( let file of allFiles.value.data ){
            if(!selected.value[file.id]){
                checked = false;
                break;
            }
        }
        allSelected.value = checked;
    }
}

function toggleFileSelect(file, index, event) {
    if (event.shiftKey && lastSelectedFileIndex.value !== null) {
        const start = Math.min(lastSelectedFileIndex.value, index);
        const end = Math.max(lastSelectedFileIndex.value, index);

        for (let i = start; i <= end; i++) {
            const fileToSelect = allFiles.value.data[i];
            selected.value[fileToSelect.id] = true;
        }
    } else {
        selected.value[file.id] = !selected.value[file.id];
        lastSelectedFileIndex.value = index;
    }
}

function onDelete() {
    allSelected.value = false
    selected.value = {}
    closeDropdown(); // Hide dropdown after delete
}

function showOnlyFavourites() {
    closeDropdown(); // Hide dropdown when toggling favourites
    if (onlyFavourites.value) {
        params.set('favourites', 1)
    } else {
        params.delete('favourites')
    }
    router.get(window.location.pathname+'?'+params.toString())
}


function addRemoveFavourite(file) {
    closeDropdown(); // Hide dropdown when toggling favourite

httpPost(route('file.addToFavourites'), {id: file.id})
    .then(() => {
        file.is_favourite = !file.is_favourite
        showSuccessNotification('Selected files have been added to favourites')
    })
    .catch(async (er) => {
        console.log(er.error.message);
    })
}


// Add a simple screen size utility for responsive classes
const $screen = (size) => {
    if (typeof window === 'undefined') return true;
    if (size === 'sm') return window.innerWidth >= 640;
    return false;
};


//Hooks

onUpdated(() => {
    allFiles.value = {
        data: props.files.data,
        next: props.files.links.next
    }
})

onMounted(() => {
    params = new URLSearchParams(window.location.search)
    onlyFavourites.value = params.get('favourites') === '1'

    search.value = params.get('search');
    emitter.on(ON_SEARCH, (value) => {
        search.value = value
    })

    const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore()), {
        rootMargin: '-250px 0px 0px 0px'
    })

    observer.observe(loadMoreIntersect.value)

})
</script>
