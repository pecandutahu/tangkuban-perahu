<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    permission: Object,
});

const isEdit = !!props.permission.id;

const form = useForm({
    name: props.permission.name || '',
});

const submit = () => {
    if (isEdit) {
        form.put(route('permissions.update', props.permission.id));
    } else {
        form.post(route('permissions.store'));
    }
};

</script>

<template>
    <Head :title="isEdit ? 'Ubah Sandi Izin' : 'Buat Izin Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ isEdit ? 'Rubah Tag Izin: ' + permission.name : 'Pendaftaran Kunci Izin Spesifik' }}
                </h2>
                <Link :href="route('permissions.index')" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                    &larr; Kembali
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                    
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Panduan Penamaan:</strong> Sistem akan otomatis mengubah spasi menjadi tanda hubung layaknya format web (kebab-case).<br>Contoh ideal yang disarankan: <strong>buat pendaftaran cuti</strong> akan menjadi <code class="bg-white px-1 font-bold">buat-pendaftaran-cuti</code>. Gunakan kata kerja spesifik.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Sandi Izin Akses (Nama Tag)" />
                            <TextInput 
                                id="name" 
                                type="text" 
                                class="mt-1 block w-full lg:w-1/2" 
                                v-model="form.name" 
                                placeholder="cth: cetak laporan bulanan"
                                required 
                                autofocus 
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center gap-x-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ isEdit ? 'Simpan' : 'Tambahkan Kunci' }}
                            </PrimaryButton>
                            <Link :href="route('permissions.index')" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-700 text-decoration-underline">Batal</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
