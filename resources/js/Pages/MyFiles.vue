<template>
    <AuthenticatedLayout>
        <!-- <pre>{{ ancestors }}</pre> -->
        <!-- breadcumbs | starts  -->
        <nav class="flex items-center justify-between p-1 mb-3">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li v-for="ans of ancestors.data" :key="ans.id" class="inline-flex items-center">
                    <Link v-if="!ans.parent_id" :href="route('myFiles')"
                          class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <HomeIcon class="w-4 h-4"/>
                        My Files
                    </Link>
                    <div v-else class="flex items-center">
                        <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <Link :href="route('myFiles', {folder: ans.path})"
                              class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ ans.name }}
                        </Link>
                    </div>
                </li>
            </ol>

            <div class="flex">
                <!-- <label class="flex items-center mr-3">
                    Only Favourites
                    <Checkbox @change="showOnlyFavourites"  v-model:checked="onlyFavourites" class="ml-2"/>
                </label> -->
                <!-- <ShareFilesButton :all-selected="allSelected" :selected-ids="selectedIds" /> -->
                <!-- <DownloadFilesButton :all="allSelected" :ids="selectedIds" class="mr-2"/> -->
                <!-- <DeleteFilesButton :delete-all="allSelected" :delete-ids="selectedIds" @delete="onDelete"/> -->
            </div>
        </nav>
        <!-- breadcumbs | ends  -->

        <table class="min-w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Name
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Owner
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Last Modiefied
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Size
                    </th>
                </tr>
            </thead>
            <tbody>
            <tr v-for="file of files.data" :key="file.id"
                @dblclick="openFolder(file)"                
                class="bg-slate-400 border-b transition duration-300 ease-in-out hover:bg-slate-300 cursor-pointer"> 
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                    <FileIcon :file="file"/>
                    {{ file.name }}
                </td>
                <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                    {{ file.owner }}
                </td>
                <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                    {{ file.created_at }}
                </td>
                <td class="text-sm text-gray-900 font-light px-6 py-4 whitespace-nowrap">
                    {{ file.size }}
                </td>
            </tr>
            </tbody>
        </table>
        <div v-if="!files.data.length"
             class="py-8 text-center text-sm text-gray-400"
        >There is no data in this folder!
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { HomeIcon } from '@heroicons/vue/20/solid';
import { router,Link } from '@inertiajs/vue3';
import FileIcon from '@/Components/app/FileIcon.vue'

//Imports


//Uses


//Refs


//Props and Emit
const {files} = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object
})

//Computed


//Methods
function openFolder(file){    
    
    if(!file.is_folder){
        alert('not folder');
        return;
    }
    router.visit(route('myFiles',{folder:file.path}));
}

//Hooks


</script>
