<script setup>

const props = defineProps({
	quoteId: String,
	sendUpdateLog: Object,
	sendUpdateOptions: Array
})

const currentOption = computed(() => {
	let cat = null;
	props.sendUpdateOptions.forEach(option => {
		if (option.childs.find(op => op.id === props.sendUpdateLog.category_id)) {
			cat = option;
		}
	})
	return cat;
})

const selectedType = computed(() => {
	return currentOption?.value?.childs.find(child => child.id === props.sendUpdateLog.category_id)
})

const redirectBack = () => {
	window.location.href = `/quotes/${props.quoteId}`;
}

</script>

<template>
  <div>
		<div class="p-4 rounded shadow mb-6 bg-white">
      <x-collapse expanded show-icon>
        <template #default="{ collapsed }">
          <div class="flex justify-between items-center flex-wrap gap-2">
            <h3 class="text-lg font-semibold text-primary-800 capitalize">{{ selectedType.title }}</h3>						
          </div>
          <x-divider class="my-4" v-if="!collapsed" />
        </template>
        <template #content>
					<div class="flex mb-3 justify-end">
						<x-button color="primary" size="sm" @click="redirectBack">Go back to lead page</x-button>
					</div>
					<div class="grid grid-cols-2 gap-5">

					</div>
        </template>
      </x-collapse>
    </div>
	</div>
</template>
