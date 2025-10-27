<script setup>
const props = defineProps({
  embeddedProduct: Object,
  insuranceProviders: Object,
  quoteTypes: Object,
});

const page = usePage();
const isLOBEmpty = ref([]);
const isInsuranceProviderIdEmpty = ref(false);

const isEdit = computed(() => {
  return route().current() === 'embedded-products.edit';
});

const form = useForm({
  product_type: props.embeddedProduct?.product_type || '',
  product_category: props.embeddedProduct?.product_category || '',
  product_validity: props.embeddedProduct?.product_validity || '',
  insurance_provider_id: props.embeddedProduct?.insurance_provider_id || '',
  product_name: props.embeddedProduct?.product_name || '',
  short_code: props.embeddedProduct?.short_code || '',
  min_age: props.embeddedProduct?.min_age || '',
  max_age: props.embeddedProduct?.max_age || '',
  min_value: props.embeddedProduct?.min_value || '',
  max_value: props.embeddedProduct?.max_value || '',
  display_name: props.embeddedProduct?.display_name || '',
  description: props.embeddedProduct?.description || '',

  placements: props.embeddedProduct?.placements || [
    {
      quote_type_id: '',
      position: '',
    },
  ],

  pricing_type: props.embeddedProduct?.pricing_type || '1',
  pricings: props.embeddedProduct?.prices || [
    {
      variant: '',
      price: '',
      is_active: true,
    },
  ],

  commission_type: props.embeddedProduct?.commission_type || '',
  commission_value: props.embeddedProduct?.commission_value || '',
  email_template_ids: props.embeddedProduct?.email_template_ids
    ? JSON.parse(props.embeddedProduct?.email_template_ids).map(e => {
        return {
          id: e.id,
        };
      })
    : [{ id: '' }],
  uncheck_message: props.embeddedProduct?.uncheck_message || '',
  logic_description: props.embeddedProduct?.logic_description || '',
  is_active: props.embeddedProduct?.is_active || false,
  company_documents: props.embeddedProduct?.company_documents
    ? JSON.parse(props.embeddedProduct.company_documents)
    : [
        {
          path: '',
          title: '',
        },
      ],
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

const positionOptions = reactive([
  { value: 'frontline', label: 'FrontLine' },
  { value: 'checkout', label: 'Checkout' },
]);

const addPosition = () => {
    form.placements.push({
      quote_type_id: '',
      position: '',
    });
  },
  removePosition = index => {
    form.placements.splice(index, 1);
  };

const addPricing = () => {
    if (form.pricings.length >= 10) return;
    form.pricings.push({
      variant: '',
      price: '',
      is_active: true,
    });
  },
  removePricing = index => {
    form.pricings.splice(index, 1);
  };

const addEmailTemplate = () => {
    form.email_template_ids.push({
      id: '',
    });
  },
  removeEmailTemplate = index => {
    form.email_template_ids.splice(index, 1);
  };

const addMoreDocs = () => {
    form.company_documents.push({
      path: '',
      title: '',
    });
  },
  removeDoc = index => {
    form.company_documents.splice(index, 1);
  };

const updatePricingType = () => {
  form.pricings = [
    {
      variant: '',
      price: '',
      is_active: true,
    },
  ];
};
function onSubmit(isValid) {
  form.placements.forEach((placement, index) => {
    isLOBEmpty.value[index] = !placement.quote_type_id;
  });

  isInsuranceProviderIdEmpty.value = !form.insurance_provider_id;
  if (form.product_type == 'insurance' && !form.insurance_provider_id) {
    isValid = false;
  }

  if (isValid && form.placements.every(placement => placement.quote_type_id)) {
    const method = isEdit.value ? 'put' : 'post';

    const url = isEdit.value
      ? route('embedded-products.update', page.props.embeddedProduct.id)
      : route('embedded-products.store');

    const options = {
      onError: errors => {
        form.setError(errors);
      },
    };

    form
      .transform(data => ({
        ...data,
        short_code: data.short_code.toUpperCase(),
        email_template_ids: JSON.stringify(data.email_template_ids),
        company_documents: JSON.stringify(data.company_documents),
      }))
      .submit(method, url, options);
  }
}
</script>

<template>
  <div>
    <Head title="Embedded Products" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Embedded Products</h2>
      <div>
        <Link :href="route('embedded-products.index')">
          <x-button size="sm" color="primary">
            Embedded products List
          </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end mb-3">
      <x-toggle
        v-model="form.is_active"
        color="success"
        label="Product Status"
      />
    </div>
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="form.errors.error">
        {{ form?.errors?.error }}
      </x-alert>

      <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <x-select
          v-model="form.product_type"
          :rules="[isRequired]"
          placeholder="Select Product Type"
          :options="[
            { value: 'insurance', label: 'Insurance' },
            { value: 'non-insurance', label: 'Non Insurance' },
          ]"
          class="w-full"
          label="Product Type"
          required
        />

        <x-select
          v-model="form.product_category"
          :rules="[isRequired]"
          placeholder="Select Product Category"
          :options="[
            { value: 'bolt-on', label: 'Bolt-on' },
            { value: 'stand-alone', label: 'Stand-alone' },
          ]"
          class="w-full"
          label="Product Category"
          required
        />

        <x-input
          v-if="form.product_category == 'bolt-on'"
          v-model="form.product_validity"
          :rules="[]"
          placeholder="Enter number of days"
          class="w-full"
          label="Product Validity"
        />

        <x-select
          v-if="form.product_type == 'insurance'"
          v-model="form.insurance_provider_id"
          placeholder="Select Insurance Provider"
          :options="insuranceProviderOptions"
          filterable
          filterPlaceholder="Filter Insurance Provider...."
          :rules="[isRequired]"
          label="Insurance Provider"
          required
        />

        <x-input
          v-model="form.product_name"
          maxLength="255"
          :rules="[isRequired]"
          class="w-full"
          :error="form.errors.product_name"
          placeholder="Actual name of the product as per the agreement"
          label="Product Name"
          required
        />

        <x-input
          v-model="form.display_name"
          maxLength="100"
          :rules="[isRequired]"
          class="w-full"
          :error="form.errors.display_name"
          placeholder="This name will be displayed to the customer"
          label="Product Display Name"
          required
        />

        <x-input
          v-model="form.short_code"
          maxLength="3"
          minLength="3"
          :rules="[isRequired]"
          class="w-full"
          placeholder="This adds to the original Reference ID as an identifier."
          :error="form.errors.short_code"
          label="Product Shortcode"
          required
        />

        <div class="flex gap-2">
          <x-input
            v-model="form.min_age"
            type="number"
            min="0"
            class="w-1/2"
            placeholder="Minimum Age"
            :error="form.errors.min_age"
            label="Age (Min)"
          />
          <x-input
            v-model="form.max_age"
            type="number"
            min="0"
            class="w-1/2"
            placeholder="Maximum Age"
            :error="form.errors.max_age"
            label="Age (Max)"
          />
        </div>

        <div class="flex gap-2">
          <x-input
            v-model="form.min_value"
            type="number"
            min="0"
            class="w-1/2"
            placeholder="Minimum Value"
            :error="form.errors.min_value"
            label="Value (Min)"
          />
          <x-input
            v-model="form.max_value"
            type="number"
            min="0"
            class="w-1/2"
            placeholder="Maximum Value"
            :error="form.errors.max_value"
            label="Value (Max)"
          />
        </div>
      </div>

      <x-divider class="my-4" />

      <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
          <x-markdown-editor
            :toolBarProp="['bold', 'italic', 'link', 'table', 'preview']"
            id="product_description"
            v-model="form.description"
            height="max-h-72"
            placeholder="The text added here will be shown to the customer as a guide to understand basic details of the product."
            label="Tooltip /Help text"
          />
        </div>

        <div>
          <div class="mb-2">Product Wordings</div>
          <template v-for="(f, index) in form.company_documents">
            <template v-if="index === 0">
              <div
                v-if="isEdit && form.company_documents[index].path"
                class="flex gap-2 my-3"
                :key="index"
              >
                <x-button
                  size="sm"
                  outlined
                  color="success"
                  :href="
                    $page.props.cdnPath + form.company_documents[index].path
                  "
                  target="_blank"
                  download
                  class="flex-1"
                >
                  View PDF
                </x-button>
                <x-button
                  size="sm"
                  outlined
                  color="error"
                  @click="removeDoc(index)"
                >
                  Remove
                </x-button>
                <!-- {{ form.company_documents[index].path }} -->
              </div>
              <FileUploader
                v-else
                v-model="form.company_documents[index].path"
                upload-route="embedded-products.upload-document"
                accept=".pdf"
                :title="`product_wordings`"
              />
            </template>
            <template v-else>
              <x-divider
                class="my-4"
                :key="form.company_documents[index].title"
              />
              <x-input
                v-model="form.company_documents[index].title"
                class="w-full"
                placeholder="Document Type"
                :key="form.company_documents[index].title"
              />
              <FileUploader
                v-model="form.company_documents[index].path"
                upload-route="embedded-products.upload-document"
                accept=".pdf"
                :title="form.company_documents[index].title"
              />
              <x-button
                size="xs"
                outlined
                block
                color="error"
                class="mb-4"
                @click="removeDoc(index)"
                :key="form.company_documents[index].title"
              >
                Remove Attachment
              </x-button>
            </template>
          </template>
          <x-button
            size="sm"
            outlined
            block
            color="primary"
            @click="addMoreDocs"
          >
            Attach more Documents
          </x-button>
        </div>
      </div>

      <x-divider class="my-4" label="PLACEMENT" />

      <template v-for="(f, index) in form.placements" :key="index">
        <div class="grid sm:grid-cols-2 gap-4">
          <x-select
            v-model="form.placements[index].quote_type_id"
            placeholder="Select LOB"
            :options="quoteTypesOptions"
            filterable
            filterPlaceholder="Filter LOB...."
            :rules="[isRequired]"
            label="LOB"
            required
          />

          <div class="flex gap-3 items-start">
            <x-select
              v-model="form.placements[index].position"
              :rules="[isRequired]"
              placeholder="Select Position"
              :options="positionOptions"
              class="w-full"
              label="Position"
              required
            />
            <div class="mt-[23px]">
              <x-button
                :disabled="form.placements.length == 1"
                @click="removePosition(index)"
                ghost
                color="error"
                icon="xc"
              />
            </div>
          </div>
        </div>
      </template>

      <div class="w-full mt-1">
        <x-button size="sm" outlined color="primary" @click="addPosition">
          Add LOB
        </x-button>
      </div>

      <x-divider class="my-4" label="PRICING" />

      <div>
        <x-select
          v-model="form.pricing_type"
          :rules="[isRequired]"
          placeholder="Select LOB"
          :options="[
            { value: '1', label: 'Single' },
            { value: '2', label: 'Multiple' },
            { value: '3', label: 'Dynamic' },
          ]"
          class="w-full"
          @update:model-value="updatePricingType"
          label="Type"
        />

        <template v-for="(f, index) in form.pricings" :key="index">
          <div
            :class="
              form.pricing_type == 2 ? 'sm:grid-cols-3' : 'sm:grid-cols-2'
            "
            class="grid gap-4"
          >
            <x-input
              v-if="form.pricing_type == '2'"
              v-model="form.pricings[index].variant"
              class="w-full"
              :rules="[isRequired]"
              label="Variant"
              required
            />

            <x-input
              v-model="form.pricings[index].price"
              type="number"
              step="0.01"
              :rules="[isRequired]"
              class="w-full"
              label="Price without VAT"
              required
            />

            <div class="flex gap-3 items-center">
              <x-input
                :modelValue="(form.pricings[index].price * 1.05).toFixed(2)"
                class="w-full"
                readonly
                label="Price with 5% VAT"
              />

              <x-toggle
                v-model="form.pricings[index].is_active"
                class="w-full"
                label="Status"
              />

              <div v-if="form.pricing_type == 2">
                <x-button
                  :disabled="form.pricings.length == 1"
                  size="sm"
                  outlined
                  color="error"
                  icon="xc"
                  @click="removePricing(index)"
                />
              </div>
            </div>
          </div>
          <x-divider
            v-if="
              form.pricing_type == 2 &&
              form.pricings.length > 1 &&
              index != form.pricings.length - 1
            "
            class="my-4 sm:col-span-3"
          />
        </template>
      </div>

      <div class="w-full" v-if="form.pricing_type == 2">
        <x-button @click="addPricing" size="sm" outlined color="primary">
          Add Variant
        </x-button>
      </div>

      <x-divider
        class="my-4"
        :label="
          form.product_type === 'insurance'
            ? 'Commission Type'
            : 'Fees to myAlfred'
        "
      />

      <div class="grid gap-4 sm:grid-cols-2">
        <x-select
          v-model="form.commission_type"
          :rules="[isRequired]"
          placeholder="Select Type"
          :options="[
            { value: '1', label: 'Flat Amount' },
            { value: '2', label: '% of Price' },
          ]"
          class="w-full"
          label="Type"
          required
        />

        <x-input
          v-model="form.commission_value"
          :rules="[isRequired]"
          class="w-full"
          label="Amount"
          required
        />
      </div>

      <x-divider class="my-4" />

      <div class="grid gap-4 sm:grid-cols-3">
        <div class="sm:col-span-1 flex flex-col">
          <template v-for="(f, index) in form.email_template_ids" :key="index">
            <div class="mb-3">
              <div class="mb-1">Email Template ID</div>
              <div class="flex gap-2">
                <x-input
                  v-model="form.email_template_ids[index].id"
                  class="w-full"
                />
                <x-button
                  :disabled="form.email_template_ids.length == 1"
                  @click="removeEmailTemplate(index)"
                  ghost
                  color="error"
                  icon="xc"
                />
              </div>
            </div>
          </template>
          <x-button
            size="sm"
            outlined
            color="primary"
            @click="addEmailTemplate"
          >
            Add Email Template ID
          </x-button>
        </div>

        <div class="sm:col-span-2">
          <x-markdown-editor
            id="uncheck_message"
            v-model="form.uncheck_message"
            placeholder="The text added here will be shown to the customer when He/She unticks the checkbox to purchase this product at the time of checkout."
            label="Uncheck Message"
          />
        </div>
      </div>

      <x-divider class="my-4" />

      <x-textarea
        v-model="form.logic_description"
        class="w-full"
        rows="5"
        :adjust-to-text="false"
        placeholder="The text added here will be used as a reference to build any additional logic for this particular product. Example : Tyre insurance should be displayed only to customers insuring brand new vehicle."
        label="Complete Logic Description"
        :required="form.pricing_type == 3"
      />

      <div class="flex justify-end gap-3 my-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          class="px-6"
          :loading="form.processing"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>

<style>
.x-divider div {
  @apply !text-base !text-primary-600 !font-semibold;
}
</style>
