<script setup>
const props = defineProps({
  rule: Object,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;
</script>
<template>
  <Head title="Rule Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Rule Detail</h2>
    <div class="flex gap-2">
      <Link :href="route('rule.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Rules Detail List
        </x-button>
      </Link>
      <Link :href="route('rule.edit', rule.id)">
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
          <dd>{{ rule.id }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Rule Name</dt>
          <dd>{{ rule.name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Rule Type</dt>
          <dd>{{ rule.rule_type.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Rule Type</dt>
          <dd>{{ rule.rule_users.map(x => x.name).toString() }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Active?</dt>
          <dd>
            <x-tag size="sm" :color="rule.is_active ? 'success' : 'error'">
              {{ rule.is_active ? 'Yes' : 'No' }}
            </x-tag>
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ dateFormat(rule.created_at) }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ dateFormat(rule.updated_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
