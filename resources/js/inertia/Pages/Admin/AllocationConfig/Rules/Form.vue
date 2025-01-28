<script setup>
const props = defineProps({
  rule: Object,
  id: String,
  usersList: Object,
  rulesTypeList: Object,
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

const selectedUsers = computed(() => {
  if (!props.rule || !props.rule.rule_users) {
    return [];
  }

  return props.rule.rule_users.map(user => user.id);
});

const selectedRuleType = computed(() => {
  return props.rule && props.rule.rule_type ? props.rule.rule_type.id : null;
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
      <x-field label="Rule Name" required>
        <x-input
          v-model="ruleForm.name"
          class="w-full"
          :error="ruleForm.errors.name"
        />
      </x-field>

      <x-field label="Rule Type" required>
        <ComboBox
          v-model="ruleForm.rule_type"
          :single="true"
          :options="ruleTypes"
          :selected="selectedRuleType ? [selectedRuleType] : []"
          :error="ruleForm.errors.rule_type"
        />
      </x-field>

      <x-field label="Rule Users" required>
        <ComboBox
          v-model="ruleForm.rule_users"
          :multiple="true"
          :options="ruleUsers"
          :selected="selectedUsers"
          :error="ruleForm.errors.rule_users"
        />
      </x-field>

      <x-field label="Is Active?">
        <x-select
          v-model="ruleForm.is_active"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
          :error="ruleForm.errors.is_active"
        />
      </x-field>
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
