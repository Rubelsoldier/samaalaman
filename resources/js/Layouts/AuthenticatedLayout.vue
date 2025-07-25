<template>
    <div class="h-screen bg-gray-100 flex flex-col sm:flex-row w-full gap-0 sm:gap-4">
        <Navigation />

        <main 
            @drop.prevent="handleDrop"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            class="flex flex-col flex-1 px-2 sm:px-4 overflow-hidden bg-gray-100 main-tt"
            :class="dragOver ? 'dropzone' : ''"
        >
            
            <template v-if="dragOver" class="text-gray-500 text-center py-8 text-sm">
                Drop files here to upload
            </template>
            <template v-else>
                <div class="flex flex-row items-center justify-between w-full gap-2">
                    <div class="w-auto">
                        <SearchForm/>
                    </div>
                    <div class="w-auto flex justify-end">
                        <UserSettingsDropdown/>
                    </div>
                </div>
                <div class="flex-1 flex flex-col overflow-hidden">
                    <slot/>
                </div>
            </template>
        </main>
    </div>
    <ErrorDialog />
    <FormProgress :form="fileUploadForm" />
    <Notification />
</template>

<script setup>

//Imports
import Navigation from '@/Components/app/Navigation.vue';
import SearchForm from '@/Components/app/SearchForm.vue';
import UserSettingsDropdown from '@/Components/app/UserSettingsDropdown.vue';
import FormProgress from '@/Components/app/FormProgress.vue';
import {emitter, FILE_UPLOAD_STARTED, showErrorDialog} from "@/event-bus.js";
import { useForm,usePage } from '@inertiajs/vue3';
import {onMounted, ref} from "vue";
import ErrorDialog from "@/Components/ErrorDialog.vue";
import Notification from "@/Components/Notification.vue";
import {showSuccessNotification} from "@/event-bus.js";

//Uses
const page = usePage();

const fileUploadForm = useForm({
    files: [],
    relative_paths: [],
    parent_id: null
})


//Refs
const dragOver = ref(false)

//Props and Emit


//Computed


//Methods
function onDragOver() {
    dragOver.value = true
}

function onDragLeave() {
    dragOver.value = false
}

function handleDrop(ev){
    dragOver.value = false;
    const files = ev.dataTransfer.files
    if (!files.length) {
        return
    }
    uploadFiles(files)
}

function uploadFiles(files){
    fileUploadForm.parent_id = page.props.folder.id;
    fileUploadForm.files = files;
    fileUploadForm.relative_paths = [...files].map(f => f.webkitRelativePath);

    fileUploadForm.post(route('file.store'), {

        onSuccess: () => {
            showSuccessNotification(`${files.length} files have been uploaded`)
        },

        onError: errors => {
            let message = '';
            if (Object.keys(errors).length > 0) {
                message = errors[Object.keys(errors)[0]]
            } else {
                message = 'Error during file upload. Please try again later.'
            }

            showErrorDialog(message)
        },
        onFinish: () => {
            fileUploadForm.clearErrors()
            fileUploadForm.reset();
        }
    })
}


//Hooks
onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles)
})

</script>

<style scoped>
    .dropzone {
        width: 100%;
        height: 100%;
        color: #8d8d8d;
        border: 2px dashed gray;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>