<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  embeddedProduct: Object,
  insuranceProviders: Object,
  quoteTypes: Object,
});
const page = usePage();
const embeddedProductsForm = useForm({
  insurance_provider_id: props.embeddedProduct?.insurance_provider_id || '',
  product_name: props.embeddedProduct?.product_name || '',
  short_code: props.embeddedProduct?.short_code || '',
  display_name: props.embeddedProduct?.display_name || '',
  product_type: props.embeddedProduct?.product_type || '',
  price: [],
  quote_type_ids: [],
  positions: props.embeddedProduct?.placements || [
    {
      quote_type_id: '',
      position: '',
    },
  ],
  pricings: props.embeddedProduct?.prices || [
    {
      price: '',
    },
  ],
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
const quoteTypesOptions = computed(() => {
  return page.props.quoteTypes.map(method => ({
    value: method.id,
    label: method.text,
  }));
});

let positionOptions = reactive([
  { value: 'frontline', label: 'FrontLine' },
  { value: 'checkout', label: 'Checkout' },
]);

const addPosition = () => {
    embeddedProductsForm.positions.push({
      quote_type_id: '',
      position: '',
    });
  },
  removePosition = () => {
    embeddedProductsForm.positions.pop();
  };

const addPricing = () => {
    embeddedProductsForm.pricings.push({
      price: '',
    });
  },
  removePricing = () => {
    embeddedProductsForm.pricings.pop();
  };

function onSubmit(isValid) {
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
        Embedded products
        <!-- <span v-if="quote">{{ quote?.uuid }}</span> -->
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
        <x-field label="Company Name" required>
          <x-select
            v-model="embeddedProductsForm.insurance_provider_id"
            :options="insuranceProviderOptions"
            :rules="[isRequired]"
            class="w-full"
          />
        </x-field>

        <x-field label="Product name" required>
          <x-input
            v-model="embeddedProductsForm.product_name"
            maxLength="255"
            :rules="[isRequired]"
            class="w-full"
            :error="embeddedProductsForm.errors.product_name"
          />
        </x-field>

        <x-field label="Shortcode" required>
          <x-input
            v-model="embeddedProductsForm.short_code"
            maxLength="3"
            :rules="[isRequired]"
            class="w-full uppercase"
            :error="embeddedProductsForm.errors.short_code"
          />
        </x-field>

        <x-field label="Display Name" required>
          <x-input
            v-model="embeddedProductsForm.display_name"
            maxLength="100"
            :rules="[isRequired]"
            class="w-full"
            :error="embeddedProductsForm.errors.display_name"
          />
        </x-field>

        <x-field label="Logic" required>
          <x-input
            v-model="embeddedProductsForm.logic"
            maxLength="100"
            :rules="[isRequired]"
            class="w-full"
            :error="embeddedProductsForm.errors.logic"
          />
        </x-field>

        <x-field label="Product Type" required>
          <x-select
            v-model="embeddedProductsForm.product_type"
            :rules="[isRequired]"
            placeholder="Product Type"
            :options="[
              { value: 'insurance', label: 'Insurance' },
              { value: 'non-insurance', label: 'Non Insurance' },
            ]"
            class="w-full"
          />
        </x-field>

        <x-field label="Description 1" required>
          <x-textarea
            v-model="embeddedProductsForm.description"
            :rules="[isRequired]"
            :adjust-to-text="false"
            class="w-full"
          />
        </x-field>

        <x-field label="Description 2" required>
          <x-textarea
            v-model="embeddedProductsForm.description2"
            :rules="[isRequired]"
            :adjust-to-text="false"
            class="w-full"
          />
        </x-field>

        <x-field label="Commission Type" required>
          <x-select
            v-model="embeddedProductsForm.commission_type"
            :rules="[isRequired]"
            placeholder="Commission type"
            :options="[
              { value: '1', label: 'Flat Amount' },
              { value: '2', label: '% Value' },
            ]"
            class="w-full"
          />
        </x-field>

        <x-field
          :label="
            embeddedProductsForm.commission_type == '2'
              ? 'Percentage Value'
              : 'Commission Value'
          "
          required
        >
          <x-input
            v-model="embeddedProductsForm.commission_value"
            type="number"
            :rules="[isRequired]"
            class="w-full"
            :error="embeddedProductsForm.errors.commission_value"
          />
        </x-field>

        <x-field label="Email Templates" required>
          <x-select
            v-model="embeddedProductsForm.email_template_id"
            :rules="[isRequired]"
            placeholder="Email Templates"
            :options="[
              { value: '1', label: 'Generic templates' },
              { value: '2', label: 'Specific templates' },
              { value: '3', label: 'OCB' },
            ]"
            class="w-full"
          />
        </x-field>

        <x-field label="Company Documents" required>
          <x-select
            v-model="embeddedProductsForm.company_documents"
            :rules="[isRequired]"
            placeholder="Company Documents"
            :options="[
              { value: '1', label: 'Certificate template' },
              { value: '2', label: 'Policy Wordings' },
            ]"
            class="w-full"
          />
        </x-field>

        <x-field label="Removal Confirmation" required>
          <x-input
            v-model="embeddedProductsForm.removal_confirmation"
            :rules="[isRequired]"
            class="w-full"
            :error="embeddedProductsForm.errors.removal_confirmation"
          />
        </x-field>
      </div>

      <div class="p-4 rounded shadow mb-6 bg-primary-50/25 mt-4">
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Placement</h3>

          <x-divider class="mb-4 mt-1" />

          <div class="grid sm:grid-cols-2 gap-4">
            <template
              v-for="(i, index) in embeddedProductsForm.positions"
              :key="index"
            >
              <x-field label="LOB" required>
                <x-select
                  v-model="embeddedProductsForm.positions[index].quote_type_id"
                  :rules="[isRequired]"
                  placeholder="Select LOB"
                  :options="quoteTypesOptions"
                  class="w-full"
                />
              </x-field>

              <x-field label="Position" required>
                <x-select
                  v-model="embeddedProductsForm.positions[index].position"
                  :rules="[isRequired]"
                  placeholder="Select Position"
                  :options="positionOptions"
                  class="w-full"
                />
              </x-field>
            </template>

            <x-button
              v-if="embeddedProductsForm.positions.length > 1"
              @click="removePosition"
              size="xs"
              outlined
              color="error"
            >
              Remove last position
            </x-button>
            <x-button @click="addPosition" size="xs" outlined color="success">
              Add another position
            </x-button>
          </div>
        </div>
      </div>

      <div class="p-4 rounded shadow mb-6 bg-primary-50/25 mt-4">
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Pricing</h3>

          <x-divider class="mb-4 mt-1" />

          <div class="grid sm:grid-cols-2 gap-4">
            <template
              v-for="(i, index) in embeddedProductsForm.pricings"
              :key="index"
            >
              <x-field class="sm:col-span-2" label="Price" required>
                <x-input
                  v-model="embeddedProductsForm.pricings[index].price"
                  type="number"
                  :rules="[isRequired]"
                  class="w-full"
                />
              </x-field>
            </template>

            <x-button
              v-if="embeddedProductsForm.pricings.length > 1"
              @click="removePricing"
              size="xs"
              outlined
              color="error"
            >
              Remove last pricing
            </x-button>
            <x-button @click="addPricing" size="xs" outlined color="success">
              Add another pricing
            </x-button>
          </div>
        </div>
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
