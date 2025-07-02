<template>
    <modal :show="props.modelValue" @show="onShow" max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Move Files
                <!-- <pre>{{ navigationStack }}</pre> -->
            </h2>
            <!-- breadcrumbs -->
            <ol class="inline-flex items-center space-x-1 md:space-x-3 mb-4">
                <li v-for="(ans, index) of navigationStack" :key="ans.id" 
                    class="inline-flex items-center relative">
                    <div v-if="!ans.parent_id" class="flex items-center">
                        <Link @click.prevent="navigateToFolder(index)"
                              class="inline-flex gap-1 items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <HomeIcon class="w-4 h-4"/>
                            {{ index === 0 ? 'My Files' : ans.name }}
                        </Link>
                        <button v-if="ans.has_children" @click.stop="loadSubFolders(ans)" class="focus:outline-none ml-1">
                            <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                    <div v-else class="flex items-center">
                        <a @click="navigateToFolder(index)"
                           class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white cursor-pointer">
                            <!-- Only show icon for root, not for others -->
                            <!-- <HomeIcon class="w-4 h-4"/> -->
                            {{ ans.name }}
                        </a>
                        <button v-if="ans.has_children" @click.stop="loadSubFolders(ans)" class="focus:outline-none ml-1">
                            <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                      clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </li>
            </ol>

            <!-- Current folder contents -->
            <div v-if="subFolders.length" 
                class="mt-4 border rounded-lg divide-y"
            >
                <div v-for="folder in subFolders" 
                     :key="folder.id"
                     @click="selectFolder(folder)"
                     class="p-2 hover:bg-gray-100 cursor-pointer flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                    </svg>
                    {{ folder.name }}
                    <button v-if="folder.has_children" @click.stop="loadSubFolders(folder)" class="focus:outline-none ml-1">
                        <svg aria-hidden="true" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3"
                               :class="{ 'opacity-25': form.processing }"
                               @click="moveFiles" :disable="form.processing">
                    Submit
                </PrimaryButton>
            </div>
        </div>
    </modal>
</template>

<script setup>
// Imports
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";  // Fixed Link import
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { nextTick, ref, onMounted } from "vue";
import { showSuccessNotification } from "@/event-bus.js";
import { HomeIcon } from '@heroicons/vue/20/solid'

// Uses
const page = usePage();

// Refs
const props = defineProps({
    modelValue: Boolean,
    allSelected: Boolean,
    selectedId: {
        type: [Number, String],
        required: true,
        default: null
    },
    ancestors: {
        type: Object,
        required: true
    },
    width: {
        type: Number,
        required: true
    },
    selectedFile_lft: {
        type: Number,
        required: true
    },
    selectedFile_rgt: {
        type: Number,
        required: true
    }
})

const form = useForm({    
    parent_id: null,
    parent_rgt: null,
    selectedFile_lft: null,
    selectedFile_rgt: null,
    width: null
})

const emailInput = ref(null)
const currentFolderId = ref(null);
const subFolders = ref([]);
const navigationStack = ref(props.ancestors.data.map(ancestor => ({
    ...ancestor,
    has_children: true,
    // _rgt: ancestor._rgt // Make sure _rgt is included from ancestors
})));
const initialAncestors = ref(null);

// Props & Emit
const emit = defineEmits(['update:modelValue'])

// Computed

// Methods
function onShow() {
    nextTick(() => {
        initialAncestors.value = [...props.ancestors.data];
        navigationStack.value = props.ancestors.data.map(ancestor => ({
            ...ancestor,
            has_children: true
        }));
    })
}

function moveFiles() {
    const targetFolder = navigationStack.value[navigationStack.value.length - 1];
    //alert(JSON.stringify(targetFolder));
    form.parent_id = targetFolder.id;
    form.parent_rgt = targetFolder._rgt;

    // Assign lft and rgt values from props to form before submit
    form.selectedFile_lft = props.selectedFile_lft;
    form.selectedFile_rgt = props.selectedFile_rgt;
    form.width = props.width;

    // if (props.allSelected) {
    //     form.all = true;
    //     form.ids = [];
    // } else {
    //     form.ids = props.selectedIds;
    // }

    form.post(route('files.move'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            showSuccessNotification('Files moved successfully');
        },
        onError: (errors) => {
            if (errors.message) {
                showSuccessNotification(errors.message, 'error');
            } else {
                showSuccessNotification('Error moving files', 'error');
            }
        }
    });
}

function closeModal() {
    navigationStack.value = initialAncestors.value.map(ancestor => ({
        ...ancestor,
        has_children: true
    }));
    subFolders.value = [];
    currentFolderId.value = null;
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

async function selectFolder(folder) {    
    
    try {
        const response = await axios.get(route('folders.subfolders', { folder: folder.id }));
        alert(JSON.stringify(response.data)); // Remove or comment out for production
        // Use the folder.path to reconstruct the breadcrumb if available
        let breadcrumb = [];
        const root = initialAncestors.value ? initialAncestors.value[0] : props.ancestors.data[0];

        // Determine if this folder has subfolders
        const hasSubfolders = Array.isArray(response.data) && response.data.length > 0;

        if (folder.id === root.id) {
            breadcrumb = [{
                ...root,
                has_children: hasSubfolders
            }];
        } else if (folder.path) {
            // Split the path and walk from root to the selected folder
            const pathParts = folder.path.split('/').filter(Boolean);
            // Find all known folders (from initialAncestors and navigationStack)
            let allKnown = [
                ...(initialAncestors.value || []),
                ...navigationStack.value,
                folder
            ];
            // Always start with root
            let currentStack = [{
                ...root,
                has_children: true
            }];
            let currentPath = '';
            for (let i = 0; i < pathParts.length; i++) {
                currentPath = currentPath ? `${currentPath}/${pathParts[i]}` : pathParts[i];
                // Find the folder with this path
                let match = allKnown.find(f => f.path === currentPath);
                if (!match && i === pathParts.length - 1) {
                    // For the last item, use folderWithProperties
                    match = {
                        id: folder.id,
                        name: folder.name,
                        path: folder.path,
                        has_children: hasSubfolders,
                        parent_id: folder.parent_id,
                        _rgt: folder._rgt
                    };
                }
                if (match) {
                    currentStack.push({
                        ...match,
                        has_children: i === pathParts.length - 1 ? hasSubfolders : true
                    });
                }
            }
            breadcrumb = currentStack;
        } else {
            // fallback to previous logic
            let ancestors = folder.ancestors;
            if (!ancestors && response.data && response.data.ancestors) {
                ancestors = response.data.ancestors;
            }
            const folderWithProperties = {
                id: folder.id,
                name: folder.name,
                path: folder.path,
                has_children: hasSubfolders,
                parent_id: folder.parent_id,
                _rgt: folder._rgt
            };
            if (ancestors && Array.isArray(ancestors) && ancestors.length > 0) {
                breadcrumb = [
                    ...ancestors.map(a => ({
                        ...a,
                        has_children: true
                    })),
                    folderWithProperties
                ];
            } else {
                // Walk up the parent chain using all known folders to keep all parents
                let stack = [];
                let current = folderWithProperties;
                let allKnown = [
                    ...(initialAncestors.value || []),
                    ...navigationStack.value,
                    folderWithProperties
                ];
                while (current && current.parent_id) {
                    const parent = allKnown.find(f => f.id === current.parent_id);
                    if (parent) {
                        stack.unshift({
                            ...parent,
                            has_children: true
                        });
                        current = parent;
                    } else {
                        break;
                    }
                }
                stack.push(folderWithProperties);
                if (stack.length === 1 || stack[0].id !== root.id) {
                    stack.unshift({
                        ...root,
                        has_children: true
                    });
                }
                breadcrumb = stack;
            }
        }

        navigationStack.value = breadcrumb;

        // Reset subfolders and currentFolderId
        subFolders.value = [];
        currentFolderId.value = null;

        if (hasSubfolders) {
            subFolders.value = response.data.map(f => ({
                ...f,
                has_children: Array.isArray(f.subfolders) ? f.subfolders.length > 0 : !!f.has_children
            }));
        }

        currentFolderId.value = folder.id;
        form.parent_id = folder.id;
    } catch (error) {
        console.error('Error loading subfolders:', error);
    }
}

async function navigateToFolder(index) {
    try {
        // Trim navigation stack to selected folder
        navigationStack.value = navigationStack.value.slice(0, index + 1);
        const folder = navigationStack.value[navigationStack.value.length - 1];
        
        // Clear current state
        subFolders.value = [];
        currentFolderId.value = null;

        // Load subfolders of selected folder
        const response = await axios.get(route('folders.subfolders', { folder: folder.id }));
        const hasSubfolders = Array.isArray(response.data) && response.data.length > 0;
        if (hasSubfolders) {
            subFolders.value = response.data.map(f => ({
                ...f,
                has_children: Array.isArray(f.subfolders) ? f.subfolders.length > 0 : !!f.has_children
            }));
        }
        
        currentFolderId.value = folder.id;
        form.parent_id = folder.id;
    } catch (error) {
        console.error('Error loading subfolders:', error);
    }
}

async function loadSubFolders(folder) {
    try {
        // Toggle dropdown behavior
        if (currentFolderId.value === folder.id) {
            currentFolderId.value = null;
            subFolders.value = [];
            return;
        }

        const response = await axios.get(route('folders.subfolders', { folder: folder.id }));
        const hasSubfolders = Array.isArray(response.data) && response.data.length > 0;
        if (hasSubfolders) {
            subFolders.value = response.data.map(f => ({
                ...f,
                has_children: Array.isArray(f.subfolders) ? f.subfolders.length > 0 : !!f.has_children
            }));
            currentFolderId.value = folder.id;
        } else {
            currentFolderId.value = null;
            subFolders.value = [];
        }
    } catch (error) {
        console.error('Error loading subfolders:', error);
        subFolders.value = [];
    }
}

// Close dropdown when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.folder-dropdown')) {
            currentFolderId.value = null;
        }
    });
});

// Hooks
</script>

<style scoped>
.folder-dropdown {
    position: relative;
}
</style>
