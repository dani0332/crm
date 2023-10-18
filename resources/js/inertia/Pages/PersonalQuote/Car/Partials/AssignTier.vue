<script setup>
const props = defineProps({
    quote: Object,
    tiers: Array
})
const { isRequired } = useRules();
const notification = useNotifications('toast');
const rules = {
    isRequired: v => !!v || 'This field is required',
};

const tierForm = useForm({
    selectedLeadId: props.quote.id,
    entityCode: props.quote.uuid,
    modelType: 'Car',
    selectedTierId: null
});

const onAssignTier = () => {

    if (tierForm.selectedTierId == null) {
        notification.error({
            title: 'Please select a tier',
            position: 'top',
        });
        return;
    }

    tierForm.post('/quotes/manual-tier-assignment', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            tierForm.clear();
            notification.success({
                title: 'Tier Assigned Successfully',
                position: 'top',
            });
        },
        onError: (err) => {
            console.log('err', err);
            notification.error({
                title: 'Something went wrong',
                position: 'top',
            });
        }
    })
}

</script>

<template>
<div class="p-4 rounded shadow mb-6 bg-white">			
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
            Assign Tier
        </h3>
    </div>
    <x-divider class="mb-4 mt-1" />
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">				
        <div class="w-full md:w-50">
            <x-field label="Select Tier" required>
                <x-select
                    v-model="tierForm.selectedTierId"                    
                    :options="tiers.map(tier => ({
                        label: tier.name,
                        value: tier.id
                    }))"
                    :error="tierForm.errors.selectedTierId"
                    placeholder="Select Tier"
                    :rules="[rules.isRequired]"
                    class="w-full"
                />
            </x-field>
        </div>
        <div class="w-full md:w-50">
            <x-button
                class="mt-6"
                color="emerald"
                size="sm"
                :loading="tierForm.processing"
                @click.prevent="onAssignTier"
            >
                Assign
            </x-button>
        </div>
    </div>
</div>
</template>