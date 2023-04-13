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
  positions: [],
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
let lobCounter = ref(0);
let positionCounter = ref(0);
let priceCounter = ref(0);

let pricingData = reactive([
  {
    id: 'price0',
    label: 'Price',
    value: '',
  },
]);

let data = reactive([
  {
    lob: {
      id: 'lob0',
      label: 'Lob',
      value: '',
    },
    position: {
      id: 'pos0',
      label: 'Position',
      value: '',
    },
  },
]);

let commissionlabel = ref('Commission value*');
let positionOptions = reactive([
  { value: 'frontline', label: 'FrontLine' },
  { value: 'checkout', label: 'Checkout' },
]);

function adNewPrice() {
  pricingData.push({
    id: `price${++priceCounter.value}`,
    label: 'Price',
    value: '',
  });
}
function deletePrice(index) {
  if (index) {
    this.pricingData.splice(index, 1);
  }
}
function addNewLobAndPostion() {
  let newObj = {
    lob: {
      id: `lob${++lobCounter.value}`,
      label: 'Lob',
      value: '',
    },
    position: {
      id: `position${++positionCounter.value}`,
      label: 'Position',
      value: '',
    },
  };
  data.push(newObj);
}
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
          :label="commissionlabel"
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

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Placement</h3>
        <div class="flex justify-end gap-3 mb-1">
          <x-button @click="addNewLobAndPostion" size="md" color="emerald">
            add new
          </x-button>
        </div>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <div v-for="(input, index) in data" :key="index">
          <div class="grid sm:grid-cols-2 gap-4">
            <x-select
              v-model="embeddedProductsForm.quote_type_ids[index]"
              :label="input.lob.label"
              :options="quoteTypesOptions"
              class="w-full"
            />
          </div>
          <div class="grid sm:grid-cols-2 gap-4">
            <x-select
              v-model="embeddedProductsForm.positions[index]"
              :label="input.position.label"
              :options="positionOptions"
              class="w-full"
            />
          </div>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Pricing</h3>
        <div class="flex justify-end gap-3 mb-1">
          <x-button @click="adNewPrice" size="md" color="emerald">
            add new
          </x-button>
        </div>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div v-for="(input, index) in pricingData" :key="index">
            <x-input
              v-model="embeddedProductsForm.price[index]"
              type="number"
              :label="input.label"
              class="w-full"
            />
            <x-button @click="deletePrice(index)">delete</x-button>
          </div>
        </dl>
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
