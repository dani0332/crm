<script setup>
const props = defineProps({
  quadrant: Object,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

const tires = computed(() => {
  if (props.quadrant)
    return props.quadrant.tiers.map(tier => tier.name).join(', ');
});

const users = computed(() => {
  if (props.quadrant)
    return props.quadrant.users.map(user => user.name).join(', ');
});
</script>
<template>
  <Head title="Quadrant Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Quadrant Detail</h2>
    <div class="flex gap-2">
      <Link :href="route('quadrants.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Quadrant Detail List
        </x-button>
      </Link>
      <Link :href="route('quadrants.edit', quadrant.id)">
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ID</dt>
          <dd>{{ quadrant.id }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Quadrant Name</dt>
          <dd>{{ quadrant.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Quadrant Tiers</dt>
          <dd>{{ tires }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Quadrant Users</dt>
          <dd>{{ users }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Active</dt>
          <dd>
            <x-tag size="sm" :color="quadrant.is_active ? 'success' : 'error'">
              {{ quadrant.is_active ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ dateFormat(quadrant.created_at) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ dateFormat(quadrant.updated_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
