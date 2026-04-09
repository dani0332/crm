<script setup>
const props = defineProps({
  rule: Object,
  ruleTypeEnumLeadSource: String,
});

const page = usePage();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

// Check if rule type is "lead source" (id = 1)
const isLeadSourceRuleType = computed(
  () => props.rule?.rule_type?.id == props.ruleTypeEnumLeadSource,
);
</script>
<template>
  <Head title="Rule Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Rule Detail</h2>
    <div class="flex gap-2">
      <Link
        v-if="
          can(permissionsEnum.RULE_CONFIG_LIST) ||
          can(permissionsEnum.RULE_CONFIG_UPDATE)
        "
        :href="route('rule.index')"
      >
        <x-button size="sm" color="#1d83bc" tag="div"> Rules List </x-button>
      </Link>
      <Link
        v-if="can(permissionsEnum.RULE_CONFIG_UPDATE)"
        :href="route('rule.edit', rule.id)"
      >
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
          <dt class="font-medium">Quote Type</dt>
          <dd>{{ rule.quote_type.name ?? 'N/A' }}</dd>
        </div>

        <!-- Lead Source Details (only for Lead Source rule type) -->
        <template v-if="isLeadSourceRuleType">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Lead Source</dt>
            <dd>{{ rule.rule_detail?.lead_source?.name ?? 'N/A' }}</dd>
          </div>

          <!-- <div v-if="rule.rule_detail?.utm_source" class="grid sm:grid-cols-2">
            <dt class="font-medium">UTM Source</dt>
            <dd>{{ rule.rule_detail.utm_source }}</dd>
          </div> -->

          <div
            v-if="rule.rule_detail?.utm_campaign"
            class="grid sm:grid-cols-2"
          >
            <dt class="font-medium">UTM Campaign</dt>
            <dd>{{ rule.rule_detail.utm_campaign }}</dd>
          </div>

          <!-- <div v-if="rule.rule_detail?.utm_medium" class="grid sm:grid-cols-2">
            <dt class="font-medium">UTM Medium</dt>
            <dd>{{ rule.rule_detail.utm_medium }}</dd>
          </div> -->
        </template>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Rule Users</dt>
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
