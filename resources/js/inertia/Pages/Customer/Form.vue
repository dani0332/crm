<script setup>
defineProps({
  customer: { type: Object, default: null },
  nationalities: { type: Object, default: null },
});

const page = usePage();
const notification = useToast();
const { isRequired, isEmail } = useRules();

const customerForm = useForm({
  first_name: page.props.customer.first_name || '',
  last_name: page.props.customer.last_name || '',
  email: page.props.customer.email || '',
  mobile_no: page.props.customer.mobile_no || '',
  gender: page.props.customer.gender || '',
  lang: page.props.customer.lang || '',
  dob: page.props.customer.dob || null,
  nationality_id: page.props.customer.nationality_id || null,
  has_alfred_access: page.props.customer.has_alfred_access || null,
  has_reward_access: page.props.customer.has_reward_access || null,
  receive_marketing_updates:
    page.props.customer.receive_marketing_updates || null,
});

const nationalityOptions = computed(() => {
  return page.props.nationalities.map(nationality => ({
    value: nationality.id,
    label: nationality.text,
  }));
});

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/customer/`;
    let title = 'Customer saved successfully';

    if (page.props.customer) {
      method = 'put';
      url = url + page.props.customer.uuid;
      title = 'Customer updated successfully';
    }

    customerForm.submit(method, url, {
      onError: errors => {
        console.log(customerForm.setError(errors));
      },
      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });
      },
    });
  } else {
    notification.error({
      title: 'Error while submitting customer. Please try again',
      position: 'top',
    });
  }
}
</script>

<template>
  <Head title="Customer" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Edit Customer</h2>
    <div>
      <Link href="/customer">
        <x-button size="sm" color="#ff5e00"> Customers List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :autofocus="false">
    <x-alert color="error" class="mb-5" v-if="customerForm.errors.error">
      {{ customerForm?.errors?.error }}
    </x-alert>
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="First Name" required>
        <x-input
          v-model="customerForm.first_name"
          :rules="[isRequired]"
          class="w-full"
          type="text"
          :error="customerForm.errors.first_name"
        />
      </x-field>
      <x-field label="Last Name" required>
        <x-input
          v-model="customerForm.last_name"
          :rules="[isRequired]"
          class="w-full"
          type="text"
          :error="customerForm.errors.last_name"
        />
      </x-field>
      <x-field label="Email" required>
        <x-input
          v-model="customerForm.email"
          :rules="[isRequired, isEmail]"
          class="w-full"
          type="email"
          :error="customerForm.errors.email"
        />
      </x-field>
      <x-input
        v-model="customerForm.mobile_no"
        type="tel"
        label="Phone Number"
        class="w-full"
        :error="customerForm.errors.mobile_no"
      />
      <x-input
        v-model="customerForm.gender"
        type="text"
        label="Gender"
        class="w-full"
        :error="customerForm.errors.gender"
      />
      <x-input
        v-model="customerForm.lang"
        type="text"
        label="Language"
        class="w-full"
        :error="customerForm.errors.lang"
      />
      <DatePicker
        v-model="customerForm.dob"
        name="created_at_start"
        label="Date of Birth"
        :hasError="customerForm.errors.dob"
      />
      <ComboBox
        v-model="customerForm.nationality_id"
        label="Nationality"
        :single="true"
        :options="nationalityOptions"
        :error="customerForm.errors.nationality_id"
      />
      <div class="flex gap-5">
        <x-form-group v-model="customerForm.has_alfred_access">
          <x-checkbox label="Has Alfred Access" color="primary" />
          <x-checkbox label="Has Reward Access" color="primary" />
        </x-form-group>
        <div class="flex mt-0.5">
          <x-checkbox
            v-model="customerForm.receive_marketing_updates"
            color="primary"
            class="font-normal"
            style="font-weight: 300"
          />
          <x-label> Receive Marketing Updates </x-label>
        </div>
      </div>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="customerForm.processing"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>
