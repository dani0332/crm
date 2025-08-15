<script setup>
const props = defineProps({
  rule: Object,
  id: String,
  usersList: Object,
  rulesTypeList: Object,
  quoteTypes: Object,
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
  quote_type_id: props.rule?.quote_type?.id.toString() ?? '',
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
    <div class="grid sm:grid-cols-2 gap-4">
      <x-input
        label="Rule Name"
        required
        v-model="ruleForm.name"
        class="w-full"
        :error="ruleForm.errors.name"
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
</template>
