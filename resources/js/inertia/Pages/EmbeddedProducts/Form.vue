<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  embeddedProduct: Object,
  insuranceProviders: Object,
});
const page = usePage();
const embeddedProductsForm = useForm({
  insurance_provider_id: props.embeddedProduct?.insurance_provider_id || '',
  product_name: props.embeddedProduct?.product_name || '',
  short_code: props.embeddedProduct?.short_code || '',
  display_name: props.embeddedProduct?.display_name || '',
  product_type: props.embeddedProduct?.product_type || '',
  pricing: props.embeddedProduct?.pricing || '',
  placement: props.embeddedProduct?.placement || '',
  logic: props.embeddedProduct?.logic || '',
  description: props.embeddedProduct?.description || '',
  description2: props.embeddedProduct?.description2 || '',
  commission_type: props.embeddedProduct?.commission_type || '',
  commission_value: props.embeddedProduct?.commission_value || '',
  email_template_id: props.embeddedProduct?.email_template_id || '',
  company_documents: props.embeddedProduct?.company_documents || '',
  removal_confirmation: props.embeddedProduct?.removal_confirmation || '',
});

const { isRequired } = useRules();

const insuranceProviderOptions = computed(() => {
  return page.props.insuranceProviders.map(method => ({
    value: method.id,
    label: method.text,
  }));
});

function onSubmit(isValid) {
  console.log(embeddedProductsForm);

  if (isValid) {
    let method = 'post';
    let url = `/embedded-products/`;
    let title = 'Product saved successfully';
    let redirectUrl = '/embedded-products';
    if (props.embeddedProduct) {
      method = 'put';
      url = url + props.embeddedProduct.id;
      title = 'Product updated successfully';
      redirectUrl = `/embedded-products/${props.embeddedProduct?.id}`;
    }

    embeddedProductsForm
      .transform(data => ({
        ...data,
        short_code: data.short_code.toUpperCase(),
      }))
      .submit(method, url, {
        onError: errors => {
          console.log(errors);
          console.log(embeddedProductsForm.setError(errors));
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
    <Head title="Embedded Products" />
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
      >
        {{ embeddedProductsForm?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 gap-4">
        <x-select
          v-model="embeddedProductsForm.insurance_provider_id"
          label="Company Name*"
          :options="insuranceProviderOptions"
          :rules="[isRequired]"
          class="w-full"
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
          class="w-full uppercase"
          :error="embeddedProductsForm.errors.short_code"
          v-uppercase
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
        <x-input
          v-model="embeddedProductsForm.logic"
          type="text"
          label="Logic*"
          maxLength="100"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.logic"
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

        <!-- <x-select
          v-model="embeddedProductsForm.pricing"
          label="Pricing*"
          :rules="[isRequired]"
          placeholder="Pricing"
          :options="[
            { value: 'single', label: 'Single' },
            { value: 'multiple', label: 'Multiple' },
          ]"
          class="w-full"
        /> -->

        <!-- <x-input
          v-model="embeddedProductsForm.placement"
          type="text"
          label="Placement*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.placement"
        /> -->
        <x-textarea
          v-model="embeddedProductsForm.description"
          label="Description 1*"
          :adjust-to-text="false"
          class="w-full"
        />

        <x-textarea
          v-model="embeddedProductsForm.description2"
          label="Description 2*"
          :adjust-to-text="false"
          class="w-full"
        />
        <x-select
          v-model="embeddedProductsForm.commission_type"
          label="Commission type*"
          :rules="[isRequired]"
          placeholder="Commission type"
          :options="[
            { value: '1', label: 'Flat Amount' },
            { value: '2', label: '% Value' },
          ]"
          class="w-full"
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
            { value: '1', label: 'Generic templates' },
            { value: '2', label: 'Specific templates' },
            { value: '3', label: 'OCB' },
          ]"
          class="w-full"
        />

        <x-select
          v-model="embeddedProductsForm.company_documents"
          label="Company Documents*"
          :rules="[isRequired]"
          placeholder="Company Documents"
          :options="[
            { value: '1', label: 'Certificate template' },
            { value: '2', label: 'policy Wordings' },
          ]"
          class="w-full"
        />

        <x-input
          v-model="embeddedProductsForm.removal_confirmation"
          type="text"
          label="Removal Confirmation*"
          :rules="[isRequired]"
          class="w-full"
          :error="embeddedProductsForm.errors.removal_confirmation"
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
