<script setup>
const props = defineProps({
  tier: Object,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;
</script>
<template>
  <Head title="Tier Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Tier Detail</h2>
    <div class="flex gap-2">
      <Link :href="route('tiers.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Tier Detail List
        </x-button>
      </Link>
      <Link :href="route('tiers.edit', tier.id)">
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
          <dd>{{ tier.id }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Tier Name</dt>
          <dd>{{ tier.name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Min Price</dt>
          <dd>{{ tier.min_price }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Max Price</dt>
          <dd>{{ tier.max_price }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Cost Per Lead</dt>
          <dd>{{ tier.cost_per_lead ?? 'N/A' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Ecommerce?</dt>
          <dd>
            <x-tag
              size="sm"
              :color="tier.can_handle_ecommerce ? 'success' : 'error'"
            >
              {{ tier.can_handle_ecommerce ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Null Value?</dt>
          <dd>
            <x-tag
              size="sm"
              :color="tier.can_handle_null_value ? 'success' : 'error'"
            >
              {{ tier.can_handle_null_value ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is TPL?</dt>
          <dd>
            <x-tag size="sm" :color="tier.can_handle_tpl ? 'success' : 'error'">
              {{ tier.can_handle_tpl ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Renewal (TPL_RENEWALS)?</dt>
          <dd>
            <x-tag
              size="sm"
              :color="tier.is_tpl_renewals ? 'success' : 'error'"
            >
              {{ tier.is_tpl_renewals ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Active?</dt>
          <dd>
            <x-tag size="sm" :color="tier.is_active ? 'success' : 'error'">
              {{ tier.is_active ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ dateFormat(tier.created_at) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ dateFormat(tier.updated_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
