<script setup>

const uploadCustomer = useForm({
    file_name : ''
});

</script>

<template>
    <Head title="Upload Customers List" />
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">
            Upload Customers
        </h2>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
        <div class="grid sm:grid-cols-2 gap-4">
            <x-input
                v-model="uploadCustomer.file_name"
                type="file"
                label="Upload File*"
                class="border border-gray-300 rounded p-2"
                :error="customerForm.errors.first_name"
            />
            <div v-if="errors.file_name" class="text-red-500 text-sm">
                {{ errors.file_name }}
            </div>
        </div>
    </x-form>
</template>
