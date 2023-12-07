<script setup>
defineProps({
  embeddedProduct: Object,
});
</script>

<template>
  <div>
    <Head title="Embedded Products" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Embedded Product Detail</h2>
      <div class="flex gap-2">
        <Link :href="`/embedded-products/${embeddedProduct.id}/edit`">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link href="/embedded-products" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Embedded Products
          </x-button>
        </Link>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Company Name</dt>
            <dd>{{ embeddedProduct.insurance_provider?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Product Name</dt>
            <dd>{{ embeddedProduct.product_name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Shortcode</dt>
            <dd>{{ embeddedProduct.short_code }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Display Name</dt>
            <dd>{{ embeddedProduct.display_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Commission Type</dt>
            <dd>
              {{
                embeddedProduct.commission_type == 1 ? 'Fixed Amout' : '% Value'
              }}
            </dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Commission Value</dt>
            <dd>{{ embeddedProduct.commission_value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Product Type</dt>
            <dd>{{ embeddedProduct.product_type }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Logic</dt>
            <dd>{{ embeddedProduct.logic }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Description</dt>
            <dd>{{ embeddedProduct.description }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Description 2</dt>
            <dd>{{ embeddedProduct.description2 }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Removal Confirmation</dt>
            <dd>{{ embeddedProduct.removal_confirmation }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Placement</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div v-for="item in embeddedProduct.placements" class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Lob</dt>
            <dd>{{ item.quote_type?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Position</dt>
            <dd>{{ item.position }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Price</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div v-for="item in embeddedProduct.prices" class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div
            v-if="embeddedProduct.pricing_type == 2"
            class="grid sm:grid-cols-2"
          >
            <dt class="font-medium">Variant</dt>
            <dd>{{ item.variant }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Price</dt>
            <dd>{{ item.price }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>
