<script setup>
import ComboBox from '@/inertia/Components/ComboBox.vue';
import CreateLeadSourceModal from '@/inertia/Components/CreateLeadSourceModal.vue';

const props = defineProps({
  rule: Object,
  id: String,
  usersList: Object,
  rulesTypeList: Object,
  quoteTypes: Object,
  leadSourcesList: Object,
  ruleTypeEnumLeadSource: String,
});
const { isRequired, isNumber } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const ruleForm = useForm({
  id: props.rule?.id ?? null,
  name: props.rule?.name ?? null,
  is_active: props.rule?.is_active ? true : false,
  rule_users: props.rule?.rule_users.map(x => x.id) ?? [],
  rule_type: props.rule?.rule_type.id ?? null,
  quote_type_id: props.rule?.quote_type?.id ?? null,
  lead_source_id: props.rule?.rule_detail?.lead_source_id ?? null,
  utm_source: props.rule?.rule_detail?.utm_source ?? null,
  utm_campaign: props.rule?.rule_detail?.utm_campaign ?? null,
  utm_medium: props.rule?.rule_detail?.utm_medium ?? null,
});

const ruleUsers = computed(() => {
  let rule_users = Object.values(props.usersList);
  return rule_users.map(user => {
    return {
      value: user.id,
      label: user.name,
    };
  });
});

const ruleTypes = computed(() => {
  let rulesTypeList = Object.values(props.rulesTypeList);
  return rulesTypeList.map(user => {
    return {
      value: user.id,
      label: user.name,
    };
  });
});

const quoteTypesOptions = computed(() => {
  let quoteTypesList = Object.values(props.quoteTypes);
  return quoteTypesList.map(quoteType => {
    return {
      value: quoteType.id,
      label: quoteType.name,
    };
  });
});

const leadSourcesOptions = computed(() => {
  let leadSourcesList = Object.values(props.leadSourcesList);
  return leadSourcesList.map(leadSource => {
    return {
      value: leadSource.id,
      label: leadSource.name,
    };
  });
});

// State for Create Lead Source Modal
const showCreateLeadSourceModal = ref(false);
const localLeadSources = ref([...Object.values(props.leadSourcesList)]);

// Computed property that uses local lead sources
const leadSourcesOptionsLocal = computed(() => {
  return localLeadSources.value.map(leadSource => {
    return {
      value: leadSource.id,
      label: leadSource.name,
    };
  });
});

// Handle new lead source creation
const handleLeadSourceCreated = newLeadSource => {
  // Add to local lead sources list
  localLeadSources.value.push(newLeadSource);

  // Auto-select the newly created lead source
  ruleForm.lead_source_id = newLeadSource.id;

  // Close the modal
  showCreateLeadSourceModal.value = false;
};

// Check if the selected rule type is "LEAD SOURCE" (id = 1)
const isLeadSourceRuleType = computed(() => {
  return ruleForm.rule_type == props.ruleTypeEnumLeadSource;
});

const selectedUsers = computed(() => {
  if (!props.rule || !props.rule.rule_users) {
    return [];
  }

  return props.rule.rule_users.map(user => user.id);
});

const selectedRuleType = computed(() => {
  return props.rule && props.rule.rule_type ? props.rule.rule_type.id : null;
});

const selectedQuoteType = computed(() => {
  return props.rule && props.rule.quote_type ? props.rule.quote_type.id : null;
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('rule.update', ruleForm.id)
      : route('rule.store');

    ruleForm.processing = true;
    ruleForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          ruleForm.setError(key, errors[key]);
        });
        ruleForm.processing = false;
        return false;
      },
      onSuccess: () => {
        ruleForm.processing = false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Rule' : 'Create Rule'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Rules
    </h2>
    <div>
      <Link :href="route('rule.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Rules List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />

  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-6">
      <x-select
        v-model="ruleForm.quote_type_id"
        label="Quote Type"
        :options="quoteTypesOptions"
        :error="ruleForm.errors.quote_type_id"
        filterable
        filterPlaceholder="Filter Quote Type...."
        placeholder="Select Quote Type"
        required
        :rules="[isRequired]"
      />

      <x-input
        label="Rule Name"
        required
        v-model="ruleForm.name"
        class="w-full"
        :error="ruleForm.errors.name"
        placeholder="Enter rule name"
      />

      <x-select
        label="Is Active?"
        v-model="ruleForm.is_active"
        class="w-full"
        :options="[
          { value: true, label: 'Yes' },
          { value: false, label: 'No' },
        ]"
        :error="ruleForm.errors.is_active"
      />

      <x-select
        v-model="ruleForm.rule_type"
        label="Rule Type"
        :options="ruleTypes"
        :error="ruleForm.errors.rule_type"
        filterable
        filterPlaceholder="Filter Rule Type...."
        placeholder="Select Rule Type"
        required
        :rules="[isRequired]"
      />
    </div>

    <!-- Lead Source Section -->
    <div v-if="isLeadSourceRuleType" class="grid sm:grid-cols-2 gap-6 mt-6">
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Lead Source <span class="text-red-600">*</span>
        </label>
        <div class="flex gap-3 items-start">
          <div class="flex-1">
            <ComboBox
              v-model="ruleForm.lead_source_id"
              :options="leadSourcesOptionsLocal"
              single
              placeholder="Select Lead Source"
              :has-error="!!ruleForm.errors.lead_source_id"
              required
              :rules="[isRequired]"
            />
            <p
              v-if="ruleForm.errors.lead_source_id"
              class="mt-1 text-sm text-red-600"
            >
              {{ ruleForm.errors.lead_source_id }}
            </p>
          </div>
          <x-button
            type="button"
            size="md"
            color="primary"
            @click="showCreateLeadSourceModal = true"
          >
            + New
          </x-button>
        </div>
      </div>

      <!-- <x-input
        label="UTM Source"
        v-model="ruleForm.utm_source"
        class="w-full"
        :error="ruleForm.errors.utm_source"
        placeholder="e.g., google, facebook, newsletter"
      /> -->

      <x-input
        label="UTM Campaign"
        v-model="ruleForm.utm_campaign"
        class="w-full"
        :error="ruleForm.errors.utm_campaign"
        placeholder="e.g., summer_sale, product_launch"
      />

      <!-- <x-input
        label="UTM Medium"
        v-model="ruleForm.utm_medium"
        class="w-full"
        :error="ruleForm.errors.utm_medium"
        placeholder="e.g., cpc, email, social"
      /> -->
    </div>

    <!-- Rule Users -->
    <div class="grid sm:grid-cols-2 gap-6 mt-6">
      <div class="sm:col-span-2">
        <x-select
          v-model="ruleForm.rule_users"
          label="Rule Users"
          :options="ruleUsers"
          :error="ruleForm.errors.rule_users"
          multiple
          filterable
          filterPlaceholder="Filter Rule Users...."
          placeholder="Select Rule Users"
          required
          :rules="[isRequired]"
          truncate
          class="w-full"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                ruleForm.rule_users = ruleUsers.map(item => item.value)
              "
              @clear="ruleForm.rule_users = []"
            />
          </template>
        </x-select>
      </div>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="ruleForm.processing"
      >
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>

  <!-- Create Lead Source Modal -->
  <CreateLeadSourceModal
    v-model="showCreateLeadSourceModal"
    @created="handleLeadSourceCreated"
  />
</template>
