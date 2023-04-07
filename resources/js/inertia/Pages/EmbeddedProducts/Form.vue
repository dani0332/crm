<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  embeddedProducts: { type: Object, default: null },
});

const embeddedProductsForm = useForm({
  company_name: props.embeddedProducts?.first_name || '',
  product_name: props.embeddedProducts?.last_name || '',
  short_code: props.embeddedProducts?.last_name || '',
  display_name: props.embeddedProducts?.last_name || '',
  product_type: props.embeddedProducts?.last_name || '',
  pricing: props.embeddedProducts?.last_name || '',
  placement: props.embeddedProducts?.last_name || '',
  description: props.embeddedProducts?.last_name || '',
  description2: props.embeddedProducts?.last_name || '',
  commission_type: props.embeddedProducts?.last_name || '',
  commission_value: props.embeddedProducts?.last_name || '',
  email_template_id: props.embeddedProducts?.last_name || '',
  company_documents: props.embeddedProducts?.last_name || '',
});

const { isRequired } = useRules();

function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = `/embedded-products`;
    let title = 'Quote saved successfully';
    let redirectUrl = '/embedded-products';
    if (props.quote) {
      //   method = 'put';
      //   url = url + props.quote.uuid;
      //   title = 'Quote updated successfully';
      //   redirectUrl = `/personal-quotes/cycle/${props.quote?.uuid}`;
    }

    embeddedProductsForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },

      onSuccess: () => {
        notification.success({
          title: title,
          position: 'top',
        });

        setTimeout(function () {
          router.get(redirectUrl);
        }, 500);
      },
    });
  }
}
</script>

<template>
  <div>
    <Head title="Embedded products" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Embedded products <span v-if="quote">{{ quote?.uuid }}</span>
      </h2>
      <div>
        <Link href="/embedded-products">
          <x-button size="sm" color="#ff5e00">
            Embedded products List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert
        color="error"
        class="mb-5"
        v-if="embeddedProductsForm.errors.error"
        >{{ embeddedProductsForm?.errors?.error }}</x-alert
      >

      <div class="grid sm:grid-cols-2 gap-4">
        <x-input
          v-model="embeddedProductsForm.company_name"
          type="text"
          label="Company Name*"
          maxLength="255"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.company_name"
        />

        <x-input
          v-model="embeddedProductsForm.product_name"
          type="text"
          label="Product name*"
          maxLength="255"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.product_name"
        />

        <x-input
          v-model="embeddedProductsForm.short_code"
          type="text"
          maxLength="3"
          label="Shortcode*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.short_code"
        />
        <x-input
          v-model="embeddedProductsForm.display_name"
          type="text"
          label="Display name*"
          maxLength="100"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.display_name"
        />
        <x-select
          v-model="embeddedProductsForm.product_type"
          label="Product Type*"
          :rules="[isRequired]"
          placeholder="Product Type"
          :options="[
            { value: 'insurance', label: 'Insurance' },
            { value: 'non-insurance', label: 'Non Insurance' },
          ]"
          class="w-full"
        />

        <x-select
          v-model="embeddedProductsForm.pricing"
          label="Pricing*"
          :rules="[isRequired]"
          placeholder="Pricing"
          :options="[
            { value: 'single', label: 'Single' },
            { value: 'multiple', label: 'Multiple' },
          ]"
          class="w-full"
        />
        <!-- <x-input
          v-if="embeddedProductsForm.pricing == 'multiple'"
          label="TransApp Code"
          placeholder="TransApp Code is required"
          class="w-full"
        /> -->
        <x-input
          v-model="embeddedProductsForm.placement"
          type="text"
          label="Placement*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.placement"
        />
        <x-input
          v-model="embeddedProductsForm.description"
          type="text"
          label="Description 1*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.description"
        />
        <x-input
          v-model="embeddedProductsForm.description2"
          type="text"
          label="Description 2*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.description2"
        />
        <x-input
          v-model="embeddedProductsForm.commission_type"
          type="text"
          label="Commission type*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.commission_type"
        />
        <x-input
          v-model="embeddedProductsForm.commission_value"
          type="number"
          label="Commission value*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.commission_value"
        />
        <x-select
          v-model="embeddedProductsForm.email_template_id"
          label="Email Templates*"
          :rules="[isRequired]"
          placeholder="Email Templates"
          :options="[
            { value: 1, label: 'Generic templates' },
            { value: 2, label: 'Specific templates' },
            { value: 3, label: 'OCB' },
          ]"
          class="w-full"
        />

        <x-select
          v-model="embeddedProductsForm.company_documents"
          label="Company Documents*"
          :rules="[isRequired]"
          placeholder="Company Documents"
          :options="[
            { value: 1, label: 'Certificate template' },
            { value: 2, label: 'policy Wordings' },
          ]"
          class="w-full"
        />
      </div>

      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="embeddedProductsForm.processing"
        >
          Save
        </x-button>
      </div>
    </x-form>
  </div>
</template>
