<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    template: Object,
    components: Array,
    positions: Array,
});

// Map already assigned components to array of IDs
const assignedComponentIds = props.template.components.map(c => c.payroll_component_id);

const form = useForm({
    name: props.template.name,
    employment_type: props.template.employment_type,
    position_id: props.template.position_id || '',
    components: assignedComponentIds,
});

const submit = () => {
    form.put(route('templates.update', props.template.id));
};
</script>

<template>
    <Head title="Edit Template Gaji" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('templates.index')" class="text-gray-500 hover:text-gray-700">
                    &larr; Kembali
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Template: {{ template.name }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-8">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-2 gap-6 mb-8">
                            <div>
                                <InputLabel for="name" value="Nama Template" />
                                <TextInput id="name" type="text" v-model="form.name" class="mt-1 block w-full" />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="employment_type" value="Status/Tipe Karyawan" />
                                <select id="employment_type" v-model="form.employment_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="permanent">Pegawai Tetap (Permanent)</option>
                                    <option value="contract">Pegawai Kontrak</option>
                                    <option value="outsource">Outsource</option>
                                    <option value="freelance">Freelance / Harian</option>
                                </select>
                                <InputError :message="form.errors.employment_type" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <InputLabel for="position_id" value="Target Jabatan (Spesifik)" />
                                <select id="position_id" v-model="form.position_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Semua Jabatan (Berlaku General)</option>
                                    <option v-for="pos in positions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
                                </select>
                                <InputError :message="form.errors.position_id" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Komponen Gaji</h3>
                            <InputError :message="form.errors.components" class="mt-2 mb-4" />

                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                                <label v-for="comp in components" :key="comp.id" class="flex items-start border p-3 rounded hover:bg-gray-50 cursor-pointer">
                                    <div class="flex items-center h-5">
                                        <input 
                                            type="checkbox" 
                                            :value="comp.id"
                                            v-model="form.components"
                                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" 
                                        />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-medium text-gray-900">{{ comp.name }}</span>
                                        <p class="text-gray-500 text-xs mt-0.5">
                                            {{ comp.component_type === 'earning' ? 'Penambah' : 'Pengurang' }} 
                                            | {{ comp.is_variable ? 'Variabel' : 'Tetap' }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end border-t pt-6">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Simpan Perubahan
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
