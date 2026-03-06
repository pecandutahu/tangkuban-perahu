<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    role: Object,
    permissions: Array,
    rolePermissions: Array,
});

const isEdit = !!props.role.id;

// Prevent modifying HR Admin name
const isHrAdmin = props.role.name === 'HR Admin';

const form = useForm({
    name: props.role.name || '',
    permissions: props.rolePermissions || [],
});

const submit = () => {
    if (isEdit) {
        form.put(route('roles.update', props.role.id));
    } else {
        form.post(route('roles.store'));
    }
};

const formatPermissionName = (name) => {
    // Convert 'view-master-data' to 'View Master Data'
    return name.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

</script>

<template>
    <Head :title="isEdit ? 'Ubah Hak Akses Role' : 'Tambah Role Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isEdit ? 'Pengaturan Akses: ' + role.name : 'Pendaftaran Role Baru' }}
                </h2>
                <Link :href="route('roles.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Kembali ke Daftar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                    <!-- Warning Error Block -->
                    <div v-if="form.errors.error" class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                        <p class="font-bold">Error</p>
                        <p>{{ form.errors.error }}</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <!-- Block 1: Role Detail -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Informasi Utama</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nama Jabatan (Role)" />
                                    <!-- HR Admin is readonly to prevent system lockout -->
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        :readonly="isHrAdmin"
                                        :class="{'bg-gray-100 cursor-not-allowed': isHrAdmin}"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                    <p v-if="isHrAdmin" class="mt-1 text-xs text-orange-600">Nama "HR Admin" terkunci karena ini adalah Super Administrator mesin.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Block 2: Permissions Checkbox System -->
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">Pemetaan Hak Akses Sistem (Permissions)</h3>
                            
                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                <p class="text-sm text-gray-600 mb-6">Pilih modul mana saja yang boleh disentuh oleh Role ini. Kotak yang tidak tercentang berarti menu/aksi tersebut akan disembunyikan secara otomatis di antarmuka Web mereka.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-8">
                                    <div v-for="permission in permissions" :key="permission.id" class="flex items-start">
                                        <div class="flex h-6 items-center">
                                            <input
                                                :id="'perm-' + permission.id"
                                                :name="'perm-' + permission.id"
                                                type="checkbox"
                                                :value="permission.name"
                                                v-model="form.permissions"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                            />
                                        </div>
                                        <div class="ml-3 text-sm leading-6">
                                            <label :for="'perm-' + permission.id" class="font-medium text-gray-900 select-none cursor-pointer">
                                                {{ formatPermissionName(permission.name) }}
                                            </label>
                                            <p class="text-gray-500 text-xs">ID Key: {{ permission.name }}</p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="permissions.length === 0" class="col-span-full text-center text-gray-400 italic">
                                        Server belum memiliki Permission yang di-generate. Silakan hubungi Developer atau jalankan Seeder.
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-x-4 border-t pt-6">
                            <Link :href="route('roles.index')" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-700">Batal</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Simpan Perubahan' : 'Buat Role Baru' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
